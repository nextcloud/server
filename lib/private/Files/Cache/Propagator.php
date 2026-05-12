<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OC\DB\Exceptions\DbalException;
use OC\Files\Storage\LocalRootStorage;
use OC\Files\Storage\Wrapper\Encryption;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Storage\IReliableEtagStorage;
use OCP\IDBConnection;
use OCP\Server;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Propagate etags and mtimes within the storage
 */
class Propagator implements IPropagator {
	public const MAX_RETRIES = 3;
	private $inBatch = false;

	private $batch = [];

	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var array
	 */
	private $ignore = [];

	private ClockInterface $clock;

	public function __construct(\OC\Files\Storage\Storage $storage, IDBConnection $connection, array $ignore = []) {
		$this->storage = $storage;
		$this->connection = $connection;
		$this->ignore = $ignore;
		$this->clock = Server::get(ClockInterface::class);
	}

	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference number of bytes the file has grown
	 */
	public function propagateChange($internalPath, $time, $sizeDifference = 0) {
		// Do not propagate changes in ignored paths
		foreach ($this->ignore as $ignore) {
			if (str_starts_with($internalPath, $ignore)) {
				return;
			}
		}

		$time = min((int)$time, $this->clock->now()->getTimestamp());

		$storageId = $this->storage->getStorageCache()->getNumericId();

		$parents = $this->getParents($internalPath);
		if ($this->storage->instanceOfStorage(LocalRootStorage::class)) {
			if (str_starts_with($internalPath, '__groupfolders/versions') || str_starts_with($internalPath, '__groupfolders/trash')) {
				// Remove '', '__groupfolders' and '__groupfolders/versions' or '__groupfolders/trash'
				$parents = array_slice($parents, 3);
			} elseif (str_starts_with($internalPath, '__groupfolders')) {
				// Remove '' and '__groupfolders'
				$parents = array_slice($parents, 2);
			}
		}

		if ($parents === []) {
			return;
		}

		if ($this->inBatch) {
			foreach ($parents as $parent) {
				$this->addToBatch($parent, $time, $sizeDifference);
			}
			return;
		}

		$parentHashes = array_map('md5', $parents);
		sort($parentHashes); // Ensure rows are always locked in the same order
		$etag = uniqid(); // since we give all folders the same etag we don't ask the storage for the etag

		$builder = $this->connection->getQueryBuilder();
		$hashParams = array_map(static fn (string $hash): ILiteral => $builder->expr()->literal($hash), $parentHashes);

		$builder->update('filecache')
			->set('mtime', $builder->func()->greatest('mtime', $builder->createNamedParameter($time, IQueryBuilder::PARAM_INT)))
			->where($builder->expr()->eq('storage', $builder->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere($builder->expr()->in('path_hash', $hashParams));
		if (!$this->storage->instanceOfStorage(IReliableEtagStorage::class)) {
			$builder->set('etag', $builder->createNamedParameter($etag, IQueryBuilder::PARAM_STR));
		}

		if ($sizeDifference !== 0) {
			$hasCalculatedSize = $builder->expr()->gt('size', $builder->expr()->literal(-1, IQUeryBuilder::PARAM_INT));
			$sizeColumn = $builder->getColumnName('size');
			$newSize = $builder->func()->greatest(
				$builder->func()->add('size', $builder->createNamedParameter($sizeDifference)),
				$builder->createNamedParameter(-1, IQueryBuilder::PARAM_INT)
			);

			// Only update if row had a previously calculated size
			$builder->set('size', $builder->createFunction("CASE WHEN $hasCalculatedSize THEN $newSize ELSE $sizeColumn END"));

			if ($this->storage->instanceOfStorage(Encryption::class)) {
				// in case of encryption being enabled after some files are already uploaded, some entries will have an unencrypted_size of 0 and a non-zero size
				$hasUnencryptedSize = $builder->expr()->neq('unencrypted_size', $builder->expr()->literal(0, IQueryBuilder::PARAM_INT));
				$sizeColumn = $builder->getColumnName('size');
				$unencryptedSizeColumn = $builder->getColumnName('unencrypted_size');
				$newUnencryptedSize = $builder->func()->greatest(
					$builder->func()->add(
						$builder->createFunction("CASE WHEN $hasUnencryptedSize THEN $unencryptedSizeColumn ELSE $sizeColumn END"),
						$builder->createNamedParameter($sizeDifference)
					),
					$builder->createNamedParameter(-1, IQueryBuilder::PARAM_INT)
				);

				// Only update if row had a previously calculated size
				$builder->set('unencrypted_size', $builder->createFunction("CASE WHEN $hasCalculatedSize THEN $newUnencryptedSize ELSE $unencryptedSizeColumn END"));
			}
		}

		for ($i = 0; $i < self::MAX_RETRIES; $i++) {
			try {
				if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_SQLITE) {
					$this->connection->beginTransaction();
					// Lock all the rows first with a SELECT FOR UPDATE ordered by path_hash
					$forUpdate = $this->connection->getQueryBuilder();
					$forUpdate->select('fileid')
						->from('filecache')
						->where($forUpdate->expr()->eq('storage', $forUpdate->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($forUpdate->expr()->in('path_hash', $hashParams))
						->orderBy('path_hash')
						->forUpdate()
						->executeQuery();
					$builder->executeStatement();
					$this->connection->commit();
				} else {
					$builder->executeStatement();
				}
				break;
			} catch (DbalException $e) {
				if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_SQLITE) {
					$this->connection->rollBack();
				}
				if (!$e->isRetryable()) {
					throw $e;
				}

				/** @var LoggerInterface $loggerInterface */
				$loggerInterface = \OCP\Server::get(LoggerInterface::class);
				$loggerInterface->warning('Retrying propagation query after retryable exception.', [ 'exception' => $e ]);
			}
		}
	}

	protected function getParents($path) {
		$parts = explode('/', $path);
		$parent = '';
		$parents = [];
		foreach ($parts as $part) {
			$parents[] = $parent;
			$parent = trim($parent . '/' . $part, '/');
		}
		return $parents;
	}

	/**
	 * Mark the beginning of a propagation batch
	 *
	 * Note that not all cache setups support propagation in which case this will be a noop
	 *
	 * Batching for cache setups that do support it has to be explicit since the cache state is not fully consistent
	 * before the batch is committed.
	 */
	public function beginBatch() {
		$this->inBatch = true;
	}

	private function addToBatch($internalPath, $time, $sizeDifference) {
		if (!isset($this->batch[$internalPath])) {
			$this->batch[$internalPath] = [
				'hash' => md5($internalPath),
				'time' => $time,
				'size' => $sizeDifference,
			];
		} else {
			$this->batch[$internalPath]['size'] += $sizeDifference;
			if ($time > $this->batch[$internalPath]['time']) {
				$this->batch[$internalPath]['time'] = $time;
			}
		}
	}

	/**
	 * Commit the active propagation batch
	 */
	public function commitBatch() {
		if (!$this->inBatch) {
			throw new \BadMethodCallException('Not in batch');
		}
		$this->inBatch = false;

		// Ensure rows are always locked in the same order
		uasort($this->batch, static fn (array $a, array $b) => $a['hash'] <=> $b['hash']);

		try {
			$this->connection->beginTransaction();

			$query = $this->connection->getQueryBuilder();
			$storageId = (int)$this->storage->getStorageCache()->getNumericId();

			if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_SQLITE) {
				// Lock the rows before updating then with a SELECT FOR UPDATE
				// The select also allow us to fetch the fileid and then use these in the UPDATE
				// queries as a faster lookup than the path_hash
				$hashes = array_map(static fn (array $a): string => $a['hash'], $this->batch);

				foreach (array_chunk($hashes, 1000) as $hashesChunk) {
					$query = $this->connection->getQueryBuilder();
					$result = $query->select('fileid', 'path', 'path_hash', 'size')
						->from('filecache')
						->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($query->expr()->in('path_hash', $query->createNamedParameter($hashesChunk, IQueryBuilder::PARAM_STR_ARRAY)))
						->orderBy('path_hash')
						->forUpdate()
						->executeQuery();

					$query = $this->connection->getQueryBuilder();
					$query->update('filecache')
						->set('mtime', $query->func()->greatest('mtime', $query->createParameter('time')))
						->set('etag', $query->expr()->literal(uniqid()))
						->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($query->expr()->eq('fileid', $query->createParameter('fileid')));

					$queryWithSize = $this->connection->getQueryBuilder();
					$queryWithSize->update('filecache')
						->set('mtime', $queryWithSize->func()->greatest('mtime', $queryWithSize->createParameter('time')))
						->set('etag', $queryWithSize->expr()->literal(uniqid()))
						->set('size', $queryWithSize->func()->add('size', $queryWithSize->createParameter('size')))
						->where($queryWithSize->expr()->eq('storage', $queryWithSize->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
						->andWhere($queryWithSize->expr()->eq('fileid', $queryWithSize->createParameter('fileid')));

					while ($row = $result->fetchAssociative()) {
						$item = $this->batch[$row['path']];
						if ($item['size'] && $row['size'] > -1) {
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
					->set('etag', $query->expr()->literal(uniqid()))
					->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
					->andWhere($query->expr()->eq('path_hash', $query->createParameter('hash')));

				$queryWithSize = $this->connection->getQueryBuilder();
				$queryWithSize->update('filecache')
					->set('mtime', $queryWithSize->func()->greatest('mtime', $queryWithSize->createParameter('time')))
					->set('etag', $queryWithSize->expr()->literal(uniqid()))
					->set('size', $queryWithSize->func()->add('size', $queryWithSize->createParameter('size')))
					->where($queryWithSize->expr()->eq('storage', $queryWithSize->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
					->andWhere($queryWithSize->expr()->eq('path_hash', $queryWithSize->createParameter('hash')));

				foreach ($this->batch as $item) {
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

			$this->batch = [];

			$this->connection->commit();
		} catch (\Exception $e) {
			$this->connection->rollback();
			throw $e;
		}
	}
}
