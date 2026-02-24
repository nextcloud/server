<?php
declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Cache;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class BatchPropagator {
	/** @var array<int, array<string, array{hash: string, time: int, size: int}>> */
	private array $parents = [];

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	public function __destruct() {
		$this->commit();
	}

	public function addParent(int $storageId, string $path, int $time, int $sizeDifference): void {
		$this->parents[$storageId] ??= [];
		if (!isset($this->parents[$storageId][$path])) {
			$this->parents[$storageId][$path] = [
				'hash' => md5($path),
				'time' => $time,
				'size' => $sizeDifference,
			];
		} else {
			$this->parents[$storageId][$path]['size'] += $sizeDifference;
			$this->parents[$storageId][$path]['time'] = max($this->parents[$storageId][$path]['time'], $time);
		}
	}

	public function commit(): void {
		// Ensure rows are always locked in the same order
		uksort($this->parents, static fn(string $a, string $b) => $a <=> $b);
		foreach ($this->parents as &$paths) {
			uasort($paths, static fn(array $a, array $b) => $a['hash'] <=> $b['hash']);
		}
		unset($paths);

		$etag = uniqid();

		try {
			$this->connection->beginTransaction();

			foreach ($this->parents as $storageId => $parents) {
				if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_SQLITE) {
					// Lock the rows before updating then with a SELECT FOR UPDATE
					// The select also allow us to fetch the fileid and then use these in the UPDATE
					// queries as a faster lookup than the path_hash
					$hashes = array_map(static fn(array $a): string => $a['hash'], $parents);

					foreach (array_chunk($hashes, 1000) as $hashesChunk) {
						$query = $this->connection->getQueryBuilder();
						$result = $query->select('storage', 'fileid', 'path', 'path_hash', 'size')
							->from('filecache')
							->orWhere()
							->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
							->andWhere($query->expr()->in('path_hash', $query->createNamedParameter($hashesChunk, IQueryBuilder::PARAM_STR_ARRAY)))
							->orderBy('path_hash')
							->forUpdate()
							->executeQuery();

						$query = $this->connection->getQueryBuilder();
						$query->update('filecache')
							->set('mtime', $query->func()->greatest('mtime', $query->createParameter('time')))
							->set('etag', $query->expr()->literal($etag))
							->where($query->expr()->eq('fileid', $query->createParameter('fileid')));

						$queryWithSize = $this->connection->getQueryBuilder();
						$queryWithSize->update('filecache')
							->set('mtime', $queryWithSize->func()->greatest('mtime', $queryWithSize->createParameter('time')))
							->set('etag', $queryWithSize->expr()->literal($etag))
							->set('size', $queryWithSize->func()->add('size', $queryWithSize->createParameter('size')))
							->where($queryWithSize->expr()->eq('fileid', $queryWithSize->createParameter('fileid')));

						while ($row = $result->fetchAssociative()) {
							$item = $this->parents[$row['storage']][$row['path']];
							if ($row['size'] > -1) {
								$queryWithSize->setParameter('fileid', $row['fileid'], IQueryBuilder::PARAM_INT)
									->setParameter('size', $item['size'], IQueryBuilder::PARAM_INT)
									->setParameter('time', $item['time'], IQueryBuilder::PARAM_INT)
									->executeStatement();
							} else {
								$query->setParameter('fileid', $row['fileid'], IQueryBuilder::PARAM_INT)
									->setParameter('time', $item['time'], IQueryBuilder::PARAM_INT)
									->executeStatement();
							}
						}
					}
				} else {
					// No FOR UPDATE support in Sqlite, but instead the whole table is locked
					$query = $this->connection->getQueryBuilder();
					$query->update('filecache')
						->set('mtime', $query->func()->greatest('mtime', $query->createParameter('time')))
						->set('etag', $query->expr()->literal($etag))
						->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($query->expr()->eq('path_hash', $query->createParameter('hash')));

					$queryWithSize = $this->connection->getQueryBuilder();
					$queryWithSize->update('filecache')
						->set('mtime', $queryWithSize->func()->greatest('mtime', $queryWithSize->createParameter('time')))
						->set('etag', $queryWithSize->expr()->literal($etag))
						->set('size', $queryWithSize->func()->add('size', $queryWithSize->createParameter('size')))
						->where($queryWithSize->expr()->eq('storage', $queryWithSize->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($queryWithSize->expr()->eq('path_hash', $queryWithSize->createParameter('hash')));

					foreach ($parents as $item) {
						if ($item['size']) {
							$queryWithSize->setParameter('hash', $item['hash'], IQueryBuilder::PARAM_STR)
								->setParameter('time', $item['time'], IQueryBuilder::PARAM_INT)
								->setParameter('size', $item['size'], IQueryBuilder::PARAM_INT)
								->executeStatement();
						} else {
							$query->setParameter('hash', $item['hash'], IQueryBuilder::PARAM_STR)
								->setParameter('time', $item['time'], IQueryBuilder::PARAM_INT)
								->executeStatement();
						}
					}
				}
			}

			$this->parents = [];

			$this->connection->commit();
		} catch (\Exception $e) {
			$this->connection->rollback();
			throw $e;
		}
	}
}
