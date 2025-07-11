<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OC\DB\Exceptions\DbalException;
use OC\Files\Storage\Wrapper\Encryption;
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

		if ($this->inBatch) {
			foreach ($parents as $parent) {
				$this->addToBatch($parent, $time, $sizeDifference);
			}
			return;
		}

		$parentHashes = array_map('md5', $parents);
		$etag = uniqid(); // since we give all folders the same etag we don't ask the storage for the etag

		$builder = $this->connection->getQueryBuilder();
		$hashParams = array_map(function ($hash) use ($builder) {
			return $builder->expr()->literal($hash);
		}, $parentHashes);

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
				$builder->executeStatement();
				break;
			} catch (DbalException $e) {
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

		$this->connection->beginTransaction();

		$query = $this->connection->getQueryBuilder();
		$storageId = (int)$this->storage->getStorageCache()->getNumericId();

		$query->update('filecache')
			->set('mtime', $query->func()->greatest('mtime', $query->createParameter('time')))
			->set('etag', $query->expr()->literal(uniqid()))
			->where($query->expr()->eq('storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('path_hash', $query->createParameter('hash')));

		$sizeQuery = $this->connection->getQueryBuilder();
		$sizeQuery->update('filecache')
			->set('size', $sizeQuery->func()->add('size', $sizeQuery->createParameter('size')))
			->where($query->expr()->eq('storage', $sizeQuery->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('path_hash', $sizeQuery->createParameter('hash')))
			->andWhere($sizeQuery->expr()->gt('size', $sizeQuery->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));

		foreach ($this->batch as $item) {
			$query->setParameter('time', $item['time'], IQueryBuilder::PARAM_INT);
			$query->setParameter('hash', $item['hash']);

			$query->executeStatement();

			if ($item['size']) {
				$sizeQuery->setParameter('size', $item['size'], IQueryBuilder::PARAM_INT);
				$sizeQuery->setParameter('hash', $item['hash']);

				$sizeQuery->executeStatement();
			}
		}

		$this->batch = [];

		$this->connection->commit();
	}
}
