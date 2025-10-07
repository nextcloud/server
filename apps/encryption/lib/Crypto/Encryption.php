<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Crypto;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Encryption\Exceptions\EncryptionFailedException;
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
	 * Initializes the encryption or decryption process for a file.
	 *
	 * i.e. Start receiving chunks from a file. This is the place to perform any initial steps 
	 * before starting encryption/decryption of the chunks.
	 *
	 * @param string $path Path to the file being processed.
	 * @param string $user User performing the operation.
	 * @param string $mode Operation mode ('r' for read, 'w' for write).
	 * @param array  $header File encryption metadata/header.
	 * @param array  $accessList List of users/keys with access.
	 * @return array Metadata for further processing (i.e. header data key-value pairs if writing or empty array if done).
	 */
	public function begin(string $path, string $user, string $mode, array $header, array $accessList): array {
		// All write-capable modes supported by Nextcloud.
		$writeModes = ['w', 'w+', 'wb', 'wb+'];

		// Resolve the actual file path and initialize core properties.
		$this->path = $this->getPathToRealFile($path);
		$this->user = $user;
		$this->accessList = $accessList;
		$this->writeCache = '';
		$this->isWriteOperation = in_array($mode, $writeModes, true);
		// Default to legacy encoding unless specified (generally will be, but conservative for BC).
		$this->useLegacyBase64Encoding = true;

		// Respect encoding specified in header.
		if (isset($header['encoding'])) {
			$this->useLegacyBase64Encoding = $header['encoding'] !== Crypt::BINARY_ENCODING_FORMAT;
		}

		// Ensure encryption session is ready; initialize master key if needed.
		if ($this->session->isReady() === false) {
			// If the master key is enabled we can initialize encryption
			// with an empty password and username.
			if ($this->util->isMasterKeyEnabled()) {
				$this->keyManager->init('', '');
			}
		}

		// Detect legacy file key usage safely and defensively.
		$useLegacyKeyFile = null;
		if (isset($header['useLegacyFileKey'])) {
			// TODO: Confirm if `==` is here for a reason and if not clean this up...
			$useLegacyFileKey = ($header['useLegacyFileKey'] == 'false') ? false : null;
		}

		// XXX ?????
		$this->fileKey = $this->keyManager->getFileKey(
			$this->path,
			$useLegacyFileKey,
			$this->session->decryptAllModeActivated()
		);

		// Always use the version from the original file; part files also
		// need to have a correct version number if moved to the final location.
		$this->version = (int)$this->keyManager->getVersion($this->util->stripPartialFileExtension($path), new View());

		if ($this->isWriteOperation) {
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
	 * Finalizes the encryption process for a file, handling buffered data and key updates.
	 *
	 * @param string $path     Path to the file being finalized.
	 * @param string $position Position in the file (for block-wise encryption).
	 * @return string          Remaining encrypted data to be written, if any.
	 * @throws PublicKeyMissingException
	 * @throws MultiKeyEncryptException
	 * @throws \Exception
	 */
	public function end(string $path, string $position = '0'): string {
		// Only perform actions if this is a write operation.
		if (!$this->isWriteOperation) {
			return '';
		}
		
		// Remember new signature version for partial files.
		if (Scanner::isPartialFile($path)) {
			self::$rememberVersion[$this->util->stripPartialFileExtension($path)] = $this->version + 1;
		}

		// Encrypt any remaining data in the write cache.
		$result = '';
		if (!empty($this->writeCache)) {
			try {
				$result = $this->crypt->symmetricEncryptFileContent(
					$this->writeCache,
					$this->fileKey,
					$this->version + 1,
					$position
				);
				$this->writeCache = '';
			} catch (\Throwable $e) {
				$this->logger->error('Encryption failure during final block: ' . $e->getMessage(), [
					'file' => $this->path ?? $path,
					'user' => $this->user ?? 'unknown',
					'app'  => 'encryption'
				]);
				throw new EncryptionFailedException(
					'Encryption failed during final block.',
					$e->getMessage(),
					$e->getCode(),
					$e
				);
			}
		}

		// Build the list of public keys required for the finalized access list.
		$publicKeys = [];
		if ($this->useMasterPassword === true) {
			$masterKeyId = $this->keyManager->getMasterKeyId();
			$publicKeys[$masterKeyId] = $this->keyManager->getPublicMasterKey();
		} else {
			foreach ($this->accessList['users'] as $accessUid) {
				try {
					$publicKeys[$accessUid] = $this->keyManager->getPublicKey($accessUid);
				} catch (PublicKeyMissingException $e) {
					$this->logger->warning(
						'No public key found for user "{uid}", user will not be able to read the file.',
						['uid' => $accessUid, 'error' => $e->getMessage(), 'app' => 'encryption']
					);
					// If the public key of the owner is missing we should fail.
					if ($accessUid === $this->user) {
						throw $e;
					}
				}
			}
		}

		// Add system-level keys (e.g. public link share keys, recovery keys).
		$owner = $this->getOwner($path);
		$publicKeys = $this->keyManager->addSystemKeys($this->accessList, $publicKeys, $owner);

		// Encrypt the file key for all relevant public keys.
		$shareKeys = $this->crypt->multiKeyEncrypt($this->fileKey, $publicKeys);

		// Remove legacy file keys for improved security.
		if (!$this->keyManager->deleteLegacyFileKey($this->path)) {
			$this->logger->warning(
				'Failed to delete legacy filekey for {path}.',
				['app' => 'encryption', 'path' => $path]
			);
		}

		// Store the new share keys for each user.
		foreach ($shareKeys as $shareKeyUid => $keyFile) {
			$this->keyManager->setShareKey($this->path, $shareKeyUid, $keyFile);
		}

		return $result ?: '';
	}

	/**
	 * Encrypts a chunk of file data.
	 *
	 * @param string $data The plaintext data to encrypt.
	 * @param int $position The position in the file (for block-wise encryption).
	 * @return string The encrypted data.
	 * @throws EncryptionFailedException If encryption fails.	 
	 */
	public function encrypt(string $data, int $position = 0): string {
		// Integrate leftover buffered data from previous round.
		if (!empty($this->writeCache)) {
			$data = $this->writeCache . $data;
			$this->writeCache = '';
		}

		$encrypted = '';
		$blockSize = $this->getUnencryptedBlockSize(true);
		
		// Process data in block-sized chunks.
		while (strlen($data) > 0) {
			$remainingLength = strlen($data);

			// Buffer incomplete blocks for future encryption.
			if ($remainingLength < $blockSize) {
				$this->writeCache = $data;
				break;
			}
			// Encrypt a full block.
			$chunk = substr($data, 0, $blockSize);

			try {
				$encryptedChunk = $this->crypt->symmetricEncryptFileContent(
					$chunk,
					$this->fileKey,
					$this->version + 1,
					(string)$position
				);
			} catch (\Throwable $e) {
				$this->logger->error('Encryption failure: ' . $e->getMessage(), [
					'file' => $this->path ?? 'unknown',
					'user' => $this->user ?? 'unknown',
					'app'  => 'encryption'
				]);
				throw new \OC\Encryption\Exceptions\EncryptionFailedException(
					'Encryption failed.',
					$e->getMessage(),
					$e->getCode(), 
					$e
				);
			}

			$encrypted .= $encryptedChunk;
			
			// Remove processed chunk from data.
			$data = substr($data, $blockSize);
		}

		return $encrypted;
	}

	/**
	 * Decrypts a chunk of encrypted file data.
	 *
	 * @param string $data The encrypted data to decrypt.
	 * @param int|string $position Position in the file (for block-wise decryption).
	 * @return string The decrypted data chunk.
	 * @throws DecryptionFailedException If the file key is missing or decryption fails.
	 */
	public function decrypt(string $data, int|string $position = 0): string {
		// Robustness: Ensure we have the file key before attempting decryption.
		if (empty($this->fileKey)) {
			$message = 'Cannot decrypt this file; this is probably a shared file. Please ask the file owner to reshare the file with you.';
			$hint = $this->l->t($message);
			$this->logger->error($message, [
				'file' => $this->path ?? 'unknown',
				'user' => $this->user ?? 'unknown',
				'app'  => 'encryption'
			]);
			throw new DecryptionFailedException($message, $hint);
		}

		// Perform decryption using the cryptographic service.
		try {
			return $this->crypt->symmetricDecryptFileContent(
				$data,
				$this->fileKey,
				$this->cipher,
				$this->version,
				(string)$position,
				!$this->useLegacyBase64Encoding
			);
		} catch (\Throwable $e) {
			// Robustness: Catch all exceptions, log, and throw a uniform decryption failure.
			$errorMsg = 'Decryption failure: ' . $e->getMessage();
			$this->logger->error($errorMsg, [
				'file' => $this->path ?? 'unknown',
				'user' => $this->user ?? 'unknown',
				'app'  => 'encryption'
			]);
			throw new DecryptionFailedException('Decryption failed.', $errorMsg);
		}
	}

	/**
	 * Updates the encrypted file’s access keys for new access permissions.
	 *
	 * @param string $path Path to the file to update.
	 * @param string $uid User performing the operation.
	 * @param array $accessList Access permissions: ['users' => [...], 'public' => [...]].
	 * @return bool True on success, false otherwise.
	 */
	public function update(string $path, string $uid, array $accessList): bool {
		// If no access list is provided, handle possible remembered version and return early.
		if (empty($accessList)) {
			if (isset(self::$rememberVersion[$path])) {
				$version = self::$rememberVersion[$path];
				$this->keyManager->setVersion($path, $version, new View());
				unset(self::$rememberVersion[$path]);
			}
			return false;
		}

		// Fetch the file encryption key.
		$fileKey = $this->keyManager->getFileKey($path, null);
		if (empty($fileKey)) {
			$this->logger->debug(
				'No file key found; assuming file "{file}" is not encrypted.',
				['file' => $path, 'app' => 'encryption']
			);
			return false;
		}

		// Build the list of public keys required for the updated access list.
		$publicKeys = [];
		if ($this->useMasterPassword) {
			$masterKeyId = $this->keyManager->getMasterKeyId();
			$publicKeys[$masterKeyId] = $this->keyManager->getPublicMasterKey();
		} else {
			foreach ($accessList['users'] as $accessUid) {
				try {
					$publicKeys[$accessUid] = $this->keyManager->getPublicKey($accessUid);
				} catch (PublicKeyMissingException $e) {
					$this->logger->warning(
						'No public key found for user "{user}", user will not be able to read the file.',
						['user' => $accessUid, 'error' => $e->getMessage(), 'app' => 'encryption']
					);
					// Robustness: continue so file isn't left inaccessible, but missing keys/users won't have access.
					// Exception: If the public key of the owner is missing we should outright fail (or at least pass 
					// the buck more strongly than just logging the warning like for others).
					if ($accessUid === $this->user) {
						throw $e;
					}
				}
			}
		}

		// Add system-level keys (e.g. public link share and recovery keys).
		$owner = $this->getOwner($path);
		$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys, $owner);

		// Encrypt the file key for all relevant public keys.
		$shareKeys = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);

		// Remove all previous share keys (security: avoid stale access).
		$this->keyManager->deleteAllFileKeys($path);

		// Write the new share keys for each user.
		foreach ($shareKeys as $shareKeyUid => $keyFile) {
			$this->keyManager->setShareKey($path, $shareKeyUid, $keyFile);
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
