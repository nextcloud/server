<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Crypto;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Files\Cache\Scanner;
use OC\Files\View;
use OCA\Encryption\Exceptions\MultiKeyEncryptException;
use OCA\Encryption\Exceptions\PublicKeyMissingException;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\Encryption\IEncryptionModule;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Default file content encryption module.
 *
 * Implements block-based encryption, decryption, key management,
 * and access control for user data storage.
 *
 * @see \OCP\Encryption\IEncryptionModule for detailed method documentation and contract.
 */
class Encryption implements IEncryptionModule {
	public const ID = 'OC_DEFAULT_MODULE';
	public const DISPLAY_NAME = 'Default encryption module';

	private string $cipher;
	private string $path;
	private ?string $user;
	private array $owner;
	private string $fileKey;
	private string $writeCache;
	private array $accessList;
	private bool $isWriteOperation;
	private bool $useMasterPassword;
	private bool $useLegacyBase64Encoding = false;
	// Current version of the file
	private int $version = 0;
	// Remember encryption signature version
	private static array $rememberVersion = [];

	public function __construct(
		private Crypt $crypt,
		private KeyManager $keyManager,
		private Util $util,
		private Session $session,
		private EncryptAll $encryptAll,
		private DecryptAll $decryptAll,
		private LoggerInterface $logger,
		private IL10N $l,
	) {
		$this->owner = [];
		$this->useMasterPassword = $this->util->isMasterKeyEnabled();
	}

	public function getId(): string {
		return self::ID;
	}

	public function getDisplayName(): string {
		return self::DISPLAY_NAME;
	}

	public function begin(string $path, ?string $user, string $mode, array $header, array $accessList): array {
		$this->path = $this->getPathToRealFile($path);
		$this->accessList = $accessList;
		$this->user = $user;
		$this->isWriteOperation = false;
		$this->writeCache = '';
		$this->useLegacyBase64Encoding = true;

		if (isset($header['encoding'])) {
			$this->useLegacyBase64Encoding = $header['encoding'] !== Crypt::BINARY_ENCODING_FORMAT;
		}

		if ($this->session->isReady() === false) {
			// if the master key is enabled we can initialize encryption
			// with a empty password and user name
			if ($this->util->isMasterKeyEnabled()) {
				$this->keyManager->init('', '');
			}
		}

		// If useLegacyFileKey is not specified in header, auto-detect, to be safe
		$useLegacyFileKey = (($header['useLegacyFileKey'] ?? '') == 'false' ? false : null);

		$this->fileKey = $this->keyManager->getFileKey($this->path, $useLegacyFileKey, $this->session->decryptAllModeActivated());

		// always use the version from the original file, also part files
		// need to have a correct version number if they get moved over to the
		// final location
		$this->version = (int)$this->keyManager->getVersion($this->stripPartFileExtension($path), new View());

		if (
			$mode === 'w'
			|| $mode === 'w+'
			|| $mode === 'wb'
			|| $mode === 'wb+'
		) {
			$this->isWriteOperation = true;
			if (empty($this->fileKey)) {
				$this->fileKey = $this->crypt->generateFileKey();
			}
		} else {
			// if we read a part file we need to increase the version by 1
			// because the version number was also increased by writing
			// the part file
			if (Scanner::isPartialFile($path)) {
				$this->version = $this->version + 1;
			}
		}

		if ($this->isWriteOperation) {
			$this->cipher = $this->crypt->getCipher();
			$this->useLegacyBase64Encoding = $this->crypt->useLegacyBase64Encoding();
		} elseif (isset($header['cipher'])) {
			$this->cipher = $header['cipher'];
		} else {
			// if we read a file without a header we fall-back to the legacy cipher
			// which was used in <=oC6
			$this->cipher = $this->crypt->getLegacyCipher();
		}

		$result = [
			'cipher' => $this->cipher,
			'signed' => 'true',
			'useLegacyFileKey' => 'false',
		];

		if ($this->useLegacyBase64Encoding !== true) {
			$result['encoding'] = Crypt::BINARY_ENCODING_FORMAT;
		}

		return $result;
	}

	public function end(string $path, string $position = '0'): string {
		$result = '';
		if ($this->isWriteOperation) {
			// in case of a part file we remember the new signature versions
			// the version will be set later on update.
			// This way we make sure that other apps listening to the pre-hooks
			// still get the old version which should be the correct value for them
			if (Scanner::isPartialFile($path)) {
				self::$rememberVersion[$this->stripPartFileExtension($path)] = $this->version + 1;
			}
			if (!empty($this->writeCache)) {
				$result = $this->crypt->symmetricEncryptFileContent($this->writeCache, $this->fileKey, $this->version + 1, $position);
				$this->writeCache = '';
			}
			$publicKeys = [];
			if ($this->useMasterPassword === true) {
				$publicKeys[$this->keyManager->getMasterKeyId()] = $this->keyManager->getPublicMasterKey();
			} else {
				foreach ($this->accessList['users'] as $uid) {
					try {
						$publicKeys[$uid] = $this->keyManager->getPublicKey($uid);
					} catch (PublicKeyMissingException $e) {
						$this->logger->warning(
							'no public key found for user "{uid}", user will not be able to read the file',
							['app' => 'encryption', 'uid' => $uid]
						);
						// if the public key of the owner is missing we should fail
						if ($uid === $this->user) {
							throw $e;
						}
					}
				}
			}

			$publicKeys = $this->keyManager->addSystemKeys($this->accessList, $publicKeys, $this->getOwner($path));
			$shareKeys = $this->crypt->multiKeyEncrypt($this->fileKey, $publicKeys);
			if (!$this->keyManager->deleteLegacyFileKey($this->path)) {
				$this->logger->warning(
					'Failed to delete legacy filekey for {path}',
					['app' => 'encryption', 'path' => $path]
				);
			}
			foreach ($shareKeys as $uid => $keyFile) {
				$this->keyManager->setShareKey($this->path, $uid, $keyFile);
			}
		}
		return $result ?: '';
	}

	public function encrypt(string $data, string $position = '0'): string {
		// If extra data is left over from the last round, make sure it
		// is integrated into the next block
		if ($this->writeCache) {
			// Concat writeCache to start of $data
			$data = $this->writeCache . $data;
			// Clear the write cache, ready for reuse - it has been
			// flushed and its old contents processed
			$this->writeCache = '';
		}

		$encrypted = '';
		// While there still remains some data to be processed & written
		while (strlen($data) > 0) {
			// Remaining length for this iteration, not of the
			// entire file (may be greater than 8192 bytes)
			$remainingLength = strlen($data);

			// If data remaining to be written is less than the
			// size of 1 unencrypted block
			if ($remainingLength < $this->getUnencryptedBlockSize(true)) {
				// Set writeCache to contents of $data
				// The writeCache will be carried over to the
				// next write round, and added to the start of
				// $data to ensure that written blocks are
				// always the correct length. If there is still
				// data in writeCache after the writing round
				// has finished, then the data will be written
				// to disk by $this->flush().
				$this->writeCache = $data;

				// Clear $data ready for next round
				$data = '';
			} else {
				// Read the chunk from the start of $data
				$chunk = substr($data, 0, $this->getUnencryptedBlockSize(true));

				$encrypted .= $this->crypt->symmetricEncryptFileContent($chunk, $this->fileKey, $this->version + 1, $position);

				// Remove the chunk we just processed from
				// $data, leaving only unprocessed data in $data
				// var, for handling on the next round
				$data = substr($data, $this->getUnencryptedBlockSize(true));
			}
		}

		return $encrypted;
	}

	public function decrypt(string $data, string $position = '0'): string {
		if (empty($this->fileKey)) {
			$msg = 'Cannot decrypt this file, probably this is a shared file. Please ask the file owner to reshare the file with you.';
			$hint = $this->l->t('Cannot decrypt this file, probably this is a shared file. Please ask the file owner to reshare the file with you.');
			$this->logger->error($msg);

			throw new DecryptionFailedException($msg, $hint);
		}

		return $this->crypt->symmetricDecryptFileContent(
			$data,
			$this->fileKey,
			$this->cipher,
			$this->version,
			$position,
			!$this->useLegacyBase64Encoding
		);
	}

	public function update(string $path, string $uid, array $accessList): bool {
		if (empty($accessList)) {
			if (isset(self::$rememberVersion[$path])) {
				$this->keyManager->setVersion($path, self::$rememberVersion[$path], new View());
				unset(self::$rememberVersion[$path]);
			}
			return false;
		}

		$fileKey = $this->keyManager->getFileKey($path, null);

		if (!empty($fileKey)) {
			$publicKeys = [];
			if ($this->useMasterPassword === true) {
				$publicKeys[$this->keyManager->getMasterKeyId()] = $this->keyManager->getPublicMasterKey();
			} else {
				foreach ($accessList['users'] as $user) {
					try {
						$publicKeys[$user] = $this->keyManager->getPublicKey($user);
					} catch (PublicKeyMissingException $e) {
						$this->logger->warning('Could not encrypt file for ' . $user . ': ' . $e->getMessage());
					}
				}
			}

			$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys, $this->getOwner($path));

			$shareKeys = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);

			$this->keyManager->deleteAllFileKeys($path);

			foreach ($shareKeys as $uid => $keyFile) {
				$this->keyManager->setShareKey($path, $uid, $keyFile);
			}
		} else {
			$this->logger->debug('no file key found, we assume that the file "{file}" is not encrypted',
				['file' => $path, 'app' => 'encryption']);

			return false;
		}

		return true;
	}

	public function shouldEncrypt(string $path): bool {
		if ($this->util->shouldEncryptHomeStorage() === false) {
			$storage = $this->util->getStorage($path);
			if ($storage && $storage->instanceOfStorage('\OCP\Files\IHomeStorage')) {
				return false;
			}
		}
		$parts = explode('/', $path);
		if (count($parts) < 4) {
			return false;
		}

		if ($parts[2] === 'files') {
			return true;
		}
		if ($parts[2] === 'files_versions') {
			return true;
		}
		if ($parts[2] === 'files_trashbin') {
			return true;
		}

		return false;
	}

	/**
	 * Get size of the unencrypted payload per block.
	 * Nextcloud reads/writes files with a block size of 8192 byte.
	 *
	 * Encrypted blocks have a 22-byte IV and 2 bytes of padding; encrypted and
	 * signed blocks have also a 71-byte signature and 1 more byte of padding,
	 * resulting respectively in:
	 *   8192 - 22 - 2 = 8168 bytes (in each unsigned unencrypted block
	 *   8192 - 22 - 2 - 71 - 1 = 8096 bytes (in each signed unencrypted block)
	 *
	 * Legacy base64 encoding then reduces the available size by a 3/4 factor:
	 *   8168 * (3/4) = 6126 bytes (in each base64-encoded unsigned unencrypted block)
	 *   8096 * (3/4) = 6072 bytes (in each base64-encoded signed unencrypted block)
	 */
	public function getUnencryptedBlockSize(bool $signed = false): int {
		if ($this->useLegacyBase64Encoding) {
			return $signed ? 6072 : 6126;
		} else {
			return $signed ? 8096 : 8168;
		}
	}

	public function isReadable(string $path, string $uid): bool {
		$fileKey = $this->keyManager->getFileKey($path, null);
		if (empty($fileKey)) {
			$owner = $this->util->getOwner($path);
			if ($owner !== $uid) {
				// if it is a shared file we throw a exception with a useful
				// error message because in this case it means that the file was
				// shared with the user at a point where the user didn't had a
				// valid private/public key
				$msg = 'Encryption module "' . $this->getDisplayName()
					. '" is not able to read ' . $path;
				$hint = $this->l->t('Cannot read this file, probably this is a shared file. Please ask the file owner to reshare the file with you.');
				$this->logger->warning($msg);
				throw new DecryptionFailedException($msg, $hint);
			}
			return false;
		}

		return true;
	}

	public function encryptAll(InputInterface $input, OutputInterface $output): void {
		$this->encryptAll->encryptAll($input, $output);
	}

	public function prepareDecryptAll(InputInterface $input, OutputInterface $output, string $user = ''): bool {
		return $this->decryptAll->prepare($input, $output, $user);
	}

	public function isReadyForUser(string $user): bool {
		if ($this->util->isMasterKeyEnabled()) {
			return true;
		}
		return $this->keyManager->userHasKeys($user);
	}

	public function needDetailedAccessList(): bool {
		return !$this->util->isMasterKeyEnabled();
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function getPathToRealFile(string $path): string {
		$realPath = $path;
		$parts = explode('/', $path);
		if ($parts[2] === 'files_versions') {
			$realPath = '/' . $parts[1] . '/files/' . implode('/', array_slice($parts, 3));
			$length = strrpos($realPath, '.');
			$realPath = substr($realPath, 0, $length);
		}

		return $realPath;
	}

	/**
	 * remove .part file extension and the ocTransferId from the file to get the
	 * original file name
	 *
	 * @param string $path
	 * @return string
	 */
	protected function stripPartFileExtension(string $path): string {
		if (pathinfo($path, PATHINFO_EXTENSION) === 'part') {
			$pos = strrpos($path, '.', -6);
			$path = substr($path, 0, $pos);
		}

		return $path;
	}

	/**
	 * get owner of a file
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getOwner(string $path): string {
		if (!isset($this->owner[$path])) {
			$this->owner[$path] = $this->util->getOwner($path);
		}
		return $this->owner[$path];
	}
}
