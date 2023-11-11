<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Ari Selseng <ari@selseng.net>
 * @author Artem Kochnev <MrJeos@gmail.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Florin Peter <github@florin-peter.de>
 * @author Frédéric Fortier <frederic.fortier@oronospolytechnique.com>
 * @author Jens-Christian Fischer <jens-christian.fischer@switch.ch>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Cache;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Wrapper\Encryption;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryUpdatedEvent;
use OCP\Files\Cache\CacheInsertEvent;
use OCP\Files\Cache\CacheEntryRemovedEvent;
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
	protected $partial = [];

	/**
	 * @var string
	 */
	protected $storageId;

	private $storage;

	/**
	 * @var Storage $storageCache
	 */
	protected $storageCache;

	/** @var IMimeTypeLoader */
	protected $mimetypeLoader;

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @var IEventDispatcher
	 */
	protected $eventDispatcher;

	/** @var QuerySearchHelper */
	protected $querySearchHelper;

	/**
	 * @param IStorage $storage
	 */
	public function __construct(IStorage $storage) {
		$this->storageId = $storage->getId();
		$this->storage = $storage;
		if (strlen($this->storageId) > 64) {
			$this->storageId = md5($this->storageId);
		}

		$this->storageCache = new Storage($storage);
		$this->mimetypeLoader = \OC::$server->getMimeTypeLoader();
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->eventDispatcher = \OC::$server->get(IEventDispatcher::class);
		$this->querySearchHelper = \OCP\Server::get(QuerySearchHelper::class);
	}

	protected function getQueryBuilder() {
		return new CacheQueryBuilder(
			$this->connection,
			\OC::$server->getSystemConfig(),
			\OC::$server->get(LoggerInterface::class),
			\OC::$server->get(IFilesMetadataManager::class),
		);
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
	 * @param string | int $file either the path of a file or folder or the file id for a file or folder
	 * @return ICacheEntry|false the cache entry as array or false if the file is not found in the cache
	 */
	public function get($file) {
		$query = $this->getQueryBuilder();
		$query->selectFileCache();
		$metadataQuery = $query->selectMetadata();

		if (is_string($file) || $file == '') {
			// normalize file
			$file = $this->normalize($file);

			$query->whereStorageId($this->getNumericStorageId())
				->wherePath($file);
		} else { //file id
			$query->whereFileId($file);
		}

		$result = $query->execute();
		$data = $result->fetch();
		$result->closeCursor();

		//merge partial data
		if (!$data && is_string($file) && isset($this->partial[$file])) {
			return $this->partial[$file];
		} elseif (!$data) {
			return $data;
		} else {
			$data['metadata'] = $metadataQuery->extractMetadata($data)->asArray();
			return self::cacheEntryFromData($data, $this->mimetypeLoader);
		}
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
				->orderBy('name', 'ASC');

			$metadataQuery = $query->selectMetadata();

			$result = $query->execute();
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
	 * @throws \RuntimeException
	 */
	public function insert($file, array $data) {
		// normalize file
		$file = $this->normalize($file);

		if (isset($this->partial[$file])) { //add any saved partial data
			$data = array_merge($this->partial[$file], $data);
			unset($this->partial[$file]);
		}

		$requiredFields = ['size', 'mtime', 'mimetype'];
		foreach ($requiredFields as $field) {
			if (!isset($data[$field])) { //data not complete save as partial and return
				$this->partial[$file] = $data;
				return -1;
			}
		}

		$data['path'] = $file;
		if (!isset($data['parent'])) {
			$data['parent'] = $this->getParentId($file);
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

			if ($builder->execute()) {
				$fileId = $builder->getLastInsertId();

				if (count($extensionValues)) {
					$query = $this->getQueryBuilder();
					$query->insert('filecache_extended');

					$query->setValue('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
					foreach ($extensionValues as $column => $value) {
						$query->setValue($column, $query->createNamedParameter($value));
					}
					$query->execute();
				}

				$event = new CacheEntryInsertedEvent($this->storage, $file, $fileId, $storageId);
				$this->eventDispatcher->dispatch(CacheInsertEvent::class, $event);
				$this->eventDispatcher->dispatchTyped($event);
				return $fileId;
			}
		} catch (UniqueConstraintViolationException $e) {
			// entry exists already
			if ($this->connection->inTransaction()) {
				$this->connection->commit();
				$this->connection->beginTransaction();
			}
		}

		// The file was created in the mean time
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
				->andWhere($query->expr()->orX(...array_map(function ($key, $value) use ($query) {
					return $query->expr()->orX(
						$query->expr()->neq($key, $query->createNamedParameter($value)),
						$query->expr()->isNull($key)
					);
				}, array_keys($values), array_values($values))));

			foreach ($values as $key => $value) {
				$query->set($key, $query->createNamedParameter($value));
			}

			$query->execute();
		}

		if (count($extensionValues)) {
			try {
				$query = $this->getQueryBuilder();
				$query->insert('filecache_extended');

				$query->setValue('fileid', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT));
				foreach ($extensionValues as $column => $value) {
					$query->setValue($column, $query->createNamedParameter($value));
				}

				$query->execute();
			} catch (UniqueConstraintViolationException $e) {
				$query = $this->getQueryBuilder();
				$query->update('filecache_extended')
					->whereFileId($id)
					->andWhere($query->expr()->orX(...array_map(function ($key, $value) use ($query) {
						return $query->expr()->orX(
							$query->expr()->neq($key, $query->createNamedParameter($value)),
							$query->expr()->isNull($key)
						);
					}, array_keys($extensionValues), array_values($extensionValues))));

				foreach ($extensionValues as $key => $value) {
					$query->set($key, $query->createNamedParameter($value));
				}

				$query->execute();
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
			if (array_search($name, $fields) !== false) {
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
			if (array_search($name, $extensionFields) !== false) {
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

		$result = $query->execute();
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
				->whereFileId($entry->getId());
			$query->execute();

			$query = $this->getQueryBuilder();
			$query->delete('filecache_extended')
				->whereFileId($entry->getId());
			$query->execute();

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
	 * @throws \OC\DatabaseException
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

			$deletedIds = array_merge($deletedIds, $childIds);
			$deletedPaths = array_merge($deletedPaths, $childPaths);

			$query = $this->getQueryBuilder();
			$query->delete('filecache_extended')
				->where($query->expr()->in('fileid', $query->createParameter('childIds')));

			foreach (array_chunk($childIds, 1000) as $childIdChunk) {
				$query->setParameter('childIds', $childIdChunk, IQueryBuilder::PARAM_INT_ARRAY);
				$query->execute();
			}

			/** @var ICacheEntry[] $childFolders */
			$childFolders = array_filter($children, function ($child) {
				return $child->getMimeType() == FileInfo::MIMETYPE_FOLDER;
			});
			foreach ($childFolders as $folder) {
				$parentIds[] = $folder->getId();
				$queue[] = $folder->getId();
			}
		}

		$query = $this->getQueryBuilder();
		$query->delete('filecache')
			->whereParentInParameter('parentIds');

		foreach (array_chunk($parentIds, 1000) as $parentIdChunk) {
			$query->setParameter('parentIds', $parentIdChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->execute();
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

	/**
	 * Move a file or folder in the cache
	 *
	 * @param ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @throws \OC\DatabaseException
	 * @throws \Exception if the given storages have an invalid id
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		if ($sourceCache instanceof Cache) {
			// normalize source and target
			$sourcePath = $this->normalize($sourcePath);
			$targetPath = $this->normalize($targetPath);

			$sourceData = $sourceCache->get($sourcePath);
			if (!$sourceData) {
				throw new \Exception('Invalid source storage path: ' . $sourcePath);
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

			$this->connection->beginTransaction();
			if ($sourceData['mimetype'] === 'httpd/unix-directory') {
				//update all child entries
				$sourceLength = mb_strlen($sourcePath);
				$query = $this->connection->getQueryBuilder();

				$fun = $query->func();
				$newPathFunction = $fun->concat(
					$query->createNamedParameter($targetPath),
					$fun->substring('path', $query->createNamedParameter($sourceLength + 1, IQueryBuilder::PARAM_INT))// +1 for the leading slash
				);
				$query->update('filecache')
					->set('storage', $query->createNamedParameter($targetStorageId, IQueryBuilder::PARAM_INT))
					->set('path_hash', $fun->md5($newPathFunction))
					->set('path', $newPathFunction)
					->where($query->expr()->eq('storage', $query->createNamedParameter($sourceStorageId, IQueryBuilder::PARAM_INT)))
					->andWhere($query->expr()->like('path', $query->createNamedParameter($this->connection->escapeLikeParameter($sourcePath) . '/%')));

				// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
				if ($sourceCache->hasEncryptionWrapper() && !$this->hasEncryptionWrapper()) {
					$query->set('encrypted', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT));
				}

				try {
					$query->execute();
				} catch (\OC\DatabaseException $e) {
					$this->connection->rollBack();
					throw $e;
				}
			}

			$query = $this->getQueryBuilder();
			$query->update('filecache')
				->set('storage', $query->createNamedParameter($targetStorageId))
				->set('path', $query->createNamedParameter($targetPath))
				->set('path_hash', $query->createNamedParameter(md5($targetPath)))
				->set('name', $query->createNamedParameter(basename($targetPath)))
				->set('parent', $query->createNamedParameter($newParentId, IQueryBuilder::PARAM_INT))
				->whereFileId($sourceId);

			// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
			if ($sourceCache->hasEncryptionWrapper() && !$this->hasEncryptionWrapper()) {
				$query->set('encrypted', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT));
			}

			$query->execute();

			$this->connection->commit();

			if ($sourceCache->getNumericStorageId() !== $this->getNumericStorageId()) {
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

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$query = $this->getQueryBuilder();
		$query->delete('filecache')
			->whereStorageId($this->getNumericStorageId());
		$query->execute();

		$query = $this->connection->getQueryBuilder();
		$query->delete('storages')
			->where($query->expr()->eq('id', $query->createNamedParameter($this->storageId)));
		$query->execute();
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

		$result = $query->execute();
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
	 *        where it will search for all mimetypes in the group ('image/*')
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

	public function searchQuery(ISearchQuery $searchQuery) {
		return current($this->querySearchHelper->searchInCaches($searchQuery, [$this]));
	}

	/**
	 * Re-calculate the folder size and the size of all parent folders
	 *
	 * @param string|boolean $path
	 * @param array $data (optional) meta data of the folder
	 */
	public function correctFolderSize($path, $data = null, $isBackgroundScan = false) {
		$this->calculateFolderSize($path, $data);
		if ($path !== '') {
			$parent = dirname($path);
			if ($parent === '.' || $parent === '/') {
				$parent = '';
			}
			if ($isBackgroundScan) {
				$parentData = $this->get($parent);
				if ($parentData['size'] !== -1 && $this->getIncompleteChildrenCount($parentData['fileid']) === 0) {
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
				->andWhere($query->expr()->lt('size', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)));

			$result = $query->execute();
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
				->whereParent($id);
			if ($ignoreUnknown) {
				$query->andWhere($query->expr()->gte('size', $query->createNamedParameter(0)));
			}

			$result = $query->execute();
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
			$shouldWriteUnEncryptedSize = $unencryptedMax > 0 || $totalSize === 0 || $entry['unencrypted_size'] > 0;
			if ($entry['size'] !== $totalSize || ($entry['unencrypted_size'] !== $unencryptedTotal && $shouldWriteUnEncryptedSize)) {
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

		$result = $query->execute();
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
		// we select the fileid here first instead of directly selecting the path since this helps mariadb/mysql
		// to use the correct index.
		// The overhead of this should be minimal since the cost of selecting the path by id should be much lower
		// than the cost of finding an item with size < 0
		$query = $this->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->whereStorageId($this->getNumericStorageId())
			->andWhere($query->expr()->lt('size', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->orderBy('fileid', 'DESC')
			->setMaxResults(1);

		$result = $query->execute();
		$id = $result->fetchOne();
		$result->closeCursor();

		if ($id === false) {
			return false;
		}

		$path = $this->getPathById($id);
		return $path ?? false;
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

		$result = $query->execute();
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
	 * @deprecated use getPathById() instead
	 */
	public static function getById($id) {
		$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$query->select('path', 'storage')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		$result = $query->execute();
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
			throw new \RuntimeException("Invalid source cache entry on copyFromCache");
		}
		$data = $this->cacheEntryToArray($sourceEntry);

		// when moving from an encrypted storage to a non-encrypted storage remove the `encrypted` mark
		if ($sourceCache instanceof Cache && $sourceCache->hasEncryptionWrapper() && !$this->hasEncryptionWrapper()) {
			$data['encrypted'] = 0;
		}

		$fileId = $this->put($targetPath, $data);
		if ($fileId <= 0) {
			throw new \RuntimeException("Failed to copy to " . $targetPath . " from cache with source data " . json_encode($data) . " ");
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
		return [
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
}
