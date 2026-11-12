<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Lock;

use OC\DB\QueryBuilder\Literal;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Lock\LockedException;

/**
 * Locking provider that stores locks in the database.
 */
class DBLockingProvider extends AbstractLockingProvider {
	/** @var array<string, bool> */
	private array $sharedLocks = [];

	public function __construct(
		private IDBConnection $connection,
		private ITimeFactory $timeFactory,
		int $ttl = 3600,
		private bool $cacheSharedLocks = true,
	) {
		parent::__construct($ttl);
	}

	/**
	 * Whether this request tracks a shared lock retained in the database.
	 */
	protected function isLocallyLocked(string $path): bool {
		return $this->sharedLocks[$path] ?? false;
	}

	#[\Override]
	protected function markAcquire(string $path, int $targetType): void {
		parent::markAcquire($path, $targetType);

		if (!$this->cacheSharedLocks) {
			return;
		}

		if ($targetType === self::LOCK_SHARED) {
			$this->sharedLocks[$path] = true;
		}
	}

	#[\Override]
	protected function markChange(string $path, int $targetType): void {
		parent::markChange($path, $targetType);

		if (!$this->cacheSharedLocks) {
			return;
		}

		if ($targetType === self::LOCK_SHARED) {
			$this->sharedLocks[$path] = true;
		} elseif ($targetType === self::LOCK_EXCLUSIVE) {
			$this->sharedLocks[$path] = false;
		}
	}

	/**
	 * Insert a file-lock row if it does not already exist.
	 */
	protected function initLockField(string $path, int $lock = 0): int {
		$expire = $this->getExpireTime();
		return $this->connection->insertIgnoreConflict('file_locks', [
			'key' => $path,
			'lock' => $lock,
			'ttl' => $expire,
		]);
	}

	protected function getExpireTime(): int {
		return $this->timeFactory->getTime() + $this->ttl;
	}

	#[\Override]
	public function isLocked(string $path, int $type): bool {
		$this->assertValidLockType($type);

		if ($this->hasAcquiredLock($path, $type)) {
			return true;
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('lock')
			->from('file_locks')
			->where($query->expr()->eq('key', $query->createNamedParameter($path)));

		$result = $query->executeQuery();
		$lockValue = (int)$result->fetchOne();

		if ($type === self::LOCK_SHARED) {
			// A shared lock retained for this request remains in the database after it is
			// released from active bookkeeping.
			return $this->isLocallyLocked($path)
				? $lockValue > 1
				: $lockValue > 0;
		}

		return $lockValue === -1;
	}

	#[\Override]
	public function acquireLock(string $path, int $type, ?string $readablePath = null): void {
		$this->assertValidLockType($type);

		$expire = $this->getExpireTime();

		if ($type === self::LOCK_SHARED) {
			if (!$this->isLocallyLocked($path)) {
				$result = $this->initLockField($path, 1);

				if ($result <= 0) {
					$query = $this->connection->getQueryBuilder();
					$query->update('file_locks')
						->set('lock', $query->func()->add('lock', $query->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
						->set('ttl', $query->createNamedParameter($expire))
						->where($query->expr()->eq('key', $query->createNamedParameter($path)))
						->andWhere($query->expr()->gte('lock', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
					$result = $query->executeStatement();
				}
			} else {
				$result = 1;
			}
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			$existing = 0;

			// A shared lock retained for this request may remain in the database after it
			// has been released from active bookkeeping.
			if (
				$this->hasAcquiredLock($path, self::LOCK_SHARED) === false
				&& $this->isLocallyLocked($path)
			) {
				$existing = 1;
			}

			$result = $this->initLockField($path, -1);

			if ($result <= 0) {
				$query = $this->connection->getQueryBuilder();
				$query->update('file_locks')
					->set('lock', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT))
					->set('ttl', $query->createNamedParameter($expire, IQueryBuilder::PARAM_INT))
					->where($query->expr()->eq('key', $query->createNamedParameter($path)))
					->andWhere($query->expr()->eq('lock', $query->createNamedParameter($existing)));
				$result = $query->executeStatement();
			}
		}

		if ($result !== 1) {
			throw new LockedException($path, null, null, $readablePath);
		}

		$this->markAcquire($path, $type);
	}

	#[\Override]
	public function releaseLock(string $path, int $type): void {
		$this->assertValidLockType($type);

		$this->markRelease($path, $type);

		// Shared locks remain in the database until the end of the request so they
		// can be reused.
		if ($type === self::LOCK_EXCLUSIVE) {
			$query = $this->connection->getQueryBuilder();
			$query->update('file_locks')
				->set('lock', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('key', $query->createNamedParameter($path)))
				->andWhere($query->expr()->eq('lock', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));
			$query->executeStatement();
		} elseif (!$this->cacheSharedLocks) {
			$query = $this->connection->getQueryBuilder();
			$query->update('file_locks')
				->set('lock', $query->func()->subtract('lock', $query->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
				->where($query->expr()->eq('key', $query->createNamedParameter($path)))
				->andWhere($query->expr()->gt('lock', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
			$query->executeStatement();
		}
	}

	#[\Override]
	public function changeLock(string $path, int $targetType): void {
		$this->assertValidLockType($targetType);

		$expire = $this->getExpireTime();

		if ($targetType === self::LOCK_SHARED) {
			$query = $this->connection->getQueryBuilder();
			$query->update('file_locks')
				->set('lock', $query->createNamedParameter(1, IQueryBuilder::PARAM_INT))
				->set('ttl', $query->createNamedParameter($expire, IQueryBuilder::PARAM_INT))
				->where($query->expr()->andX(
					$query->expr()->eq('key', $query->createNamedParameter($path)),
					$query->expr()->eq('lock', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT))));
			$result = $query->executeStatement();
		} elseif ($targetType === self::LOCK_EXCLUSIVE) {
			// The database retains one shared lock per request, so an upgrade is only
			// possible when this request holds exactly one shared lock.
			if ($this->getOwnSharedLockCount($path) > 1) {
				throw new LockedException($path);
			}

			$query = $this->connection->getQueryBuilder();
			$query->update('file_locks')
				->set('lock', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT))
				->set('ttl', $query->createNamedParameter($expire, IQueryBuilder::PARAM_INT))
				->where($query->expr()->andX(
					$query->expr()->eq('key', $query->createNamedParameter($path)),
					$query->expr()->eq('lock', $query->createNamedParameter(1, IQueryBuilder::PARAM_INT))));
			$result = $query->executeStatement();
		}

		if ($result !== 1) {
			throw new LockedException($path);
		}

		$this->markChange($path, $targetType);
	}

	public function cleanExpiredLocks(): void {
		$expire = $this->timeFactory->getTime();

		try {
			$query = $this->connection->getQueryBuilder();
			$query->delete('file_locks')
				->where($query->expr()->lt('ttl', $query->createNamedParameter($expire, IQueryBuilder::PARAM_INT)));
			$query->executeStatement();
		} catch (\Exception $e) {
			// If the table is missing, cleanup was successful.
			if ($this->connection->tableExists('file_locks')) {
				throw $e;
			}
		}
	}

	#[\Override]
	public function releaseAll(): void {
		parent::releaseAll();

		if (!$this->cacheSharedLocks) {
			return;
		}

		// Shared locks remain in the database until the end of the request.
		$lockedPaths = array_keys(array_filter($this->sharedLocks));
		$chunkedPaths = array_chunk($lockedPaths, 100);

		$query = $this->connection->getQueryBuilder();
		$query->update('file_locks')
			->set('lock', $query->func()->subtract('lock', $query->expr()->literal(1)))
			->where($query->expr()->in('key', $query->createParameter('chunk')))
			->andWhere($query->expr()->gt('lock', new Literal(0)));

		foreach ($chunkedPaths as $chunk) {
			$query->setParameter('chunk', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$query->executeStatement();
		}
	}
}
