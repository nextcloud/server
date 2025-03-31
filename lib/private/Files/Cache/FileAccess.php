<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

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

	/**
	 * Retrieves files stored in a specific storage that have a specified ancestor in the file hierarchy.
	 * Allows filtering by mime types, encryption status, and limits the number of results.
	 *
	 * @param int $storageId The ID of the storage to search within.
	 * @param int $rootId The file ID of the ancestor to base the search on.
	 * @param int $lastFileId The last processed file ID. Only files with a higher ID will be included. Defaults to 0.
	 * @param list<int> $mimeTypes An array of mime types to filter the results. If empty, no mime type filtering will be applied.
	 * @param bool $endToEndEncrypted Whether to include EndToEndEncrypted files
	 * @param bool $serverSideEncrypted Whether to include ServerSideEncrypted files
	 * @param int $maxResults The maximum number of results to retrieve. If set to 0, all matching files will be retrieved.
	 * @return \Generator A generator yielding matching files as cache entries.
	 * @throws Exception
	 */
	public function getByAncestorInStorage(int $storageId, int $rootId, int $lastFileId = 0, array $mimeTypes = [], bool $endToEndEncrypted = true, bool $serverSideEncrypted = true, int $maxResults = 100): \Generator {
		$qb = $this->getQuery();
		$qb->selectFileCache();
		$qb->andWhere($qb->expr()->eq('filecache.fileid', $qb->createNamedParameter($rootId, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		/** @var array{path:string}|false $root */
		$root = $result->fetch();
		$result->closeCursor();

		if ($root === false) {
			throw new Exception('Could not fetch storage root');
		}

		$qb = $this->getQuery();

		$path = $root['path'] === '' ? '' : $root['path'] . '/';

		$qb->select('*')
			->from('filecache', 'filecache')
			->andWhere($qb->expr()->like('filecache.path', $qb->createNamedParameter($path . '%')))
			->andWhere($qb->expr()->eq('filecache.storage', $qb->createNamedParameter($storageId)))
			->andWhere($qb->expr()->gt('filecache.fileid', $qb->createNamedParameter($lastFileId)));

		if (!$endToEndEncrypted) {
			$qb->innerJoin('filecache', 'filecache', 'p', $qb->expr()->eq('filecache.parent', 'p.fileid'));
			$qb->andWhere($qb->expr()->eq('p.encrypted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		}

		if (!$serverSideEncrypted) {
			$qb->andWhere($qb->expr()->eq('filecache.encrypted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		}

		if (count($mimeTypes) > 0) {
			$qb->andWhere($qb->expr()->in('filecache.mimetype', $qb->createNamedParameter($mimeTypes, IQueryBuilder::PARAM_INT_ARRAY)));
		}

		if ($maxResults !== 0) {
			$qb->setMaxResults($maxResults);
		}
		$files = $qb->orderBy('filecache.fileid', 'ASC')
			->executeQuery();

		while (
			/** @var array */
			$row = $files->fetch()
		) {
			yield Cache::cacheEntryFromData($row, $this->mimeTypeLoader);
		}

		$files->closeCursor();
	}

	/**
	 * Retrieves a list of all distinct mounts.
	 * Allows filtering by specific mount providers and excluding certain mount points.
	 * Optionally rewrites home directory root paths to avoid cache and trashbin.
	 *
	 * @param list<string> $mountProviders An array of mount provider class names to filter. If empty, all providers will be included.
	 * @param string|false $excludeMountPoints A string pattern to exclude mount points. Set to false to not exclude any mount points.
	 * @param bool $rewriteHomeDirectories Whether to rewrite the root path IDs for home directories to only include user files.
	 * @return \Generator A generator yielding mount configurations as an array containing 'storage_id', 'root_id', and 'override_root'.
	 * @throws Exception
	 */
	public function getDistinctMounts(array $mountProviders = [], string|false $excludeMountPoints = false, bool $rewriteHomeDirectories = true): \Generator {
		$qb = $this->connection->getQueryBuilder();
		$qb->selectDistinct(['root_id', 'storage_id', 'mount_provider_class'])
			->from('mounts');
		if (count($mountProviders) > 0) {
			$qb->where($qb->expr()->in('mount_provider_class', $qb->createPositionalParameter($mountProviders, IQueryBuilder::PARAM_STR_ARRAY)));
		}
		if ($excludeMountPoints !== false) {
			$qb->andWhere($qb->expr()->notLike('mount_point', $qb->createPositionalParameter($excludeMountPoints)));
		}
		$result = $qb->executeQuery();


		while (
			/** @var array{storage_id:int, root_id:int,mount_provider_class:string} $row */
			$row = $result->fetch()
		) {
			$storageId = (int)$row['storage_id'];
			$rootId = (int)$row['root_id'];
			$overrideRoot = $rootId;
			if (in_array($row['mount_provider_class'], [
				OC\Files\Mount\LocalHomeMountProvider::class,
				OC\Files\Mount\ObjectHomeMountProvider::class,
			])) {
				// Only crawl files, not cache or trashbin
				$qb = $this->getQuery();
				try {
					$qb->selectFileCache();
					/** @var array|false $root */
					$root = $qb
						->andWhere($qb->expr()->eq('filecache.storage', $qb->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($qb->expr()->eq('filecache.path', $qb->createNamedParameter('files')))
						->executeQuery()->fetch();
					if ($root !== false) {
						$overrideRoot = intval($root['fileid']);
					}
				} catch (Exception $e) {
					$this->logger->error('Could not fetch home storage files root for storage ' . $storageId, ['exception' => $e]);
					continue;
				}
			}
			yield [
				'storage_id' => $storageId,
				'root_id' => $rootId,
				'override_root' => $overrideRoot,
			];
		}
		$result->closeCursor();
	}
}
