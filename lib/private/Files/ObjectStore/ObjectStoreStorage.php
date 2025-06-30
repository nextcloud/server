<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\S3MultipartUploadException;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\CountWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheEntry;
use OC\Files\Storage\PolyFill\CopyDirectory;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\IScanner;
use OCP\Files\FileInfo;
use OCP\Files\GenericFileException;
use OCP\Files\NotFoundException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\Files\ObjectStore\IObjectStoreMultiPartUpload;
use OCP\Files\Storage\IChunkedFileWrite;
use OCP\Files\Storage\IStorage;
use Psr\Log\LoggerInterface;

class ObjectStoreStorage extends \OC\Files\Storage\Common implements IChunkedFileWrite {
	use CopyDirectory;

	protected IObjectStore $objectStore;
	protected string $id;
	private string $objectPrefix = 'urn:oid:';

	private LoggerInterface $logger;

	private bool $handleCopiesAsOwned;
	protected bool $validateWrites = true;
	private bool $preserveCacheItemsOnDelete = false;

	/**
	 * @param array $parameters
	 * @throws \Exception
	 */
	public function __construct(array $parameters) {
		if (isset($parameters['objectstore']) && $parameters['objectstore'] instanceof IObjectStore) {
			$this->objectStore = $parameters['objectstore'];
		} else {
			throw new \Exception('missing IObjectStore instance');
		}
		if (isset($parameters['storageid'])) {
			$this->id = 'object::store:' . $parameters['storageid'];
		} else {
			$this->id = 'object::store:' . $this->objectStore->getStorageId();
		}
		if (isset($parameters['objectPrefix'])) {
			$this->objectPrefix = $parameters['objectPrefix'];
		}
		if (isset($parameters['validateWrites'])) {
			$this->validateWrites = (bool)$parameters['validateWrites'];
		}
		$this->handleCopiesAsOwned = (bool)($parameters['handleCopiesAsOwned'] ?? false);

		$this->logger = \OCP\Server::get(LoggerInterface::class);
	}

	public function mkdir(string $path, bool $force = false, array $metadata = []): bool {
		$path = $this->normalizePath($path);
		if (!$force && $this->file_exists($path)) {
			$this->logger->warning("Tried to create an object store folder that already exists: $path");
			return false;
		}

		$mTime = time();
		$data = [
			'mimetype' => 'httpd/unix-directory',
			'size' => $metadata['size'] ?? 0,
			'mtime' => $mTime,
			'storage_mtime' => $mTime,
			'permissions' => \OCP\Constants::PERMISSION_ALL,
		];
		if ($path === '') {
			//create root on the fly
			$data['etag'] = $this->getETag('');
			$this->getCache()->put('', $data);
			return true;
		} else {
			// if parent does not exist, create it
			$parent = $this->normalizePath(dirname($path));
			$parentType = $this->filetype($parent);
			if ($parentType === false) {
				if (!$this->mkdir($parent)) {
					// something went wrong
					$this->logger->warning("Parent folder ($parent) doesn't exist and couldn't be created");
					return false;
				}
			} elseif ($parentType === 'file') {
				// parent is a file
				$this->logger->warning("Parent ($parent) is a file");
				return false;
			}
			// finally create the new dir
			$mTime = time(); // update mtime
			$data['mtime'] = $mTime;
			$data['storage_mtime'] = $mTime;
			$data['etag'] = $this->getETag($path);
			$this->getCache()->put($path, $data);
			return true;
		}
	}

	private function normalizePath(string $path): string {
		$path = trim($path, '/');
		//FIXME why do we sometimes get a path like 'files//username'?
		$path = str_replace('//', '/', $path);

		// dirname('/folder') returns '.' but internally (in the cache) we store the root as ''
		if (!$path || $path === '.') {
			$path = '';
		}

		return $path;
	}

	/**
	 * Object Stores use a NoopScanner because metadata is directly stored in
	 * the file cache and cannot really scan the filesystem. The storage passed in is not used anywhere.
	 */
	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->scanner)) {
			$this->scanner = new ObjectStoreScanner($storage);
		}
		/** @var \OC\Files\ObjectStore\ObjectStoreScanner */
		return $this->scanner;
	}

	public function getId(): string {
		return $this->id;
	}

	public function rmdir(string $path): bool {
		$path = $this->normalizePath($path);
		$entry = $this->getCache()->get($path);

		if (!$entry || $entry->getMimeType() !== ICacheEntry::DIRECTORY_MIMETYPE) {
			return false;
		}

		return $this->rmObjects($entry);
	}

	private function rmObjects(ICacheEntry $entry): bool {
		$children = $this->getCache()->getFolderContentsById($entry->getId());
		foreach ($children as $child) {
			if ($child->getMimeType() === ICacheEntry::DIRECTORY_MIMETYPE) {
				if (!$this->rmObjects($child)) {
					return false;
				}
			} else {
				if (!$this->rmObject($child)) {
					return false;
				}
			}
		}

		if (!$this->preserveCacheItemsOnDelete) {
			$this->getCache()->remove($entry->getPath());
		}

		return true;
	}

	public function unlink(string $path): bool {
		$path = $this->normalizePath($path);
		$entry = $this->getCache()->get($path);

		if ($entry instanceof ICacheEntry) {
			if ($entry->getMimeType() === ICacheEntry::DIRECTORY_MIMETYPE) {
				return $this->rmObjects($entry);
			} else {
				return $this->rmObject($entry);
			}
		}
		return false;
	}

	public function rmObject(ICacheEntry $entry): bool {
		try {
			$this->objectStore->deleteObject($this->getURN($entry->getId()));
		} catch (\Exception $ex) {
			if ($ex->getCode() !== 404) {
				$this->logger->error(
					'Could not delete object ' . $this->getURN($entry->getId()) . ' for ' . $entry->getPath(),
					[
						'app' => 'objectstore',
						'exception' => $ex,
					]
				);
				return false;
			}
			//removing from cache is ok as it does not exist in the objectstore anyway
		}
		if (!$this->preserveCacheItemsOnDelete) {
			$this->getCache()->remove($entry->getPath());
		}
		return true;
	}

	public function stat(string $path): array|false {
		$path = $this->normalizePath($path);
		$cacheEntry = $this->getCache()->get($path);
		if ($cacheEntry instanceof CacheEntry) {
			return $cacheEntry->getData();
		} else {
			if ($path === '') {
				$this->mkdir('', true);
				$cacheEntry = $this->getCache()->get($path);
				if ($cacheEntry instanceof CacheEntry) {
					return $cacheEntry->getData();
				}
			}
			return false;
		}
	}

	public function getPermissions(string $path): int {
		$stat = $this->stat($path);

		if (is_array($stat) && isset($stat['permissions'])) {
			return $stat['permissions'];
		}

		return parent::getPermissions($path);
	}

	/**
	 * Override this method if you need a different unique resource identifier for your object storage implementation.
	 * The default implementations just appends the fileId to 'urn:oid:'. Make sure the URN is unique over all users.
	 * You may need a mapping table to store your URN if it cannot be generated from the fileid.
	 *
	 * @return string the unified resource name used to identify the object
	 */
	public function getURN(int $fileId): string {
		return $this->objectPrefix . $fileId;
	}

	public function opendir(string $path) {
		$path = $this->normalizePath($path);

		try {
			$files = [];
			$folderContents = $this->getCache()->getFolderContents($path);
			foreach ($folderContents as $file) {
				$files[] = $file['name'];
			}

			return IteratorDirectory::wrap($files);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return false;
		}
	}

	public function filetype(string $path): string|false {
		$path = $this->normalizePath($path);
		$stat = $this->stat($path);
		if ($stat) {
			if ($stat['mimetype'] === 'httpd/unix-directory') {
				return 'dir';
			}
			return 'file';
		} else {
			return false;
		}
	}

	public function fopen(string $path, string $mode) {
		$path = $this->normalizePath($path);

		if (strrpos($path, '.') !== false) {
			$ext = substr($path, strrpos($path, '.'));
		} else {
			$ext = '';
		}

		switch ($mode) {
			case 'r':
			case 'rb':
				$stat = $this->stat($path);
				if (is_array($stat)) {
					$filesize = $stat['size'] ?? 0;
					// Reading 0 sized files is a waste of time
					if ($filesize === 0) {
						return fopen('php://memory', $mode);
					}

					try {
						$handle = $this->objectStore->readObject($this->getURN($stat['fileid']));
						if ($handle === false) {
							return false; // keep backward compatibility
						}
						$streamStat = fstat($handle);
						$actualSize = $streamStat['size'] ?? -1;
						if ($actualSize > -1 && $actualSize !== $filesize) {
							$this->getCache()->update((int)$stat['fileid'], ['size' => $actualSize]);
						}
						return $handle;
					} catch (NotFoundException $e) {
						$this->logger->error(
							'Could not get object ' . $this->getURN($stat['fileid']) . ' for file ' . $path,
							[
								'app' => 'objectstore',
								'exception' => $e,
							]
						);
						throw $e;
					} catch (\Exception $e) {
						$this->logger->error(
							'Could not get object ' . $this->getURN($stat['fileid']) . ' for file ' . $path,
							[
								'app' => 'objectstore',
								'exception' => $e,
							]
						);
						return false;
					}
				} else {
					return false;
				}
				// no break
			case 'w':
			case 'wb':
			case 'w+':
			case 'wb+':
				$dirName = dirname($path);
				$parentExists = $this->is_dir($dirName);
				if (!$parentExists) {
					return false;
				}

				$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
				$handle = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile) {
					$this->writeBack($tmpFile, $path);
					unlink($tmpFile);
				});
			case 'a':
			case 'ab':
			case 'r+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				$handle = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile) {
					$this->writeBack($tmpFile, $path);
					unlink($tmpFile);
				});
		}
		return false;
	}

	public function file_exists(string $path): bool {
		$path = $this->normalizePath($path);
		return (bool)$this->stat($path);
	}

	public function rename(string $source, string $target): bool {
		$source = $this->normalizePath($source);
		$target = $this->normalizePath($target);
		$this->remove($target);
		$this->getCache()->move($source, $target);
		$this->touch(dirname($target));
		return true;
	}

	public function getMimeType(string $path): string|false {
		$path = $this->normalizePath($path);
		return parent::getMimeType($path);
	}

	public function touch(string $path, ?int $mtime = null): bool {
		if (is_null($mtime)) {
			$mtime = time();
		}

		$path = $this->normalizePath($path);
		$dirName = dirname($path);
		$parentExists = $this->is_dir($dirName);
		if (!$parentExists) {
			return false;
		}

		$stat = $this->stat($path);
		if (is_array($stat)) {
			// update existing mtime in db
			$stat['mtime'] = $mtime;
			$this->getCache()->update($stat['fileid'], $stat);
		} else {
			try {
				//create a empty file, need to have at least on char to make it
				// work with all object storage implementations
				$this->file_put_contents($path, ' ');
			} catch (\Exception $ex) {
				$this->logger->error(
					'Could not create object for ' . $path,
					[
						'app' => 'objectstore',
						'exception' => $ex,
					]
				);
				throw $ex;
			}
		}
		return true;
	}

	public function writeBack(string $tmpFile, string $path) {
		$size = filesize($tmpFile);
		$this->writeStream($path, fopen($tmpFile, 'r'), $size);
	}

	public function hasUpdated(string $path, int $time): bool {
		return false;
	}

	public function needsPartFile(): bool {
		return false;
	}

	public function file_put_contents(string $path, mixed $data): int {
		$fh = fopen('php://temp', 'w+');
		fwrite($fh, $data);
		rewind($fh);
		return $this->writeStream($path, $fh, strlen($data));
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		if ($size === null) {
			$stats = fstat($stream);
			if (is_array($stats) && isset($stats['size'])) {
				$size = $stats['size'];
			}
		}

		$stat = $this->stat($path);
		if (empty($stat)) {
			// create new file
			$stat = [
				'permissions' => \OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_CREATE,
			];
		}
		// update stat with new data
		$mTime = time();
		$stat['size'] = (int)$size;
		$stat['mtime'] = $mTime;
		$stat['storage_mtime'] = $mTime;

		$mimetypeDetector = \OC::$server->getMimeTypeDetector();
		$mimetype = $mimetypeDetector->detectPath($path);
		$metadata = [
			'mimetype' => $mimetype,
			'original-storage' => $this->getId(),
			'original-path' => $path,
		];

		$stat['mimetype'] = $mimetype;
		$stat['etag'] = $this->getETag($path);
		$stat['checksum'] = '';

		$exists = $this->getCache()->inCache($path);
		$uploadPath = $exists ? $path : $path . '.part';

		if ($exists) {
			$fileId = $stat['fileid'];
		} else {
			$parent = $this->normalizePath(dirname($path));
			if (!$this->is_dir($parent)) {
				throw new \InvalidArgumentException("trying to upload a file ($path) inside a non-directory ($parent)");
			}
			$fileId = $this->getCache()->put($uploadPath, $stat);
		}

		$urn = $this->getURN($fileId);
		try {
			//upload to object storage
			if ($size === null) {
				$countStream = CountWrapper::wrap($stream, function ($writtenSize) use ($fileId, &$size) {
					$this->getCache()->update($fileId, [
						'size' => $writtenSize,
					]);
					$size = $writtenSize;
				});
				if ($this->objectStore instanceof IObjectStoreMetaData) {
					$this->objectStore->writeObjectWithMetaData($urn, $countStream, $metadata);
				} else {
					$this->objectStore->writeObject($urn, $countStream, $metadata['mimetype']);
				}
				if (is_resource($countStream)) {
					fclose($countStream);
				}
				$stat['size'] = $size;
			} else {
				if ($this->objectStore instanceof IObjectStoreMetaData) {
					$this->objectStore->writeObjectWithMetaData($urn, $stream, $metadata);
				} else {
					$this->objectStore->writeObject($urn, $stream, $metadata['mimetype']);
				}
				if (is_resource($stream)) {
					fclose($stream);
				}
			}
		} catch (\Exception $ex) {
			if (!$exists) {
				/*
				 * Only remove the entry if we are dealing with a new file.
				 * Else people lose access to existing files
				 */
				$this->getCache()->remove($uploadPath);
				$this->logger->error(
					'Could not create object ' . $urn . ' for ' . $path,
					[
						'app' => 'objectstore',
						'exception' => $ex,
					]
				);
			} else {
				$this->logger->error(
					'Could not update object ' . $urn . ' for ' . $path,
					[
						'app' => 'objectstore',
						'exception' => $ex,
					]
				);
			}
			throw $ex; // make this bubble up
		}

		if ($exists) {
			// Always update the unencrypted size, for encryption the Encryption wrapper will update this afterwards anyways
			$stat['unencrypted_size'] = $stat['size'];
			$this->getCache()->update($fileId, $stat);
		} else {
			if (!$this->validateWrites || $this->objectStore->objectExists($urn)) {
				$this->getCache()->move($uploadPath, $path);
			} else {
				$this->getCache()->remove($uploadPath);
				throw new \Exception("Object not found after writing (urn: $urn, path: $path)", 404);
			}
		}

		return $size;
	}

	public function getObjectStore(): IObjectStore {
		return $this->objectStore;
	}

	public function copyFromStorage(
		IStorage $sourceStorage,
		string $sourceInternalPath,
		string $targetInternalPath,
		bool $preserveMtime = false,
	): bool {
		if ($sourceStorage->instanceOfStorage(ObjectStoreStorage::class)) {
			/** @var ObjectStoreStorage $sourceStorage */
			if ($sourceStorage->getObjectStore()->getStorageId() === $this->getObjectStore()->getStorageId()) {
				/** @var CacheEntry $sourceEntry */
				$sourceEntry = $sourceStorage->getCache()->get($sourceInternalPath);
				$sourceEntryData = $sourceEntry->getData();
				// $sourceEntry['permissions'] here is the permissions from the jailed storage for the current
				// user. Instead we use $sourceEntryData['scan_permissions'] that are the permissions from the
				// unjailed storage.
				if (is_array($sourceEntryData) && array_key_exists('scan_permissions', $sourceEntryData)) {
					$sourceEntry['permissions'] = $sourceEntryData['scan_permissions'];
				}
				$this->copyInner($sourceStorage->getCache(), $sourceEntry, $targetInternalPath);
				return true;
			}
		}

		return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, ?ICacheEntry $sourceCacheEntry = null): bool {
		$sourceCache = $sourceStorage->getCache();
		if (
			$sourceStorage->instanceOfStorage(ObjectStoreStorage::class) &&
			$sourceStorage->getObjectStore()->getStorageId() === $this->getObjectStore()->getStorageId()
		) {
			if ($this->getCache()->get($targetInternalPath)) {
				$this->unlink($targetInternalPath);
				$this->getCache()->remove($targetInternalPath);
			}
			$this->getCache()->moveFromCache($sourceCache, $sourceInternalPath, $targetInternalPath);
			// Do not import any data when source and target bucket are identical.
			return true;
		}
		if (!$sourceCacheEntry) {
			$sourceCacheEntry = $sourceCache->get($sourceInternalPath);
		}

		$this->copyObjects($sourceStorage, $sourceCache, $sourceCacheEntry);
		if ($sourceStorage->instanceOfStorage(ObjectStoreStorage::class)) {
			/** @var ObjectStoreStorage $sourceStorage */
			$sourceStorage->setPreserveCacheOnDelete(true);
		}
		if ($sourceCacheEntry->getMimeType() === ICacheEntry::DIRECTORY_MIMETYPE) {
			$sourceStorage->rmdir($sourceInternalPath);
		} else {
			$sourceStorage->unlink($sourceInternalPath);
		}
		if ($sourceStorage->instanceOfStorage(ObjectStoreStorage::class)) {
			/** @var ObjectStoreStorage $sourceStorage */
			$sourceStorage->setPreserveCacheOnDelete(false);
		}
		if ($this->getCache()->get($targetInternalPath)) {
			$this->unlink($targetInternalPath);
			$this->getCache()->remove($targetInternalPath);
		}
		$this->getCache()->moveFromCache($sourceCache, $sourceInternalPath, $targetInternalPath);

		return true;
	}

	/**
	 * Copy the object(s) of a file or folder into this storage, without touching the cache
	 */
	private function copyObjects(IStorage $sourceStorage, ICache $sourceCache, ICacheEntry $sourceCacheEntry) {
		$copiedFiles = [];
		try {
			foreach ($this->getAllChildObjects($sourceCache, $sourceCacheEntry) as $file) {
				$sourceStream = $sourceStorage->fopen($file->getPath(), 'r');
				if (!$sourceStream) {
					throw new \Exception("Failed to open source file {$file->getPath()} ({$file->getId()})");
				}
				$this->objectStore->writeObject($this->getURN($file->getId()), $sourceStream, $file->getMimeType());
				if (is_resource($sourceStream)) {
					fclose($sourceStream);
				}
				$copiedFiles[] = $file->getId();
			}
		} catch (\Exception $e) {
			foreach ($copiedFiles as $fileId) {
				try {
					$this->objectStore->deleteObject($this->getURN($fileId));
				} catch (\Exception $e) {
					// ignore
				}
			}
			throw $e;
		}
	}

	/**
	 * @return \Iterator<ICacheEntry>
	 */
	private function getAllChildObjects(ICache $cache, ICacheEntry $entry): \Iterator {
		if ($entry->getMimeType() === FileInfo::MIMETYPE_FOLDER) {
			foreach ($cache->getFolderContentsById($entry->getId()) as $child) {
				yield from $this->getAllChildObjects($cache, $child);
			}
		} else {
			yield $entry;
		}
	}

	public function copy(string $source, string $target): bool {
		$source = $this->normalizePath($source);
		$target = $this->normalizePath($target);

		$cache = $this->getCache();
		$sourceEntry = $cache->get($source);
		if (!$sourceEntry) {
			throw new NotFoundException('Source object not found');
		}

		$this->copyInner($cache, $sourceEntry, $target);

		return true;
	}

	private function copyInner(ICache $sourceCache, ICacheEntry $sourceEntry, string $to) {
		$cache = $this->getCache();

		if ($sourceEntry->getMimeType() === FileInfo::MIMETYPE_FOLDER) {
			if ($cache->inCache($to)) {
				$cache->remove($to);
			}
			$this->mkdir($to, false, ['size' => $sourceEntry->getSize()]);

			foreach ($sourceCache->getFolderContentsById($sourceEntry->getId()) as $child) {
				$this->copyInner($sourceCache, $child, $to . '/' . $child->getName());
			}
		} else {
			$this->copyFile($sourceEntry, $to);
		}
	}

	private function copyFile(ICacheEntry $sourceEntry, string $to) {
		$cache = $this->getCache();

		$sourceUrn = $this->getURN($sourceEntry->getId());

		if (!$cache instanceof Cache) {
			throw new \Exception('Invalid source cache for object store copy');
		}

		$targetId = $cache->copyFromCache($cache, $sourceEntry, $to);

		$targetUrn = $this->getURN($targetId);

		try {
			$this->objectStore->copyObject($sourceUrn, $targetUrn);
			if ($this->handleCopiesAsOwned) {
				// Copied the file thus we gain all permissions as we are the owner now ! warning while this aligns with local storage it should not be used and instead fix local storage !
				$cache->update($targetId, ['permissions' => \OCP\Constants::PERMISSION_ALL]);
			}
		} catch (\Exception $e) {
			$cache->remove($to);

			throw $e;
		}
	}

	public function startChunkedWrite(string $targetPath): string {
		if (!$this->objectStore instanceof IObjectStoreMultiPartUpload) {
			throw new GenericFileException('Object store does not support multipart upload');
		}
		$cacheEntry = $this->getCache()->get($targetPath);
		$urn = $this->getURN($cacheEntry->getId());
		return $this->objectStore->initiateMultipartUpload($urn);
	}

	/**
	 * @throws GenericFileException
	 */
	public function putChunkedWritePart(
		string $targetPath,
		string $writeToken,
		string $chunkId,
		$data,
		$size = null,
	): ?array {
		if (!$this->objectStore instanceof IObjectStoreMultiPartUpload) {
			throw new GenericFileException('Object store does not support multipart upload');
		}
		$cacheEntry = $this->getCache()->get($targetPath);
		$urn = $this->getURN($cacheEntry->getId());

		$result = $this->objectStore->uploadMultipartPart($urn, $writeToken, (int)$chunkId, $data, $size);

		$parts[$chunkId] = [
			'PartNumber' => $chunkId,
			'ETag' => trim($result->get('ETag'), '"'),
		];
		return $parts[$chunkId];
	}

	public function completeChunkedWrite(string $targetPath, string $writeToken): int {
		if (!$this->objectStore instanceof IObjectStoreMultiPartUpload) {
			throw new GenericFileException('Object store does not support multipart upload');
		}
		$cacheEntry = $this->getCache()->get($targetPath);
		$urn = $this->getURN($cacheEntry->getId());
		$parts = $this->objectStore->getMultipartUploads($urn, $writeToken);
		$sortedParts = array_values($parts);
		sort($sortedParts);
		try {
			$size = $this->objectStore->completeMultipartUpload($urn, $writeToken, $sortedParts);
			$stat = $this->stat($targetPath);
			$mtime = time();
			if (is_array($stat)) {
				$stat['size'] = $size;
				$stat['mtime'] = $mtime;
				$stat['mimetype'] = $this->getMimeType($targetPath);
				$this->getCache()->update($stat['fileid'], $stat);
			}
		} catch (S3MultipartUploadException|S3Exception $e) {
			$this->objectStore->abortMultipartUpload($urn, $writeToken);
			$this->logger->error(
				'Could not compete multipart upload ' . $urn . ' with uploadId ' . $writeToken,
				[
					'app' => 'objectstore',
					'exception' => $e,
				]
			);
			throw new GenericFileException('Could not write chunked file');
		}
		return $size;
	}

	public function cancelChunkedWrite(string $targetPath, string $writeToken): void {
		if (!$this->objectStore instanceof IObjectStoreMultiPartUpload) {
			throw new GenericFileException('Object store does not support multipart upload');
		}
		$cacheEntry = $this->getCache()->get($targetPath);
		$urn = $this->getURN($cacheEntry->getId());
		$this->objectStore->abortMultipartUpload($urn, $writeToken);
	}

	public function setPreserveCacheOnDelete(bool $preserve) {
		$this->preserveCacheItemsOnDelete = $preserve;
	}
}
