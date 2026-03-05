<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

use OC\Files\Mount\LocalHomeMountProvider;
use OC\Files\Mount\ObjectHomeMountProvider;
use OC\FilesMetadata\FilesMetadataManager;
use OC\SystemConfig;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\IFileAccess;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Low level access to the file cache
 */
class FileAccess implements IFileAccess {
	public function __construct(
		private IDBConnection $connection,
		private SystemConfig $systemConfig,
		private LoggerInterface $logger,
		private FilesMetadataManager $metadataManager,
		private IMimeTypeLoader $mimeTypeLoader,
	) {
	}

	private function getQuery(): CacheQueryBuilder {
		return new CacheQueryBuilder(
			$this->connection->getQueryBuilder(),
			$this->metadataManager,
		);
	}

	public function getByFileIdInStorage(int $fileId, int $storageId): ?CacheEntry {
		$items = array_values($this->getByFileIdsInStorage([$fileId], $storageId));
		return $items[0] ?? null;
	}

	public function getByPathInStorage(string $path, int $storageId): ?CacheEntry {
		$query = $this->getQuery()->selectFileCache();
		$query->andWhere($query->expr()->eq('filecache.path_hash', $query->createNamedParameter(md5($path))));
		$query->andWhere($query->expr()->eq('filecache.storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));

		$row = $query->executeQuery()->fetch();
		return $row ? Cache::cacheEntryFromData($row, $this->mimeTypeLoader) : null;
	}

	public function getByFileId(int $fileId): ?CacheEntry {
		$items = array_values($this->getByFileIds([$fileId]));
		return $items[0] ?? null;
	}

	/**
	 * @param array[] $rows
	 * @return array<int, CacheEntry>
	 */
	private function rowsToEntries(array $rows): array {
		$result = [];
		foreach ($rows as $row) {
			$entry = Cache::cacheEntryFromData($row, $this->mimeTypeLoader);
			$result[$entry->getId()] = $entry;
		}
		return $result;
	}

	/**
	 * @param int[] $fileIds
	 * @return array<int, CacheEntry>
	 */
	public function getByFileIds(array $fileIds): array {
		$query = $this->getQuery()->selectFileCache();
		$query->andWhere($query->expr()->in('filecache.fileid', $query->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));

		$rows = $query->executeQuery()->fetchAll();
		return $this->rowsToEntries($rows);
	}

	/**
	 * @param int[] $fileIds
	 * @param int $storageId
	 * @return array<int, CacheEntry>
	 */
	public function getByFileIdsInStorage(array $fileIds, int $storageId): array {
		$fileIds = array_values($fileIds);
		$query = $this->getQuery()->selectFileCache();
		$query->andWhere($query->expr()->in('filecache.fileid', $query->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$query->andWhere($query->expr()->eq('filecache.storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));

		$rows = $query->executeQuery()->fetchAll();
		return $this->rowsToEntries($rows);
	}

	public function getByAncestorInStorage(int $storageId, int $folderId, int $fileIdCursor = 0, int $maxResults = 100, array $mimeTypeIds = [], bool $endToEndEncrypted = true, bool $serverSideEncrypted = true): \Generator {
		$qb = $this->getQuery();
		$qb->select('path')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($folderId, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		/** @var array{path:string}|false $root */
		$root = $result->fetch();
		$result->closeCursor();

		if ($root === false) {
			throw new Exception('Could not fetch storage root');
		}

		$qb = $this->getQuery();

		$path = $root['path'] === '' ? '' : $root['path'] . '/';

		$qb->selectDistinct('f.*')
			->from('filecache', 'f')
			->where($qb->expr()->like('f.path', $qb->createNamedParameter($this->connection->escapeLikeParameter($path) . '%')))
			->andWhere($qb->expr()->eq('f.storage', $qb->createNamedParameter($storageId)))
			->andWhere($qb->expr()->gt('f.fileid', $qb->createNamedParameter($fileIdCursor, IQueryBuilder::PARAM_INT)))
			->hintShardKey('storage', $storageId);

		if (!$endToEndEncrypted && $this->connection->getShardDefinition('filecache') === null) {
			// End to end encrypted files are descendants of a folder with encrypted=1
			// We can only do this inner join if the filecache table is not sharded
			$qb->innerJoin('f', 'filecache', 'f2', $qb->expr()->eq('f2.fileid', 'f.parent'));

			$qb->andWhere(
				$qb->expr()->eq('f2.encrypted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			);
		}

		if (!$serverSideEncrypted) {
			// Server side encrypted files have encrypted=1 directly
			$qb->andWhere($qb->expr()->eq('f.encrypted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		}

		if (count($mimeTypeIds) > 0) {
			$qb->andWhere($qb->expr()->in('f.mimetype', $qb->createNamedParameter($mimeTypeIds, IQueryBuilder::PARAM_INT_ARRAY)));
		}

		if ($maxResults !== 0) {
			$qb->setMaxResults($maxResults);
		}
		$qb->orderBy('f.fileid', 'ASC');
		$files = $qb->executeQuery();

		if (!$endToEndEncrypted && $this->connection->getShardDefinition('filecache') !== null) {
			// End to end encrypted files are descendants of a folder with encrypted=1
			// If the filecache table is sharded we need to check with a separate query if the parent is encrypted
			$rows = [];
			do {
				while (count($rows) < 1000 && ($row = $files->fetch())) {
					$rows[] = $row;
				}
				$parents = array_map(function ($row) {
					return $row['parent'];
				}, $rows);

				$parentQuery = $this->getQuery();
				$parentQuery->select('fileid', 'encrypted')->from('filecache');
				$parentQuery->where($parentQuery->expr()->in('fileid', $parentQuery->createNamedParameter($parents, IQueryBuilder::PARAM_INT_ARRAY)));
				$parentQuery->hintShardKey('storage', $storageId);
				$result = $parentQuery->executeQuery();
				$parentRows = $result->fetchAll();
				$result->closeCursor();

				$encryptedByFileId = array_column($parentRows, 'encrypted', 'fileid');
				foreach ($rows as $row) {
					if ($encryptedByFileId[$row['parent']]) {
						continue;
					}
					yield Cache::cacheEntryFromData($row, $this->mimeTypeLoader);
				}
				$rows = [];
			} while ($rows[] = $files->fetch());
		} else {
			while (
				/** @var array */
				$row = $files->fetch()
			) {
				yield Cache::cacheEntryFromData($row, $this->mimeTypeLoader);
			}
		}

		$files->closeCursor();
	}

	public function getDistinctMounts(array $mountProviders = [], bool $onlyUserFilesMounts = true): \Generator {
		$qb = $this->connection->getQueryBuilder();
		$qb->selectDistinct(['root_id', 'storage_id', 'mount_provider_class'])
			->from('mounts');
		if ($onlyUserFilesMounts) {
			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->like('mount_point', $qb->createNamedParameter('/%/files/%')),
					$qb->expr()->in('mount_provider_class', $qb->createNamedParameter([
						LocalHomeMountProvider::class,
						ObjectHomeMountProvider::class,
					], IQueryBuilder::PARAM_STR_ARRAY))
				)
			);
		}
		if (count($mountProviders) > 0) {
			$qb->andWhere($qb->expr()->in('mount_provider_class', $qb->createNamedParameter($mountProviders, IQueryBuilder::PARAM_STR_ARRAY)));
		}
		$qb->orderBy('root_id', 'ASC');
		$result = $qb->executeQuery();

		while (
			/** @var array{storage_id:int, root_id:int,mount_provider_class:string} $row */
			$row = $result->fetch()
		) {
			$storageId = (int)$row['storage_id'];
			$rootId = (int)$row['root_id'];
			$overrideRoot = $rootId;
			// LocalHomeMountProvider is the default provider for user home directories
			// ObjectHomeMountProvider is the home directory provider for when S3 primary storage is used
			if ($onlyUserFilesMounts && in_array($row['mount_provider_class'], [
				LocalHomeMountProvider::class,
				ObjectHomeMountProvider::class,
			], true)) {
				// Only crawl files, not cache or trashbin
				$qb = $this->getQuery();
				try {
					$qb->select('fileid')
						->from('filecache')
						->where($qb->expr()->eq('storage', $qb->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($rootId, IQueryBuilder::PARAM_INT)))
						->andWhere($qb->expr()->eq('path', $qb->createNamedParameter('files')));
					/** @var array|false $root */
					$root = $qb->executeQuery()->fetch();
					if ($root !== false) {
						$overrideRoot = (int)$root['fileid'];
					}
				} catch (Exception $e) {
					$this->logger->error('Could not fetch home storage files root for storage ' . $storageId, ['exception' => $e]);
					continue;
				}
			}
			// Reference to root_id is still necessary even if we have the overridden_root_id, because storage_id and root_id uniquely identify a mount
			yield [
				'storage_id' => $storageId,
				'root_id' => $rootId,
				'overridden_root' => $overrideRoot,
			];
		}
		$result->closeCursor();
	}
}
