<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\Encryption\Util;
use OC\Files\Cache\CacheEntry;
use OC\Files\Filesystem;
use OC\Files\Mount\Manager;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\Storage\Common;
use OC\Files\Storage\LocalTempFileTrait;
use OC\Memcache\ArrayCache;
use OCP\Cache\CappedMemoryCache;
use OCP\Encryption\Exceptions\InvalidHeaderException;
use OCP\Encryption\IFile;
use OCP\Encryption\IManager;
use OCP\Encryption\Keys\IStorage;
use OCP\Files;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\GenericFileException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage;
use Psr\Log\LoggerInterface;

class Encryption extends Wrapper {
	use LocalTempFileTrait;

	private string $mountPoint;
	protected array $unencryptedSize = [];
	private IMountPoint $mount;
	/** for which path we execute the repair step to avoid recursions */
	private array $fixUnencryptedSizeOf = [];
	/** @var CappedMemoryCache<bool> */
	private CappedMemoryCache $encryptedPaths;
	private bool $enabled = true;

	/**
	 * @param array $parameters
	 */
	public function __construct(
		array $parameters,
		private IManager $encryptionManager,
		private Util $util,
		private LoggerInterface $logger,
		private IFile $fileHelper,
		private ?string $uid,
		private IStorage $keyStorage,
		private Manager $mountManager,
		private ArrayCache $arrayCache,
	) {
		$this->mountPoint = $parameters['mountPoint'];
		$this->mount = $parameters['mount'];
		$this->encryptedPaths = new CappedMemoryCache();
		parent::__construct($parameters);
	}

	public function filesize(string $path): int|float|false {
		$fullPath = $this->getFullPath($path);

		$info = $this->getCache()->get($path);
		if ($info === false) {
			/* Pass call to wrapped storage, it may be a special file like a part file */
			return $this->storage->filesize($path);
		}
		if (isset($this->unencryptedSize[$fullPath])) {
			$size = $this->unencryptedSize[$fullPath];

			// Update file cache (only if file is already cached).
			// Certain files are not cached (e.g. *.part).
			if (isset($info['fileid'])) {
				if ($info instanceof ICacheEntry) {
					$info['encrypted'] = $info['encryptedVersion'];
				} else {
					/**
					 * @psalm-suppress RedundantCondition
					 */
					if (!is_array($info)) {
						$info = [];
					}
					$info['encrypted'] = true;
					$info = new CacheEntry($info);
				}

				if ($size !== $info->getUnencryptedSize()) {
					$this->getCache()->update($info->getId(), [
						'unencrypted_size' => $size
					]);
				}
			}

			return $size;
		}

		if (isset($info['fileid']) && $info['encrypted']) {
			return $this->verifyUnencryptedSize($path, $info->getUnencryptedSize());
		}

		return $this->storage->filesize($path);
	}

	private function modifyMetaData(string $path, array $data): array {
		$fullPath = $this->getFullPath($path);
		$info = $this->getCache()->get($path);

		if (isset($this->unencryptedSize[$fullPath])) {
			$data['encrypted'] = true;
			$data['size'] = $this->unencryptedSize[$fullPath];
			$data['unencrypted_size'] = $data['size'];
		} else {
			if (isset($info['fileid']) && $info['encrypted']) {
				$data['size'] = $this->verifyUnencryptedSize($path, $info->getUnencryptedSize());
				$data['encrypted'] = true;
				$data['unencrypted_size'] = $data['size'];
			}
		}

		if (isset($info['encryptedVersion']) && $info['encryptedVersion'] > 1) {
			$data['encryptedVersion'] = $info['encryptedVersion'];
		}

		return $data;
	}

	public function getMetaData(string $path): ?array {
		$data = $this->storage->getMetaData($path);
		if (is_null($data)) {
			return null;
		}
		return $this->modifyMetaData($path, $data);
	}

	public function getDirectoryContent(string $directory): \Traversable {
		$parent = rtrim($directory, '/');
		foreach ($this->getWrapperStorage()->getDirectoryContent($directory) as $data) {
			yield $this->modifyMetaData($parent . '/' . $data['name'], $data);
		}
	}

	public function file_get_contents(string $path): string|false {
		$encryptionModule = $this->getEncryptionModule($path);

		if ($encryptionModule) {
			$handle = $this->fopen($path, 'r');
			if (!$handle) {
				return false;
			}
			$data = stream_get_contents($handle);
			fclose($handle);
			return $data;
		}
		return $this->storage->file_get_contents($path);
	}

	public function file_put_contents(string $path, mixed $data): int|float|false {
		// file put content will always be translated to a stream write
		$handle = $this->fopen($path, 'w');
		if (is_resource($handle)) {
			$written = fwrite($handle, $data);
			fclose($handle);
			return $written;
		}

		return false;
	}

	public function unlink(string $path): bool {
		$fullPath = $this->getFullPath($path);
		if ($this->util->isExcluded($fullPath)) {
			return $this->storage->unlink($path);
		}

		$encryptionModule = $this->getEncryptionModule($path);
		if ($encryptionModule) {
			$this->keyStorage->deleteAllFileKeys($fullPath);
		}

		return $this->storage->unlink($path);
	}

	public function rename(string $source, string $target): bool {
		$result = $this->storage->rename($source, $target);

		if ($result
			// versions always use the keys from the original file, so we can skip
			// this step for versions
			&& $this->isVersion($target) === false
			&& $this->encryptionManager->isEnabled()) {
			$sourcePath = $this->getFullPath($source);
			if (!$this->util->isExcluded($sourcePath)) {
				$targetPath = $this->getFullPath($target);
				if (isset($this->unencryptedSize[$sourcePath])) {
					$this->unencryptedSize[$targetPath] = $this->unencryptedSize[$sourcePath];
				}
				$this->keyStorage->renameKeys($sourcePath, $targetPath);
				$module = $this->getEncryptionModule($target);
				if ($module) {
					$module->update($targetPath, $this->uid, []);
				}
			}
		}

		return $result;
	}

	public function rmdir(string $path): bool {
		$result = $this->storage->rmdir($path);
		$fullPath = $this->getFullPath($path);
		if ($result
			&& $this->util->isExcluded($fullPath) === false
			&& $this->encryptionManager->isEnabled()
		) {
			$this->keyStorage->deleteAllFileKeys($fullPath);
		}

		return $result;
	}

	public function isReadable(string $path): bool {
		$isReadable = true;

		$metaData = $this->getMetaData($path);
		if (
			!$this->is_dir($path)
			&& isset($metaData['encrypted'])
			&& $metaData['encrypted'] === true
		) {
			$fullPath = $this->getFullPath($path);
			$module = $this->getEncryptionModule($path);
			$isReadable = $module->isReadable($fullPath, $this->uid);
		}

		return $this->storage->isReadable($path) && $isReadable;
	}

	public function copy(string $source, string $target): bool {
		$sourcePath = $this->getFullPath($source);

		if ($this->util->isExcluded($sourcePath)) {
			return $this->storage->copy($source, $target);
		}

		// need to stream copy file by file in case we copy between a encrypted
		// and a unencrypted storage
		$this->unlink($target);
		return $this->copyFromStorage($this, $source, $target);
	}

	public function fopen(string $path, string $mode) {
		// check if the file is stored in the array cache, this means that we
		// copy a file over to the versions folder, in this case we don't want to
		// decrypt it
		if ($this->arrayCache->hasKey('encryption_copy_version_' . $path)) {
			$this->arrayCache->remove('encryption_copy_version_' . $path);
			return $this->storage->fopen($path, $mode);
		}

		if (!$this->enabled) {
			return $this->storage->fopen($path, $mode);
		}

		$encryptionEnabled = $this->encryptionManager->isEnabled();
		$shouldEncrypt = false;
		$encryptionModule = null;
		$header = $this->getHeader($path);
		$signed = isset($header['signed']) && $header['signed'] === 'true';
		$fullPath = $this->getFullPath($path);
		$encryptionModuleId = $this->util->getEncryptionModuleId($header);

		if ($this->util->isExcluded($fullPath) === false) {
			$size = $unencryptedSize = 0;
			$realFile = $this->util->stripPartialFileExtension($path);
			$targetExists = $this->is_file($realFile) || $this->file_exists($path);
			$targetIsEncrypted = false;
			if ($targetExists) {
				// in case the file exists we require the explicit module as
				// specified in the file header - otherwise we need to fail hard to
				// prevent data loss on client side
				if (!empty($encryptionModuleId)) {
					$targetIsEncrypted = true;
					$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
				}

				if ($this->file_exists($path)) {
					$size = $this->storage->filesize($path);
					$unencryptedSize = $this->filesize($path);
				} else {
					$size = $unencryptedSize = 0;
				}
			}

			try {
				if (
					$mode === 'w'
					|| $mode === 'w+'
					|| $mode === 'wb'
					|| $mode === 'wb+'
				) {
					// if we update a encrypted file with a un-encrypted one we change the db flag
					if ($targetIsEncrypted && $encryptionEnabled === false) {
						$cache = $this->storage->getCache();
						$entry = $cache->get($path);
						$cache->update($entry->getId(), ['encrypted' => 0]);
					}
					if ($encryptionEnabled) {
						// if $encryptionModuleId is empty, the default module will be used
						$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
						$shouldEncrypt = $encryptionModule->shouldEncrypt($fullPath);
						$signed = true;
					}
				} else {
					$info = $this->getCache()->get($path);
					// only get encryption module if we found one in the header
					// or if file should be encrypted according to the file cache
					if (!empty($encryptionModuleId)) {
						$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
						$shouldEncrypt = true;
					} elseif ($info !== false && $info['encrypted'] === true) {
						// we come from a old installation. No header and/or no module defined
						// but the file is encrypted. In this case we need to use the
						// OC_DEFAULT_MODULE to read the file
						$encryptionModule = $this->encryptionManager->getEncryptionModule('OC_DEFAULT_MODULE');
						$shouldEncrypt = true;
						$targetIsEncrypted = true;
					}
				}
			} catch (ModuleDoesNotExistsException $e) {
				$this->logger->warning('Encryption module "' . $encryptionModuleId . '" not found, file will be stored unencrypted', [
					'exception' => $e,
					'app' => 'core',
				]);
			}

			// encryption disabled on write of new file and write to existing unencrypted file -> don't encrypt
			if (!$this->shouldEncrypt($path)) {
				if (!$targetExists || !$targetIsEncrypted) {
					$shouldEncrypt = false;
				}
			}

			if ($shouldEncrypt === true && $encryptionModule !== null) {
				$this->encryptedPaths->set($this->util->stripPartialFileExtension($path), true);
				$headerSize = $this->getHeaderSize($path);
				if ($mode === 'r' && $headerSize === 0) {
					$firstBlock = $this->readFirstBlock($path);
					if (!$firstBlock) {
						throw new InvalidHeaderException("Unable to get header block for $path");
					} elseif (!str_starts_with($firstBlock, Util::HEADER_START)) {
						throw new InvalidHeaderException("Unable to get header size for $path, file doesn't start with encryption header");
					} else {
						throw new InvalidHeaderException("Unable to get header size for $path, even though file does start with encryption header");
					}
				}
				$source = $this->storage->fopen($path, $mode);
				if (!is_resource($source)) {
					return false;
				}
				$handle = \OC\Files\Stream\Encryption::wrap($source, $path, $fullPath, $header,
					$this->uid, $encryptionModule, $this->storage, $this, $this->util, $this->fileHelper, $mode,
					$size, $unencryptedSize, $headerSize, $signed);

				return $handle;
			}
		}

		return $this->storage->fopen($path, $mode);
	}


	/**
	 * perform some plausibility checks if the unencrypted size is correct.
	 * If not, we calculate the correct unencrypted size and return it
	 *
	 * @param string $path internal path relative to the storage root
	 * @param int $unencryptedSize size of the unencrypted file
	 *
	 * @return int unencrypted size
	 */
	protected function verifyUnencryptedSize(string $path, int $unencryptedSize): int {
		$size = $this->storage->filesize($path);
		$result = $unencryptedSize;

		if ($unencryptedSize < 0
			|| ($size > 0 && $unencryptedSize === $size)
			|| $unencryptedSize > $size
		) {
			// check if we already calculate the unencrypted size for the
			// given path to avoid recursions
			if (isset($this->fixUnencryptedSizeOf[$this->getFullPath($path)]) === false) {
				$this->fixUnencryptedSizeOf[$this->getFullPath($path)] = true;
				try {
					$result = $this->fixUnencryptedSize($path, $size, $unencryptedSize);
				} catch (\Exception $e) {
					$this->logger->error('Couldn\'t re-calculate unencrypted size for ' . $path, ['exception' => $e]);
				}
				unset($this->fixUnencryptedSizeOf[$this->getFullPath($path)]);
			}
		}

		return $result;
	}

	/**
	 * calculate the unencrypted size
	 *
	 * @param string $path internal path relative to the storage root
	 * @param int $size size of the physical file
	 * @param int $unencryptedSize size of the unencrypted file
	 */
	protected function fixUnencryptedSize(string $path, int $size, int $unencryptedSize): int|float {
		$headerSize = $this->getHeaderSize($path);
		$header = $this->getHeader($path);
		$encryptionModule = $this->getEncryptionModule($path);

		$stream = $this->storage->fopen($path, 'r');

		// if we couldn't open the file we return the old unencrypted size
		if (!is_resource($stream)) {
			$this->logger->error('Could not open ' . $path . '. Recalculation of unencrypted size aborted.');
			return $unencryptedSize;
		}

		$newUnencryptedSize = 0;
		$size -= $headerSize;
		$blockSize = $this->util->getBlockSize();

		// if a header exists we skip it
		if ($headerSize > 0) {
			$this->fread_block($stream, $headerSize);
		}

		// fast path, else the calculation for $lastChunkNr is bogus
		if ($size === 0) {
			return 0;
		}

		$signed = isset($header['signed']) && $header['signed'] === 'true';
		$unencryptedBlockSize = $encryptionModule->getUnencryptedBlockSize($signed);

		// calculate last chunk nr
		// next highest is end of chunks, one subtracted is last one
		// we have to read the last chunk, we can't just calculate it (because of padding etc)

		$lastChunkNr = ceil($size / $blockSize) - 1;
		// calculate last chunk position
		$lastChunkPos = ($lastChunkNr * $blockSize);
		// try to fseek to the last chunk, if it fails we have to read the whole file
		if (@fseek($stream, $lastChunkPos, SEEK_CUR) === 0) {
			$newUnencryptedSize += $lastChunkNr * $unencryptedBlockSize;
		}

		$lastChunkContentEncrypted = '';
		$count = $blockSize;

		while ($count > 0) {
			$data = $this->fread_block($stream, $blockSize);
			$count = strlen($data);
			$lastChunkContentEncrypted .= $data;
			if (strlen($lastChunkContentEncrypted) > $blockSize) {
				$newUnencryptedSize += $unencryptedBlockSize;
				$lastChunkContentEncrypted = substr($lastChunkContentEncrypted, $blockSize);
				$lastChunkNr++;
			}
		}

		fclose($stream);

		// we have to decrypt the last chunk to get it actual size
		$encryptionModule->begin($this->getFullPath($path), $this->uid, 'r', $header, []);
		$decryptedLastChunk = $encryptionModule->decrypt($lastChunkContentEncrypted, $lastChunkNr . 'end');
		$decryptedLastChunk .= $encryptionModule->end($this->getFullPath($path), $lastChunkNr . 'end');

		// calc the real file size with the size of the last chunk
		$newUnencryptedSize += strlen($decryptedLastChunk);

		$this->updateUnencryptedSize($this->getFullPath($path), $newUnencryptedSize);

		// write to cache if applicable
		$cache = $this->storage->getCache();
		$entry = $cache->get($path);
		$cache->update($entry['fileid'], [
			'unencrypted_size' => $newUnencryptedSize
		]);

		return $newUnencryptedSize;
	}

	/**
	 * fread_block
	 *
	 * This function is a wrapper around the fread function.  It is based on the
	 * stream_read_block function from lib/private/Files/Streams/Encryption.php
	 * It calls stream read until the requested $blockSize was received or no remaining data is present.
	 * This is required as stream_read only returns smaller chunks of data when the stream fetches from a
	 * remote storage over the internet and it does not care about the given $blockSize.
	 *
	 * @param resource $handle the stream to read from
	 * @param int $blockSize Length of requested data block in bytes
	 * @return string Data fetched from stream.
	 */
	private function fread_block($handle, int $blockSize): string {
		$remaining = $blockSize;
		$data = '';

		do {
			$chunk = fread($handle, $remaining);
			$chunk_len = strlen($chunk);
			$data .= $chunk;
			$remaining -= $chunk_len;
		} while (($remaining > 0) && ($chunk_len > 0));

		return $data;
	}

	public function moveFromStorage(
		Storage\IStorage $sourceStorage,
		string $sourceInternalPath,
		string $targetInternalPath,
		$preserveMtime = true,
	): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		// TODO clean this up once the underlying moveFromStorage in OC\Files\Storage\Wrapper\Common is fixed:
		// - call $this->storage->moveFromStorage() instead of $this->copyBetweenStorage
		// - copy the file cache update from  $this->copyBetweenStorage to this method
		// - copy the copyKeys() call from  $this->copyBetweenStorage to this method
		// - remove $this->copyBetweenStorage

		if (!$sourceStorage->isDeletable($sourceInternalPath)) {
			return false;
		}

		$result = $this->copyBetweenStorage($sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime, true);
		if ($result) {
			$setPreserveCacheOnDelete = $sourceStorage->instanceOfStorage(ObjectStoreStorage::class) && !$this->instanceOfStorage(ObjectStoreStorage::class);
			if ($setPreserveCacheOnDelete) {
				/** @var ObjectStoreStorage $sourceStorage */
				$sourceStorage->setPreserveCacheOnDelete(true);
			}
			try {
				if ($sourceStorage->is_dir($sourceInternalPath)) {
					$result = $sourceStorage->rmdir($sourceInternalPath);
				} else {
					$result = $sourceStorage->unlink($sourceInternalPath);
				}
			} finally {
				if ($setPreserveCacheOnDelete) {
					/** @var ObjectStoreStorage $sourceStorage */
					$sourceStorage->setPreserveCacheOnDelete(false);
				}
			}
		}
		return $result;
	}

	public function copyFromStorage(
		Storage\IStorage $sourceStorage,
		string $sourceInternalPath,
		string $targetInternalPath,
		$preserveMtime = false,
		$isRename = false,
	): bool {
		// TODO clean this up once the underlying moveFromStorage in OC\Files\Storage\Wrapper\Common is fixed:
		// - call $this->storage->copyFromStorage() instead of $this->copyBetweenStorage
		// - copy the file cache update from  $this->copyBetweenStorage to this method
		// - copy the copyKeys() call from  $this->copyBetweenStorage to this method
		// - remove $this->copyBetweenStorage

		return $this->copyBetweenStorage($sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime, $isRename);
	}

	/**
	 * Update the encrypted cache version in the database
	 */
	private function updateEncryptedVersion(
		Storage\IStorage $sourceStorage,
		string $sourceInternalPath,
		string $targetInternalPath,
		bool $isRename,
		bool $keepEncryptionVersion,
	): void {
		$isEncrypted = $this->shouldEncrypt($targetInternalPath);
		$cacheInformation = [
			'encrypted' => $isEncrypted,
		];
		if ($isEncrypted) {
			$sourceCacheEntry = $sourceStorage->getCache()->get($sourceInternalPath);
			$targetCacheEntry = $this->getCache()->get($targetInternalPath);

			// Rename of the cache already happened, so we do the cleanup on the target
			if ($sourceCacheEntry === false && $targetCacheEntry !== false) {
				$encryptedVersion = $targetCacheEntry['encryptedVersion'];
				$isRename = false;
			} else {
				$encryptedVersion = $sourceCacheEntry['encryptedVersion'];
			}

			// In case of a move operation from an unencrypted to an encrypted
			// storage the old encrypted version would stay with "0" while the
			// correct value would be "1". Thus we manually set the value to "1"
			// for those cases.
			// See also https://github.com/owncloud/core/issues/23078
			if ($encryptedVersion === 0 || !$keepEncryptionVersion) {
				$encryptedVersion = 1;
			}

			$cacheInformation['encryptedVersion'] = $encryptedVersion;
		}

		// in case of a rename we need to manipulate the source cache because
		// this information will be kept for the new target
		if ($isRename) {
			$sourceStorage->getCache()->put($sourceInternalPath, $cacheInformation);
		} else {
			$this->getCache()->put($targetInternalPath, $cacheInformation);
		}
	}

	/**
	 * copy file between two storages
	 * @throws \Exception
	 */
	private function copyBetweenStorage(
		Storage\IStorage $sourceStorage,
		string $sourceInternalPath,
		string $targetInternalPath,
		bool $preserveMtime,
		bool $isRename,
	): bool {
		// for versions we have nothing to do, because versions should always use the
		// key from the original file. Just create a 1:1 copy and done
		if ($this->isVersion($targetInternalPath)
			|| $this->isVersion($sourceInternalPath)) {
			// remember that we try to create a version so that we can detect it during
			// fopen($sourceInternalPath) and by-pass the encryption in order to
			// create a 1:1 copy of the file
			$this->arrayCache->set('encryption_copy_version_' . $sourceInternalPath, true);
			$result = $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
			$this->arrayCache->remove('encryption_copy_version_' . $sourceInternalPath);
			if ($result) {
				$info = $this->getCache('', $sourceStorage)->get($sourceInternalPath);
				// make sure that we update the unencrypted size for the version
				if (isset($info['encrypted']) && $info['encrypted'] === true) {
					$this->updateUnencryptedSize(
						$this->getFullPath($targetInternalPath),
						$info->getUnencryptedSize()
					);
				}
				$this->updateEncryptedVersion($sourceStorage, $sourceInternalPath, $targetInternalPath, $isRename, true);
			}
			return $result;
		}

		// first copy the keys that we reuse the existing file key on the target location
		// and don't create a new one which would break versions for example.
		if ($sourceStorage->instanceOfStorage(Common::class) && $sourceStorage->getMountOption('mount_point')) {
			$mountPoint = $sourceStorage->getMountOption('mount_point');
			$source = $mountPoint . '/' . $sourceInternalPath;
			$target = $this->getFullPath($targetInternalPath);
			$this->copyKeys($source, $target);
		} else {
			$this->logger->error('Could not find mount point, can\'t keep encryption keys');
		}

		if ($sourceStorage->is_dir($sourceInternalPath)) {
			$dh = $sourceStorage->opendir($sourceInternalPath);
			if (!$this->is_dir($targetInternalPath)) {
				$result = $this->mkdir($targetInternalPath);
			} else {
				$result = true;
			}
			if (is_resource($dh)) {
				while ($result && ($file = readdir($dh)) !== false) {
					if (!Filesystem::isIgnoredDir($file)) {
						$result = $this->copyFromStorage($sourceStorage, $sourceInternalPath . '/' . $file, $targetInternalPath . '/' . $file, $preserveMtime, $isRename);
					}
				}
			}
		} else {
			$source = false;
			$target = false;
			try {
				$source = $sourceStorage->fopen($sourceInternalPath, 'r');
				$target = $this->fopen($targetInternalPath, 'w');
				if ($source === false || $target === false) {
					$result = false;
				} else {
					[, $result] = Files::streamCopy($source, $target, true);
				}
			} finally {
				if ($source !== false) {
					fclose($source);
				}
				if ($target !== false) {
					fclose($target);
				}
			}
			if ($result) {
				if ($preserveMtime) {
					$this->touch($targetInternalPath, $sourceStorage->filemtime($sourceInternalPath));
				}
				$this->updateEncryptedVersion($sourceStorage, $sourceInternalPath, $targetInternalPath, $isRename, false);
			} else {
				// delete partially written target file
				$this->unlink($targetInternalPath);
				// delete cache entry that was created by fopen
				$this->getCache()->remove($targetInternalPath);
			}
		}
		return (bool)$result;
	}

	public function getLocalFile(string $path): string|false {
		if ($this->encryptionManager->isEnabled()) {
			$cachedFile = $this->getCachedFile($path);
			if (is_string($cachedFile)) {
				return $cachedFile;
			}
		}
		return $this->storage->getLocalFile($path);
	}

	public function isLocal(): bool {
		if ($this->encryptionManager->isEnabled()) {
			return false;
		}
		return $this->storage->isLocal();
	}

	public function stat(string $path): array|false {
		$stat = $this->storage->stat($path);
		if (!$stat) {
			return false;
		}
		$fileSize = $this->filesize($path);
		$stat['size'] = $fileSize;
		$stat[7] = $fileSize;
		$stat['hasHeader'] = $this->getHeaderSize($path) > 0;
		return $stat;
	}

	public function hash(string $type, string $path, bool $raw = false): string|false {
		$fh = $this->fopen($path, 'rb');
		if ($fh === false) {
			return false;
		}
		$ctx = hash_init($type);
		hash_update_stream($ctx, $fh);
		fclose($fh);
		return hash_final($ctx, $raw);
	}

	/**
	 * return full path, including mount point
	 *
	 * @param string $path relative to mount point
	 * @return string full path including mount point
	 */
	protected function getFullPath(string $path): string {
		return Filesystem::normalizePath($this->mountPoint . '/' . $path);
	}

	/**
	 * read first block of encrypted file, typically this will contain the
	 * encryption header
	 */
	protected function readFirstBlock(string $path): string {
		$firstBlock = '';
		if ($this->storage->is_file($path)) {
			$handle = $this->storage->fopen($path, 'r');
			if ($handle === false) {
				return '';
			}
			$firstBlock = fread($handle, $this->util->getHeaderSize());
			fclose($handle);
		}
		return $firstBlock;
	}

	/**
	 * return header size of given file
	 */
	protected function getHeaderSize(string $path): int {
		$headerSize = 0;
		$realFile = $this->util->stripPartialFileExtension($path);
		if ($this->storage->is_file($realFile)) {
			$path = $realFile;
		}
		$firstBlock = $this->readFirstBlock($path);

		if (str_starts_with($firstBlock, Util::HEADER_START)) {
			$headerSize = $this->util->getHeaderSize();
		}

		return $headerSize;
	}

	/**
	 * read header from file
	 */
	protected function getHeader(string $path): array {
		$realFile = $this->util->stripPartialFileExtension($path);
		$exists = $this->storage->is_file($realFile);
		if ($exists) {
			$path = $realFile;
		}

		$result = [];

		$isEncrypted = $this->encryptedPaths->get($realFile);
		if (is_null($isEncrypted)) {
			$info = $this->getCache()->get($path);
			$isEncrypted = isset($info['encrypted']) && $info['encrypted'] === true;
		}

		if ($isEncrypted) {
			$firstBlock = $this->readFirstBlock($path);
			$result = $this->util->parseRawHeader($firstBlock);

			// if the header doesn't contain a encryption module we check if it is a
			// legacy file. If true, we add the default encryption module
			if (!isset($result[Util::HEADER_ENCRYPTION_MODULE_KEY]) && (!empty($result) || $exists)) {
				$result[Util::HEADER_ENCRYPTION_MODULE_KEY] = 'OC_DEFAULT_MODULE';
			}
		}

		return $result;
	}

	/**
	 * read encryption module needed to read/write the file located at $path
	 *
	 * @throws ModuleDoesNotExistsException
	 * @throws \Exception
	 */
	protected function getEncryptionModule(string $path): ?\OCP\Encryption\IEncryptionModule {
		$encryptionModule = null;
		$header = $this->getHeader($path);
		$encryptionModuleId = $this->util->getEncryptionModuleId($header);
		if (!empty($encryptionModuleId)) {
			try {
				$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
			} catch (ModuleDoesNotExistsException $e) {
				$this->logger->critical('Encryption module defined in "' . $path . '" not loaded!');
				throw $e;
			}
		}

		return $encryptionModule;
	}

	public function updateUnencryptedSize(string $path, int|float $unencryptedSize): void {
		$this->unencryptedSize[$path] = $unencryptedSize;
	}

	/**
	 * copy keys to new location
	 *
	 * @param string $source path relative to data/
	 * @param string $target path relative to data/
	 */
	protected function copyKeys(string $source, string $target): bool {
		if (!$this->util->isExcluded($source)) {
			return $this->keyStorage->copyKeys($source, $target);
		}

		return false;
	}

	/**
	 * check if path points to a files version
	 */
	protected function isVersion(string $path): bool {
		$normalized = Filesystem::normalizePath($path);
		return substr($normalized, 0, strlen('/files_versions/')) === '/files_versions/';
	}

	/**
	 * check if the given storage should be encrypted or not
	 */
	public function shouldEncrypt(string $path): bool {
		if (!$this->encryptionManager->isEnabled()) {
			return false;
		}
		$fullPath = $this->getFullPath($path);
		$mountPointConfig = $this->mount->getOption('encrypt', true);
		if ($mountPointConfig === false) {
			return false;
		}

		try {
			$encryptionModule = $this->getEncryptionModule($fullPath);
		} catch (ModuleDoesNotExistsException $e) {
			return false;
		}

		if ($encryptionModule === null) {
			$encryptionModule = $this->encryptionManager->getEncryptionModule();
		}

		return $encryptionModule->shouldEncrypt($fullPath);
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		// always fall back to fopen
		$target = $this->fopen($path, 'w');
		if ($target === false) {
			throw new GenericFileException("Failed to open $path for writing");
		}
		[$count, $result] = Files::streamCopy($stream, $target, true);
		fclose($stream);
		fclose($target);

		// object store, stores the size after write and doesn't update this during scan
		// manually store the unencrypted size
		if ($result && $this->getWrapperStorage()->instanceOfStorage(ObjectStoreStorage::class) && $this->shouldEncrypt($path)) {
			$this->getCache()->put($path, ['unencrypted_size' => $count]);
		}

		return $count;
	}

	public function clearIsEncryptedCache(): void {
		$this->encryptedPaths->clear();
	}

	/**
	 * Allow temporarily disabling the wrapper
	 */
	public function setEnabled(bool $enabled): void {
		$this->enabled = $enabled;
	}

	/**
	 * Check if the on-disk data for a file has a valid encrypted header
	 *
	 * @param string $path
	 * @return bool
	 */
	public function hasValidHeader(string $path): bool {
		$firstBlock = $this->readFirstBlock($path);
		$header = $this->util->parseRawHeader($firstBlock);
		return (count($header) > 0);
	}
}
