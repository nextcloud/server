<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OC\DatabaseException;
use OC\DB\Exceptions\DbalException;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Cache\Wrapper\CacheWrapper;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Wrapper\Encryption;
use OC\SystemConfig;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Cache\CacheEntryUpdatedEvent;
use OCP\Files\Cache\CacheInsertEvent;
use OCP\Files\Cache\CacheUpdateEvent;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;
use OCP\Files\Storage\IStorage;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Metadata cache for a storage
 *
 * The cache stores the metadata for all files and folders in a storage and is kept up to date through the following mechanisms:
 *
 * - Scanner: scans the storage and updates the cache where needed
 * - Watcher: checks for changes made to the filesystem outside of the Nextcloud instance and rescans files and folder when a change is detected
 * - Updater: listens to changes made to the filesystem inside of the Nextcloud instance and updates the cache where needed
 * - ChangePropagator: updates the mtime and etags of parent folders whenever a change to the cache is made to the cache by the updater
 */
class Cache implements ICache {
	use MoveFromCacheTrait {
		MoveFromCacheTrait::moveFromCache as moveFromCacheFallback;
	}

	/**
	 * @var array partial data for the cache
	 */
	protected array $partial = [];
	protected string $storageId;
	protected Storage $storageCache;
	protected IMimeTypeLoader $mimetypeLoader;
	protected IDBConnection $connection;
	protected SystemConfig $systemConfig;
	protected LoggerInterface $logger;
	protected QuerySearchHelper $querySearchHelper;
	protected IEventDispatcher $eventDispatcher;
	protected IFilesMetadataManager $metadataManager;

	public function __construct(
		private IStorage $storage,
		// this constructor is used in to many pleases to easily do proper di
		// so instead we group it all together
		?CacheDependencies $dependencies = null,
	) {
		$this->storageId = $storage->getId();
		if (strlen($this->storageId) > 64) {
			$this->storageId = md5($this->storageId);
		}
		if (!$dependencies) {
			$dependencies = Server::get(CacheDependencies::class);
		}
		$this->storageCache = new Storage($this->storage, true, $dependencies->getConnection());
		$this->mimetypeLoader = $dependencies->getMimeTypeLoader();
		$this->connection = $dependencies->getConnection();
		$this->systemConfig = $dependencies->getSystemConfig();
		$this->logger = $dependencies->getLogger();
		$this->querySearchHelper = $dependencies->getQuerySearchHelper();
		$this->eventDispatcher = $dependencies->getEventDispatcher();
		$this->metadataManager = $dependencies->getMetadataManager();
	}

	protected function getQueryBuilder() {
		return new CacheQueryBuilder(
			$this->connection->getQueryBuilder(),
			$this->metadataManager,
		);
	}

	public function getStorageCache(): Storage {
		return $this->storageCache;
	}

	/**
	 * Get the numeric storage id for this cache's storage
	 *
	 * @return int
	 */
	public function getNumericStorageId() {
		return $this->storageCache->getNumericId();
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string|int $file either the path of a file or folder or the file id for a file or folder
	 * @return ICacheEntry|false the cache entry as array or false if the file is not found in the cache
	 */
	public function get($file) {
		$query = $this->getQueryBuilder();
		$query->selectFileCache();
		$metadataQuery = $query->selectMetadata();

		if (is_string($file) || $file == '') {
			// normalize file
			$file = $this->normalize($file);

			$query->wherePath($file);
		} else { //file id
			$query->whereFileId($file);
		}
		$query->whereStorageId($this->getNumericStorageId());

		$result = $query->executeQuery();
		$data = $result->fetch();
		$result->closeCursor();

		if ($data !== false) {
			$data['metadata'] = $metadataQuery->extractMetadata($data)->asArray();
			return self::cacheEntryFromData($data, $this->mimetypeLoader);
		} else {
			//merge partial data
			if (is_string($file) && isset($this->partial[$file])) {
				return $this->partial[$file];
			}
		}

		return false;
	}

	/**
	 * Create a CacheEntry from database row
	 *
	 * @param array $data
	 * @param IMimeTypeLoader $mimetypeLoader
	 * @return CacheEntry
	 */
	public static function cacheEntryFromData($data, IMimeTypeLoader $mimetypeLoader) {
		//fix types
		$data['name'] = (string)$data['name'];
		$data['path'] = (string)$data['path'];
		$data['fileid'] = (int)$data['fileid'];
		$data['parent'] = (int)$data['parent'];
		$data['size'] = Util::numericToNumber($data['size']);
		$data['unencrypted_size'] = Util::numericToNumber($data['unencrypted_size'] ?? 0);
		$data['mtime'] = (int)$data['mtime'];
		$data['storage_mtime'] = (int)$data['storage_mtime'];
		$data['encryptedVersion'] = (int)$data['encrypted'];
		$data['encrypted'] = (bool)$data['encrypted'];
		$data['storage_id'] = $data['storage'];
		$data['storage'] = (int)$data['storage'];
		$data['mimetype'] = $mimetypeLoader->getMimetypeById($data['mimetype']);
		$data['mimepart'] = $mimetypeLoader->getMimetypeById($data['mimepart']);
		if ($data['storage_mtime'] == 0) {
			$data['storage_mtime'] = $data['mtime'];
		}
		if (isset($data['f_permissions'])) {
			$data['scan_permissions'] ??= $data['f_permissions'];
		}
		$data['permissions'] = (int)$data['permissions'];
		if (isset($data['creation_time'])) {
			$data['creation_time'] = (int)$data['creation_time'];
		}
		if (isset($data['upload_time'])) {
			$data['upload_time'] = (int)$data['upload_time'];
		}
		return new CacheEntry($data);
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return ICacheEntry[]
	 */
	public function getFolderContents($folder) {
		$fileId = $this->getId($folder);
		return $this->getFolderContentsById($fileId);
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param int $fileId the file id of the folder
	 * @return ICacheEntry[]
	 */
	public function getFolderContentsById($fileId) {
		if ($fileId > -1) {
			$query = $this->getQueryBuilder();
			$query->selectFileCache()
				->whereParent($fileId)
				->whereStorageId($this->getNumericStorageId())
				->orderBy('name', 'ASC');

			$metadataQuery = $query->selectMetadata();

			$result = $query->executeQuery();
			$files = $result->fetchAll();
			$result->closeCursor();

			return array_map(function (array $data) use ($metadataQuery) {
				$data['metadata'] = $metadataQuery->extractMetadata($data)->asArray();
				return self::cacheEntryFromData($data, $this->mimetypeLoader);
			}, $files);
		}
		return [];
	}

	/**
	 * insert or update meta data for a file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 */
	public function put($file, array $data) {
		if (($id = $this->getId($file)) > -1) {
			$this->update($id, $data);
			return $id;
		} else {
			return $this->insert($file, $data);
		}
	}

	/**
	 * insert meta data for a new file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException|Exception
	 */
	public function insert($file, array $data) {
		// normalize file
		$file = $this->normalize($file);

		if (isset($this->partial[$file])) { //add any saved partial data
			$data = array_merge($this->partial[$file]->getData(), $data);
			unset($this->partial[$file]);
		}

		$requiredFields = ['size', 'mtime', 'mimetype'];
		foreach ($requiredFields as $field) {
			if (!isset($data[$field])) { //data not complete save as partial and return
				$this->partial[$file] = new CacheEntry($data);
				return -1;
			}
		}

		$data['path'] = $file;
		if (!isset($data['parent'])) {
			$data['parent'] = $this->getParentId($file);
		}
		if ($data['parent'] === -1 && $file !== '') {
			throw new \Exception('Parent folder not in filecache for ' . $file);
		}
		$data['name'] = basename($file);

		[$values, $extensionValues] = $this->normalizeData($data);
		$storageId = $this->getNumericStorageId();
		$values['storage'] = $storageId;

		try {
			$builder = $this->connection->getQueryBuilder();
			$builder->insert('filecache');

			foreach ($values as $column => $value) {
				$builder->setValue($column, $builder->createNamedParameter($value));
			}

			if ($builder->executeStatement()) {
				$fileId = $builder->getLastInsertId();

				if (count($extensionValues)) {
					$query = $this->getQueryBuilder();
					$query->insert('filecache_extended');
					$query->hintShardKey('storage', $storageId);

					$query->setValue('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
					foreach ($extensionValues as $column => $value) {
						$query->setValue($column, $query->createNamedParameter($value));
					}
					$query->executeStatement();
				}

				$event = new CacheEntryInsertedEvent($this->storage, $file, $fileId, $storageId);
				$this->eventDispatcher->dispatch(CacheInsertEvent::class, $event);
				$this->eventDispatcher->dispatchTyped($event);
				return $fileId;
			}
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				// entry exists already
				if ($this->connection->inTransaction()) {
					$this->connection->commit();
					$this->connection->beginTransaction();
				}
			} else {
				throw $e;
			}
		}

		// The file was created in the meantime
		if (($id = $this->getId($file)) > -1) {
			$this->update($id, $data);
			return $id;
		} else {
			throw new \RuntimeException('File entry could not be inserted but could also not be selected with getId() in order to perform an update. Please try again.');
		}
	}

	/**
	 * update the metadata of an existing file or folder in the cache
	 *
	 * @param int $id the fileid of the existing file or folder
	 * @param array $data [$key => $value] the metadata to update, only the fields provided in the array will be updated, non-provided values will remain unchanged
	 */
	public function update($id, array $data) {
		if (isset($data['path'])) {
			// normalize path
			$data['path'] = $this->normalize($data['path']);
		}

		if (isset($data['name'])) {
			// normalize path
			$data['name'] = $this->normalize($data['name']);
		}

		[$values, $extensionValues] = $this->normalizeData($data);

		if (count($values)) {
			$query = $this->getQueryBuilder();

			$query->update('filecache')
				->whereFileId($id)
				->whereStorageId($this->getNumericStorageId())
				->andWhere($query->expr()->orX(...array_map(function ($key, $value) use ($query) {
					return $query->expr()->orX(
						$query->expr()->neq($key, $query->createNamedParameter($value)),
						$query->expr()->isNull($key)
					);
				}, array_keys($values), array_values($values))));

			foreach ($values as $key => $value) {
				$query->set($key, $query->createNamedParameter($value));
			}

			$query->executeStatement();
		}

		if (count($extensionValues)) {
			try {
				$query = $this->getQueryBuilder();
				$query->insert('filecache_extended');
				$query->hintShardKey('storage', $this->getNumericStorageId());

				$query->setValue('fileid', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT));
				foreach ($extensionValues as $column => $value) {
					$query->setValue($column, $query->createNamedParameter($value));
				}

				$query->executeStatement();
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $e;
				}
				$query = $this->getQueryBuilder();
				$query->update('filecache_extended')
					->whereFileId($id)
					->hintShardKey('storage', $this->getNumericStorageId())
					->andWhere($query->expr()->orX(...array_map(function ($key, $value) use ($query) {
						return $query->expr()->orX(
							$query->expr()->neq($key, $query->createNamedParameter($value)),
							$query->expr()->isNull($key)
						);
					}, array_keys($extensionValues), array_values($extensionValues))));

				foreach ($extensionValues as $key => $value) {
					$query->set($key, $query->createNamedParameter($value));
				}

				$query->executeStatement();
			}
		}

		$path = $this->getPathById($id);
		// path can still be null if the file doesn't exist
		if ($path !== null) {
			$event = new CacheEntryUpdatedEvent($this->storage, $path, $id, $this->getNumericStorageId());
			$this->eventDispatcher->dispatch(CacheUpdateEvent::class, $event);
			$this->eventDispatcher->dispatchTyped($event);
		}
	}

	/**
	 * extract query parts and params array from data array
	 *
	 * @param array $data
	 * @return array
	 */
	protected function normalizeData(array $data): array {
		$fields = [
			'path', 'parent', 'name', 'mimetype', 'size', 'mtime', 'storage_mtime', 'encrypted',
			'etag', 'permissions', 'checksum', 'storage', 'unencrypted_size'];
		$extensionFields = ['metadata_etag', 'creation_time', 'upload_time'];

		$doNotCopyStorageMTime = false;
		if (array_key_exists('mtime', $data) && $data['mtime'] === null) {
			// this horrific magic tells it to not copy storage_mtime to mtime
			unset($data['mtime']);
			$doNotCopyStorageMTime = true;
		}

		$params = [];
		$extensionParams = [];
		foreach ($data as $name => $value) {
			if (in_array($name, $fields)) {
				if ($name === 'path') {
					$params['path_hash'] = md5($value);
				} elseif ($name === 'mimetype') {
					$params['mimepart'] = $this->mimetypeLoader->getId(substr($value, 0, strpos($value, '/')));
					$value = $this->mimetypeLoader->getId($value);
				} elseif ($name === 'storage_mtime') {
					if (!$doNotCopyStorageMTime && !isset($data['mtime'])) {
						$params['mtime'] = $value;
					}
				} elseif ($name === 'encrypted') {
					if (isset($data['encryptedVersion'])) {
						$value = $data['encryptedVersion'];
					} else {
						// Boolean to integer conversion
						$value = $value ? 1 : 0;
					}
				}
				$params[$name] = $value;
			}
			if (in_array($name, $extensionFields)) {
				$extensionParams[$name] = $value;
			}
		}
		return [$params, array_filter($extensionParams)];
	}

	/**
	 * get the file id for a file
	 *
	 * A file id is a numeric id for a file or folder that's unique within an owncloud instance which stays the same for the lifetime of a file
	 *
	 * File ids are easiest way for apps to store references to a file since unlike paths they are not affected by renames or sharing
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		// normalize file
		$file = $this->normalize($file);

		$query = $this->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->whereStorageId($this->getNumericStorageId())
			->wherePath($file);

		$result = $query->executeQuery();
		$id = $result->fetchOne();
		$result->closeCursor();

		return $id === false ? -1 : (int)$id;
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file) {
		if ($file === '') {
			return -1;
		} else {
			$parent = $this->getParentPath($file);
			return (int)$this->getId($parent);
		}
	}

	private function getParentPath($path) {
		$parent = dirname($path);
		if ($parent === '.') {
			$parent = '';
		}
		return $parent;
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->getId($file) != -1;
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * when removing a folder from the cache all files and folders inside the folder will be removed as well
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$entry = $this->get($file);

		if ($entry instanceof ICacheEntry) {
			$query = $this->getQueryBuilder();
			$query->delete('filecache')
				->whereStorageId($this->getNumericStorageId())
				->whereFileId($entry->getId());
			$query->executeStatement();

			$query = $this->getQueryBuilder();
			$query->delete('filecache_extended')
				->whereFileId($entry->getId())
				->hintShardKey('storage', $this->getNumericStorageId());
			$query->executeStatement();

			if ($entry->getMimeType() == FileInfo::MIMETYPE_FOLDER) {
				$this->removeChildren($entry);
			}

			$this->eventDispatcher->dispatchTyped(new CacheEntryRemovedEvent($this->storage, $entry->getPath(), $entry->getId(), $this->getNumericStorageId()));
		}
	}

	/**
	 * Remove all children of a folder
	 *
	 * @param ICacheEntry $entry the cache entry of the folder to remove the children of
	 * @throws DatabaseException
	 */
	private function removeChildren(ICacheEntry $entry) {
		$parentIds = [$entry->getId()];
		$queue = [$entry->getId()];
		$deletedIds = [];
		$deletedPaths = [];

		// we walk depth first through the file tree, removing all filecache_extended attributes while we walk
		// and collecting all folder ids to later use to delete the filecache entries
		while ($entryId = array_pop($queue)) {
			$children = $this->getFolderContentsById($entryId);
			$childIds = array_map(function (ICacheEntry $cacheEntry) {
				return $cacheEntry->getId();
			}, $children);
			$childPaths = array_map(function (ICacheEntry $cacheEntry) {
				return $cacheEntry->getPath();
			}, $children);

			foreach ($childIds as $childId) {
				$deletedIds[] = $childId;
			}

			foreach ($childPaths as $childPath) {
				$deletedPaths[] = $childPath;
			}

			$query = $this->getQueryBuilder();
			$query->delete('filecache_extended')
				->where($query->expr()->in('fileid', $query->createParameter('childIds')))
				->hintShardKey('storage', $this->getNumericStorageId());

			foreach (array_chunk($childIds, 1000) as $childIdChunk) {
				$query->setParameter('childIds', $childIdChunk, IQueryBuilder::PARAM_INT_ARRAY);
				$query->executeStatement();
			}

			/** @var ICacheEntry[] $childFolders */
			$childFolders = [];
			foreach ($children as $child) {
				if ($child->getMimeType() == FileInfo::MIMETYPE_FOLDER) {
					$childFolders[] = $child;
				}
			}
			foreach ($childFolders as $folder) {
				$parentIds[] = $folder->getId();
				$queue[] = $folder->getId();
			}
		}

		$query = $this->getQueryBuilder();
		$query->delete('filecache')
			->whereStorageId($this->getNumericStorageId())
			->whereParentInParameter('parentIds');

		// Sorting before chunking allows the db to find the entries close to each
		// other in the index
		sort($parentIds, SORT_NUMERIC);
		foreach (array_chunk($parentIds, 1000) as $parentIdChunk) {
			$query->setParameter('parentIds', $parentIdChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->executeStatement();
		}

		foreach (array_combine($deletedIds, $deletedPaths) as $fileId => $filePath) {
			$cacheEntryRemovedEvent = new CacheEntryRemovedEvent(
				$this->storage,
				$filePath,
				$fileId,
				$this->getNumericStorageId()
			);
			$this->eventDispatcher->dispatchTyped($cacheEntryRemovedEvent);
		}
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->moveFromCache($this, $source, $target);
	}

	/**
	 * Get the storage id and path needed for a move
	 *
	 * @param string $path
	 * @return array [$storageId, $internalPath]
	 */
	protected function getMoveInfo($path) {
		return [$this->getNumericStorageId(), $path];
	}

	protected function hasEncryptionWrapper(): bool {
		return $this->storage->instanceOfStorage(Encryption::class);
	}

	protected function shouldEncrypt(string $targetPath): bool {
		if (!$this->storage->instanceOfStorage(Encryption::class)) {
			return false;
		}
		return $this->storage->shouldEncrypt($targetPath);
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @throws DatabaseException
	 * @throws \Exception if the given storages have an invalid id
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		if ($sourceCache instanceof Cache) {
			// normalize source and target
			$sourcePath = $this->normalize($sourcePath);
			$targetPath = $this->normalize($targetPath);

			$sourceData = $sourceCache->get($sourcePath);
			if (!$sourceData) {
				throw new \Exception('Source path not found in cache: ' . $sourcePath);
			}

			$shardDefinition = $this->connection->getShardDefinition('filecache');
			if (
				$shardDefinition
				&& $shardDefinition->getShardForKey($sourceCache->getNumericStorageId()) !== $shardDefinition->getShardForKey($this->getNumericStorageId())
			) {
				$this->moveFromStorageSharded($shardDefinition, $sourceCache, $sourceData, $targetPath);
				return;
			}

			$sourceId = $sourceData['fileid'];
			$newParentId = $this->getParentId($targetPath);

			[$sourceStorageId, $sourcePath] = $sourceCache->getMoveInfo($sourcePath);
			[$targetStorageId, $targetPath] = $this->getMoveInfo($targetPath);

			if (is_null($sourceStorageId) || $sourceStorageId === false) {
				throw new \Exception('Invalid source storage id: ' . $sourceStorageId);
			}
			if (is_null($targetStorageId) || $targetStorageId === false) {
				throw new \Exception('Invalid target storage id: ' . $targetStorageId);
			}

			if ($sourceData['mimetype'] === 'httpd/unix-directory') {
				//update all child entries
				$sourceLength = mb_strlen($sourcePath);

				$childIds = $this->getChildIds($sourceStorageId, $sourcePath);

				$childChunks = array_chunk($childIds, 1000);

				$query = $this->getQueryBuilder();

				$fun = $query->func();
				$newPathFunction = $fun->concat(
					$query->createNamedParameter($targetPath),
					$fun->substring('path', $query->createNamedParameter($sourceLength + 1, IQueryBuilder::PARAM_INT))// +1 for the leading slash
				);
				$query->update('filecache')
					->set('path_hash', $fun->md5($newPathFunction))
					->set('path', $newPathFunction)
					->whereStorageId($sourceStorageId)
					->andWhere($query->expr()->in('fileid', $query->createParameter('files')));

				if ($sourceStorageId !== $targetStorageId) {
					$query->set('storage', $query->createNamedParameter($targetStorageId), IQueryBuilder::PARAM_INT);
				}

				// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
				if ($sourceCache->hasEncryptionWrapper() && !$this->hasEncryptionWrapper()) {
					$query->set('encrypted', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT));
				}

				// Retry transaction in case of RetryableException like deadlocks.
				// Retry up to 4 times because we should receive up to 4 concurrent requests from the frontend
				$retryLimit = 4;
				for ($i = 1; $i <= $retryLimit; $i++) {
					try {
						$this->connection->beginTransaction();
						foreach ($childChunks as $chunk) {
							$query->setParameter('files', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
							$query->executeStatement();
						}
						break;
					} catch (DatabaseException $e) {
						$this->connection->rollBack();
						throw $e;
					} catch (DbalException $e) {
						$this->connection->rollBack();

						if (!$e->isRetryable()) {
							throw $e;
						}

						// Simply throw if we already retried 4 times.
						if ($i === $retryLimit) {
							throw $e;
						}

						// Sleep a bit to give some time to the other transaction to finish.
						usleep(100 * 1000 * $i);
					}
				}
			} else {
				$this->connection->beginTransaction();
			}

			$query = $this->getQueryBuilder();
			$query->update('filecache')
				->set('path', $query->createNamedParameter($targetPath))
				->set('path_hash', $query->createNamedParameter(md5($targetPath)))
				->set('name', $query->createNamedParameter(basename($targetPath)))
				->set('parent', $query->createNamedParameter($newParentId, IQueryBuilder::PARAM_INT))
				->whereStorageId($sourceStorageId)
				->whereFileId($sourceId);

			if ($sourceStorageId !== $targetStorageId) {
				$query->set('storage', $query->createNamedParameter($targetStorageId), IQueryBuilder::PARAM_INT);
			}

			// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
			if ($sourceCache->hasEncryptionWrapper() && !$this->hasEncryptionWrapper()) {
				$query->set('encrypted', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT));
			}

			$query->executeStatement();

			$this->connection->commit();

			if ($sourceCache->getNumericStorageId() !== $this->getNumericStorageId()) {
				\OCP\Server::get(\OCP\Files\Config\IUserMountCache::class)->clear();
				$this->eventDispatcher->dispatchTyped(new CacheEntryRemovedEvent($this->storage, $sourcePath, $sourceId, $sourceCache->getNumericStorageId()));
				$event = new CacheEntryInsertedEvent($this->storage, $targetPath, $sourceId, $this->getNumericStorageId());
				$this->eventDispatcher->dispatch(CacheInsertEvent::class, $event);
				$this->eventDispatcher->dispatchTyped($event);
			} else {
				$event = new CacheEntryUpdatedEvent($this->storage, $targetPath, $sourceId, $this->getNumericStorageId());
				$this->eventDispatcher->dispatch(CacheUpdateEvent::class, $event);
				$this->eventDispatcher->dispatchTyped($event);
			}
		} else {
			$this->moveFromCacheFallback($sourceCache, $sourcePath, $targetPath);
		}
	}

	private function getChildIds(int $storageId, string $path): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->like('path', $query->createNamedParameter($this->connection->escapeLikeParameter($path) . '/%')));
		return $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$query = $this->getQueryBuilder();
		$query->delete('filecache')
			->whereStorageId($this->getNumericStorageId());
		$query->executeStatement();

		$query = $this->connection->getQueryBuilder();
		$query->delete('storages')
			->where($query->expr()->eq('id', $query->createNamedParameter($this->storageId)));
		$query->executeStatement();
	}

	/**
	 * Get the scan status of a file
	 *
	 * - Cache::NOT_FOUND: File is not in the cache
	 * - Cache::PARTIAL: File is not stored in the cache but some incomplete data is known
	 * - Cache::SHALLOW: The folder and it's direct children are in the cache but not all sub folders are fully scanned
	 * - Cache::COMPLETE: The file or folder, with all it's children) are fully scanned
	 *
	 * @param string $file
	 *
	 * @return int Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		// normalize file
		$file = $this->normalize($file);

		$query = $this->getQueryBuilder();
		$query->select('size')
			->from('filecache')
			->whereStorageId($this->getNumericStorageId())
			->wherePath($file);

		$result = $query->executeQuery();
		$size = $result->fetchOne();
		$result->closeCursor();

		if ($size !== false) {
			if ((int)$size === -1) {
				return self::SHALLOW;
			} else {
				return self::COMPLETE;
			}
		} else {
			if (isset($this->partial[$file])) {
				return self::PARTIAL;
			} else {
				return self::NOT_FOUND;
			}
		}
	}

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern the search pattern using SQL search syntax (e.g. '%searchstring%')
	 * @return ICacheEntry[] an array of cache entries where the name matches the search pattern
	 */
	public function search($pattern) {
		$operator = new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', $pattern);
		return $this->searchQuery(new SearchQuery($operator, 0, 0, [], null));
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype either a full mimetype to search ('text/plain') or only the first part of a mimetype ('image')
	 *                         where it will search for all mimetypes in the group ('image/*')
	 * @return ICacheEntry[] an array of cache entries where the mimetype matches the search
	 */
	public function searchByMime($mimetype) {
		if (!str_contains($mimetype, '/')) {
			$operator = new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', $mimetype . '/%');
		} else {
			$operator = new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', $mimetype);
		}
		return $this->searchQuery(new SearchQuery($operator, 0, 0, [], null));
	}

	public function searchQuery(ISearchQuery $query) {
		return current($this->querySearchHelper->searchInCaches($query, [$this]));
	}

	/**
	 * Re-calculate the folder size and the size of all parent folders
	 *
	 * @param array|ICacheEntry|null $data (optional) meta data of the folder
	 */
	public function correctFolderSize(string $path, $data = null, bool $isBackgroundScan = false): void {
		$this->calculateFolderSize($path, $data);

		if ($path !== '') {
			$parent = dirname($path);
			if ($parent === '.' || $parent === '/') {
				$parent = '';
			}

			if ($isBackgroundScan) {
				$parentData = $this->get($parent);
				if ($parentData !== false
					&& $parentData['size'] !== -1
					&& $this->getIncompleteChildrenCount($parentData['fileid']) === 0
				) {
					$this->correctFolderSize($parent, $parentData, $isBackgroundScan);
				}
			} else {
				$this->correctFolderSize($parent);
			}
		}
	}

	/**
	 * get the incomplete count that shares parent $folder
	 *
	 * @param int $fileId the file id of the folder
	 * @return int
	 */
	public function getIncompleteChildrenCount($fileId) {
		if ($fileId > -1) {
			$query = $this->getQueryBuilder();
			$query->select($query->func()->count())
				->from('filecache')
				->whereParent($fileId)
				->whereStorageId($this->getNumericStorageId())
				->andWhere($query->expr()->eq('size', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));

			$result = $query->executeQuery();
			$size = (int)$result->fetchOne();
			$result->closeCursor();

			return $size;
		}
		return -1;
	}

	/**
	 * calculate the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array|null|ICacheEntry $entry (optional) meta data of the folder
	 * @return int|float
	 */
	public function calculateFolderSize($path, $entry = null) {
		return $this->calculateFolderSizeInner($path, $entry);
	}


	/**
	 * inner function because we can't add new params to the public function without breaking any child classes
	 *
	 * @param string $path
	 * @param array|null|ICacheEntry $entry (optional) meta data of the folder
	 * @param bool $ignoreUnknown don't mark the folder size as unknown if any of it's children are unknown
	 * @return int|float
	 */
	protected function calculateFolderSizeInner(string $path, $entry = null, bool $ignoreUnknown = false) {
		$totalSize = 0;
		if (is_null($entry) || !isset($entry['fileid'])) {
			$entry = $this->get($path);
		}
		if (isset($entry['mimetype']) && $entry['mimetype'] === FileInfo::MIMETYPE_FOLDER) {
			$id = $entry['fileid'];

			$query = $this->getQueryBuilder();
			$query->select('size', 'unencrypted_size')
				->from('filecache')
				->whereStorageId($this->getNumericStorageId())
				->whereParent($id);
			if ($ignoreUnknown) {
				$query->andWhere($query->expr()->gte('size', $query->createNamedParameter(0)));
			}

			$result = $query->executeQuery();
			$rows = $result->fetchAll();
			$result->closeCursor();

			if ($rows) {
				$sizes = array_map(function (array $row) {
					return Util::numericToNumber($row['size']);
				}, $rows);
				$unencryptedOnlySizes = array_map(function (array $row) {
					return Util::numericToNumber($row['unencrypted_size']);
				}, $rows);
				$unencryptedSizes = array_map(function (array $row) {
					return Util::numericToNumber(($row['unencrypted_size'] > 0) ? $row['unencrypted_size'] : $row['size']);
				}, $rows);

				$sum = array_sum($sizes);
				$min = min($sizes);

				$unencryptedSum = array_sum($unencryptedSizes);
				$unencryptedMin = min($unencryptedSizes);
				$unencryptedMax = max($unencryptedOnlySizes);

				$sum = 0 + $sum;
				$min = 0 + $min;
				if ($min === -1) {
					$totalSize = $min;
				} else {
					$totalSize = $sum;
				}
				if ($unencryptedMin === -1 || $min === -1) {
					$unencryptedTotal = $unencryptedMin;
				} else {
					$unencryptedTotal = $unencryptedSum;
				}
			} else {
				$totalSize = 0;
				$unencryptedTotal = 0;
				$unencryptedMax = 0;
			}

			// only set unencrypted size for a folder if any child entries have it set, or the folder is empty
			$shouldWriteUnEncryptedSize = $unencryptedMax > 0 || $totalSize === 0 || ($entry['unencrypted_size'] ?? 0) > 0;
			if ($entry['size'] !== $totalSize || (($entry['unencrypted_size'] ?? 0) !== $unencryptedTotal && $shouldWriteUnEncryptedSize)) {
				if ($shouldWriteUnEncryptedSize) {
					// if all children have an unencrypted size of 0, just set the folder unencrypted size to 0 instead of summing the sizes
					if ($unencryptedMax === 0) {
						$unencryptedTotal = 0;
					}

					$this->update($id, [
						'size' => $totalSize,
						'unencrypted_size' => $unencryptedTotal,
					]);
				} else {
					$this->update($id, [
						'size' => $totalSize,
					]);
				}
			}
		}
		return $totalSize;
	}

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll() {
		$query = $this->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->whereStorageId($this->getNumericStorageId());

		$result = $query->executeQuery();
		$files = $result->fetchAll(\PDO::FETCH_COLUMN);
		$result->closeCursor();

		return array_map(function ($id) {
			return (int)$id;
		}, $files);
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiple incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|false the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		$query = $this->getQueryBuilder();
		$query->select('path')
			->from('filecache')
			->whereStorageId($this->getNumericStorageId())
			->andWhere($query->expr()->eq('size', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)))
			->orderBy('fileid', 'DESC')
			->setMaxResults(1);

		$result = $query->executeQuery();
		$path = $result->fetchOne();
		$result->closeCursor();

		return $path === false ? false : (string)$path;
	}

	/**
	 * get the path of a file on this storage by it's file id
	 *
	 * @param int $id the file id of the file or folder to search
	 * @return string|null the path of the file (relative to the storage) or null if a file with the given id does not exists within this cache
	 */
	public function getPathById($id) {
		$query = $this->getQueryBuilder();
		$query->select('path')
			->from('filecache')
			->whereStorageId($this->getNumericStorageId())
			->whereFileId($id);

		$result = $query->executeQuery();
		$path = $result->fetchOne();
		$result->closeCursor();

		if ($path === false) {
			return null;
		}

		return (string)$path;
	}

	/**
	 * get the storage id of the storage for a file and the internal path of the file
	 * unlike getPathById this does not limit the search to files on this storage and
	 * instead does a global search in the cache table
	 *
	 * @param int $id
	 * @return array first element holding the storage id, second the path
	 * @deprecated 17.0.0 use getPathById() instead
	 */
	public static function getById($id) {
		$query = Server::get(IDBConnection::class)->getQueryBuilder();
		$query->select('path', 'storage')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			$numericId = $row['storage'];
			$path = $row['path'];
		} else {
			return null;
		}

		if ($id = Storage::getStorageId($numericId)) {
			return [$id, $path];
		} else {
			return null;
		}
	}

	/**
	 * normalize the given path
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalize($path) {
		return trim(\OC_Util::normalizeUnicode($path), '/');
	}

	/**
	 * Copy a file or folder in the cache
	 *
	 * @param ICache $sourceCache
	 * @param ICacheEntry $sourceEntry
	 * @param string $targetPath
	 * @return int fileId of copied entry
	 */
	public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int {
		if ($sourceEntry->getId() < 0) {
			throw new \RuntimeException('Invalid source cache entry on copyFromCache');
		}
		$data = $this->cacheEntryToArray($sourceEntry);

		// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
		if ($sourceCache instanceof Cache
			&& $sourceCache->hasEncryptionWrapper()
			&& !$this->shouldEncrypt($targetPath)) {
			$data['encrypted'] = 0;
		}

		$fileId = $this->put($targetPath, $data);
		if ($fileId <= 0) {
			throw new \RuntimeException('Failed to copy to ' . $targetPath . ' from cache with source data ' . json_encode($data) . ' ');
		}
		if ($sourceEntry->getMimeType() === ICacheEntry::DIRECTORY_MIMETYPE) {
			$folderContent = $sourceCache->getFolderContentsById($sourceEntry->getId());
			foreach ($folderContent as $subEntry) {
				$subTargetPath = $targetPath . '/' . $subEntry->getName();
				$this->copyFromCache($sourceCache, $subEntry, $subTargetPath);
			}
		}
		return $fileId;
	}

	private function cacheEntryToArray(ICacheEntry $entry): array {
		$data = [
			'size' => $entry->getSize(),
			'mtime' => $entry->getMTime(),
			'storage_mtime' => $entry->getStorageMTime(),
			'mimetype' => $entry->getMimeType(),
			'mimepart' => $entry->getMimePart(),
			'etag' => $entry->getEtag(),
			'permissions' => $entry->getPermissions(),
			'encrypted' => $entry->isEncrypted(),
			'creation_time' => $entry->getCreationTime(),
			'upload_time' => $entry->getUploadTime(),
			'metadata_etag' => $entry->getMetadataEtag(),
		];
		if ($entry instanceof CacheEntry && isset($entry['scan_permissions'])) {
			$data['permissions'] = $entry['scan_permissions'];
		}
		return $data;
	}

	public function getQueryFilterForStorage(): ISearchOperator {
		return new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', $this->getNumericStorageId());
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		if ($rawEntry->getStorageId() === $this->getNumericStorageId()) {
			return $rawEntry;
		} else {
			return null;
		}
	}

	private function moveFromStorageSharded(ShardDefinition $shardDefinition, ICache $sourceCache, ICacheEntry $sourceEntry, $targetPath): void {
		$sourcePath = $sourceEntry->getPath();
		while ($sourceCache instanceof CacheWrapper) {
			if ($sourceCache instanceof CacheJail) {
				$sourcePath = $sourceCache->getSourcePath($sourcePath);
			}
			$sourceCache = $sourceCache->getCache();
		}

		if ($sourceEntry->getMimeType() === ICacheEntry::DIRECTORY_MIMETYPE) {
			$fileIds = $this->getChildIds($sourceCache->getNumericStorageId(), $sourcePath);
		} else {
			$fileIds = [];
		}
		$fileIds[] = $sourceEntry->getId();

		$helper = $this->connection->getCrossShardMoveHelper();

		$sourceConnection = $helper->getConnection($shardDefinition, $sourceCache->getNumericStorageId());
		$targetConnection = $helper->getConnection($shardDefinition, $this->getNumericStorageId());

		$cacheItems = $helper->loadItems($sourceConnection, 'filecache', 'fileid', $fileIds);
		$extendedItems = $helper->loadItems($sourceConnection, 'filecache_extended', 'fileid', $fileIds);
		$metadataItems = $helper->loadItems($sourceConnection, 'files_metadata', 'file_id', $fileIds);

		// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
		$removeEncryptedFlag = ($sourceCache instanceof Cache && $sourceCache->hasEncryptionWrapper()) && !$this->hasEncryptionWrapper();

		$sourcePathLength = strlen($sourcePath);
		foreach ($cacheItems as &$cacheItem) {
			if ($cacheItem['path'] === $sourcePath) {
				$cacheItem['path'] = $targetPath;
				$cacheItem['parent'] = $this->getParentId($targetPath);
				$cacheItem['name'] = basename($cacheItem['path']);
			} else {
				$cacheItem['path'] = $targetPath . '/' . substr($cacheItem['path'], $sourcePathLength + 1); // +1 for the leading slash
			}
			$cacheItem['path_hash'] = md5($cacheItem['path']);
			$cacheItem['storage'] = $this->getNumericStorageId();
			if ($removeEncryptedFlag) {
				$cacheItem['encrypted'] = 0;
			}
		}

		$targetConnection->beginTransaction();

		try {
			$helper->saveItems($targetConnection, 'filecache', $cacheItems);
			$helper->saveItems($targetConnection, 'filecache_extended', $extendedItems);
			$helper->saveItems($targetConnection, 'files_metadata', $metadataItems);
		} catch (\Exception $e) {
			$targetConnection->rollback();
			throw $e;
		}

		$sourceConnection->beginTransaction();

		try {
			$helper->deleteItems($sourceConnection, 'filecache', 'fileid', $fileIds);
			$helper->deleteItems($sourceConnection, 'filecache_extended', 'fileid', $fileIds);
			$helper->deleteItems($sourceConnection, 'files_metadata', 'file_id', $fileIds);
		} catch (\Exception $e) {
			$targetConnection->rollback();
			$sourceConnection->rollBack();
			throw $e;
		}

		try {
			$sourceConnection->commit();
		} catch (\Exception $e) {
			$targetConnection->rollback();
			throw $e;
		}
		$targetConnection->commit();
	}
}
