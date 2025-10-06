<?php

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
 * Implements an encryption module for Nextcloud's Server-side Encryption (SSE).
 *
 * @since 9.1.0
 */
class Encryption implements IEncryptionModule {
	// The technical ID for this encryption module. Must be unique.
	public const ID = 'OC_DEFAULT_MODULE';
	// Human-readable name of this encryption module.
	public const DISPLAY_NAME = 'Default encryption module';

	/**
	 * The cipher algorithm used for encrypting and decrypting file contents (e.g., AES-256-CFB).
	 * Set during file operations based on file header or defaults.
	 *
	 * @var string
	 */
	private string $cipher;

	/**
	 * The absolute path to the file being processed.
	 * Used for key management and file operations.
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Username of the user performing the read/write operation.
	 *
	 * @var string
	 */
	private string $user;

	/**
	 * Cached map of file paths to their respective owners.
	 * Used to avoid repeated lookups.
	 *
	 * @var array<string, string>
	 */
	private array $owner;

	/**
	 * The encryption key used for the current file operation.
	 *
	 * @var string
	 */
	private string $fileKey;

	/**
	 * Buffer/cache for data that has not yet been encrypted and written.
	 * Used for block-wise encryption.
	 *
	 * @var string
	 */
	private string $writeCache;

	/**
	 * List of users and public entities that have access to the file.
	 * Contains the keys 'users' and 'public'.
	 *
	 * @var array
	 */
	private array $accessList;

	/**
	 * Indicates whether the current operation is a write operation.
	 *
	 * @var bool
	 */
	private bool $isWriteOperation;

	/**
	 * Indicates whether the master password (master key) is being used for encryption.
	 *
	 * @var bool
	 */
	private bool $useMasterPassword;

	/**
	 * Flag for whether legacy base64 encoding is used for file encryption.
	 * Legacy encoding affects block size calculation and compatibility.
	 *
	 * @var bool
	 */
	private bool $useLegacyBase64Encoding = false;

	/**
	 * The current version of the file being processed.
	 * Used for key and signature versioning.
	 *
	 * @var int
	 */
	private int $version = 0;

	/**
	 * Static cache mapping file paths to remembered encryption signature versions.
	 * Used during multipart and update operations.
	 *
	 * @var array<string, int>
	 */
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

	/**
	 * Returns the technical unique ID of the encryption module.
	 *
	 * @return string Technical unique ID.
	 */
	public function getId(): string {
		return self::ID;
	}

	/**
	 * Unlike getId(), this function returns a human-readable (possibly translated) name of the encryption module.
	 *
	 * @return string Display name of the encryption module.
	 */
	public function getDisplayName(): string {
		return self::DISPLAY_NAME;
	}

	/**
	 * Start receiving chunks from a file. This is the place to perform any initial steps 
	 * before starting encryption/decryption of the chunks.
	 *
	 * @param string $path Path to the file.
	 * @param string $user User reading or writing the file.
	 * @param string $mode PHP stream open mode.
	 * @param array $header Header data read from the file.
	 * @param array $accessList List of users and public access; contains the keys 'users' and 'public'.
	 *
	 * @return array Header data as key-value pairs to be written to the header in case of a write operation,
	 *               or an empty array if no additional data is needed.
	 */
	public function begin(string $path, string $user, string $mode, array $header, array $accessList): array {
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
			// If the master key is enabled we can initialize encryption
			// with an empty password and username.
			if ($this->util->isMasterKeyEnabled()) {
				$this->keyManager->init('', '');
			}
		}

		/* If useLegacyFileKey is not specified in header, auto-detect, to be safe */
		$useLegacyFileKey = (($header['useLegacyFileKey'] ?? '') == 'false' ? false : null);

		$this->fileKey = $this->keyManager->getFileKey($this->path, $useLegacyFileKey, $this->session->decryptAllModeActivated());

		// Always use the version from the original file; part files also
		// need to have a correct version number if moved to the final location.
		$this->version = (int)$this->keyManager->getVersion($this->util->stripPartialFileExtension($path), new View());

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
			// If we read a part file we need to increase the version by 1,
			// because the version number was also increased by writing the part file.
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
			// If we read a file without a header, fall back to the legacy cipher
			// which was used in <=oC6.
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

	/**
	 * Last chunk received. This is the place to perform any final operations and return any remaining
	 * data if something is left in the buffer.
	 *
	 * @param string $path Path to the file.
	 * @param string $position Position.
	 * @return string Remaining data to be written to the file in case of a write operation.
	 * @throws PublicKeyMissingException
	 * @throws \Exception
	 * @throws MultiKeyEncryptException
	 */
	public function end(string $path, string $position = '0'): string {
		$result = '';
		if ($this->isWriteOperation) {
			// In case of a part file, remember the new signature versions.
			// The version will be set later on update.
			// This ensures that other apps listening to the pre-hooks
			// still get the old version, which should be the correct value for them.
			if (Scanner::isPartialFile($path)) {
				self::$rememberVersion[$this->util->stripPartialFileExtension($path)] = $this->version + 1;
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
							'No public key found for user "{uid}", user will not be able to read the file.',
							['app' => 'encryption', 'uid' => $uid]
						);
						// If the public key of the owner is missing we should fail.
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
					'Failed to delete legacy filekey for {path}.',
					['app' => 'encryption', 'path' => $path]
				);
			}
			foreach ($shareKeys as $uid => $keyFile) {
				$this->keyManager->setShareKey($this->path, $uid, $keyFile);
			}
		}
		return $result ?: '';
	}

	/**
	 * Encrypts data.
	 *
	 * @param string $data Data to encrypt.
	 * @param int $position Position in the file.
	 * @return string Encrypted data.
	 */
	public function encrypt(string $data, int $position = 0): string {
		// If extra data is left over from the last round, make sure it
		// is integrated into the next block.
		if ($this->writeCache) {
			// Concat writeCache to start of $data.
			$data = $this->writeCache . $data;

			// Clear the write cache, ready for reuse - it has been
			// flushed and its old contents processed.
			$this->writeCache = '';
		}

		$encrypted = '';
		// While there still remains some data to be processed & written.
		while (strlen($data) > 0) {
			// Remaining length for this iteration, not of the
			// entire file (may be greater than 8192 bytes).
			$remainingLength = strlen($data);

			// If data remaining to be written is less than the
			// size of 1 unencrypted block.
			if ($remainingLength < $this->getUnencryptedBlockSize(true)) {
				// Set writeCache to contents of $data.
				// The writeCache will be carried over to the
				// next write round, and added to the start of
				// $data to ensure that written blocks are
				// always the correct length. If there is still
				// data in writeCache after the writing round
				// has finished, then the data will be written
				// to disk by $this->flush().
				$this->writeCache = $data;

				// Clear $data ready for next round.
				$data = '';
			} else {
				// Read the chunk from the start of $data.
				$chunk = substr($data, 0, $this->getUnencryptedBlockSize(true));

				$encrypted .= $this->crypt->symmetricEncryptFileContent($chunk, $this->fileKey, $this->version + 1, (string)$position);

				// Remove the chunk we just processed from
				// $data, leaving only unprocessed data in $data
				// var, for handling on the next round.
				$data = substr($data, $this->getUnencryptedBlockSize(true));
			}
		}

		return $encrypted;
	}

	/**
	 * Decrypts data.
	 *
	 * @param string $data Data to decrypt.
	 * @param int|string $position Position in the file.
	 * @return string Decrypted data.
	 * @throws DecryptionFailedException
	 */
	public function decrypt(string $data, int|string $position = 0): string {
		if (empty($this->fileKey)) {
			$msg = 'Cannot decrypt this file; this is probably a shared file. Please ask the file owner to reshare the file with you.';
			$hint = $this->l->t('Cannot decrypt this file; this is probably a shared file. Please ask the file owner to reshare the file with you.');
			$this->logger->error($msg);

			throw new DecryptionFailedException($msg, $hint);
		}

		return $this->crypt->symmetricDecryptFileContent($data, $this->fileKey, $this->cipher, $this->version, $position, !$this->useLegacyBase64Encoding);
	}

	/**
	 * Updates the encrypted file, for example, granting additional users access.
	 *
	 * @param string $path Path to the file to update.
	 * @param string $uid User performing the operation.
	 * @param array $accessList List of users and public access; contains the keys 'users' and 'public'.
	 * @return bool True on success, false otherwise.
	 */
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
			$this->logger->debug('No file key found; we assume that the file "{file}" is not encrypted.',
				['file' => $path, 'app' => 'encryption']);

			return false;
		}

		return true;
	}

	/**
	 * Determines if a file at the given path should be encrypted based on storage type and path characteristics.
	 *
	 * @param string $path The file path to check.
	 * @return bool True if the file should be encrypted, false otherwise.
	 */
	public function shouldEncrypt(string $path): bool {
		// If home storage encryption is disabled, and this is a home storage, don't encrypt
		if ($this->util->shouldEncryptHomeStorage() === false) {
			$storage = $this->util->getStorage($path);
			if ($storage && $storage->instanceOfStorage('\OCP\Files\IHomeStorage')) {
				return false;
			}
		}

		// Ensure the path has enough segments to be valid for encryption
		$parts = explode('/', $path);
		if (count($parts) < 4) {
			return false;
		}

		// Only encrypt files in certain folders
		$encryptFolders = [
			'files',
			'files_versions',
			'files_trashbin'
		];
		if (in_array($parts[2], $encryptFolders, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the maximum number of bytes of unencrypted data that can fit in a single file block,
	 * taking into account encryption overhead and encoding.
	 * 
	 * Block structure details:
	 * - Base block size: 8192 bytes
	 * - Overheads:
	 *   - IV: 22 bytes
	 *   - Padding: 2 bytes (unsigned), +1 byte if signed
	 *   - Signature: 71 bytes (if signed)
	 * - Legacy base64 encoding further reduces capacity by a factor of 0.75
	 *
	 * Final sizes:
	 * - Unsigned binary: 8168 bytes
	 * - Signed binary: 8096 bytes
	 * - Unsigned base64: 6126 bytes
	 * - Signed base64: 6072 bytes
	 *
	 * @param bool $signed Whether the block is cryptographically signed.
	 * @return int Number of bytes available for unencrypted data.
	 */
	public function getUnencryptedBlockSize(bool $signed = false): int {
		if (!$this->useLegacyBase64Encoding) {
			return $signed ? 8096 : 8168;
		} else {
			return $signed ? 6072 : 6126;
		}
	}

	/**
	 * Checks if the specified file is readable (decryptable) for the given user.
	 *
	 * @param string $path Path to the file.
	 * @param string $uid User for whom to check readability.
	 * @return bool True if readable, false otherwise.
	 * @throws DecryptionFailedException If the file is shared and the user can't read it.
	 */
	public function isReadable(string $path, string $uid): bool {
		$fileKey = $this->keyManager->getFileKey($path, null);
		
		if (empty($fileKey)) {
			$owner = $this->util->getOwner($path);
			
			if ($owner !== $uid) {
				// If it is a shared file, throw an exception with a useful
				// error message, because this means the file was shared
				// with the user at a point where the user didn't have a
				// valid private/public key.
				$msg = 'Encryption module "' . $this->getDisplayName()
					. '" is not able to read ' . $path;
				$msg = sprintf(
					'Encryption module "%s" is not able to read %s',
					$this->getDisplayName(),
					$path
				);
				$hint = $this->l->t(
					'Cannot read this file, probably this is a shared file. Please ask the file owner to reshare the file with you.'
				);
				$this->logger->warning($msg);
				throw new DecryptionFailedException($msg, $hint);
			}
			
			return false;
		}

		return true;
	}

	/**
	 * Initiates encryption of all files using the encryption module.
	 * This method delegates to the encryptAll service and streams status information to the provided output.
	 *
	 * @param InputInterface $input  Input interface for user interaction (CLI or otherwise).
	 * @param OutputInterface $output Output interface for status and progress updates.
	 */
	public function encryptAll(InputInterface $input, OutputInterface $output): void {
		$this->encryptAll->encryptAll($input, $output);
	}

	/**
	 * Prepares the encryption module for the 'decrypt all' operation.
	 * Delegates to the DecryptAll service, which performs all necessary setup.
	 *
	 * @param InputInterface $input The input interface for user interaction.
	 * @param OutputInterface $output The output interface for user feedback.
	 * @param string $user (optional) User whose files should be prepared for decryption. If omitted, assumes all users.
	 * @return bool True if preparation succeeded; false otherwise.
	 */
	public function prepareDecryptAll(InputInterface $input, OutputInterface $output, string $user = ''): bool {
		return $this->decryptAll->prepare($input, $output, $user);
	}

	/**
	 * Resolves the real file path from a versioned file path.
	 * If the provided path points to a versioned file, this method reconstructs
	 * the path to its corresponding original file and strips any version suffix.
	 *
	 * @param string $path The (possibly versioned) file path.
	 * @return string The canonical file path.
	 */
	protected function getPathToRealFile(string $path): string {
		$parts = explode('/', $path);
		
		// Robustness: ensure the path has enough segments for version detection
		if (count($parts) > 2 && $parts[2] === 'files_versions') {
			// Reconstruct the original file path
			$realPath = '/' . $parts[1] . '/files/' . implode('/', array_slice($parts, 3));

			// Remove the file version suffix if present (e.g., .v123)
			$dotPos = strrpos($realPath, '.');
			if ($dotPos !== false) {
				return substr($realPath, 0, $dotPos);
			}
			return $realPath;
		}
		// If not a versioned path, return as is
		return $path;
	}

	/**
	 * Retrieves the owner of a file, caching the result for future lookups.
	 *
	 * @param string $path Path to the file.
	 * @return string Owner of the file.
	 */
	protected function getOwner(string $path): string {
		if (!isset($this->owner[$path])) {
			$this->owner[$path] = $this->util->getOwner($path);
		}
		return $this->owner[$path];
	}

	/**
	 * Checks if the module is ready to be used by the specified user.
	 * Returns false if key pairs have not been generated for the user.
	 *
	 * @param string $user User to check.
	 * @return bool True if ready, false otherwise.
	 * @since 9.1.0
	 */
	public function isReadyForUser(string $user): bool {
		if ($this->util->isMasterKeyEnabled()) {
			return true;
		}
		return $this->keyManager->userHasKeys($user);
	}

	/**
	 * Indicates whether the encryption module needs a detailed list of users with access to the file.
	 * For example, modules using per-user encryption keys may require this information.
	 *
	 * @return bool True if a detailed access list is required, false otherwise.
	 */
	public function needDetailedAccessList(): bool {
		return !$this->util->isMasterKeyEnabled();
	}
}
