<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Individual IT Services <info@individual-it.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Ole Ostergaard <ole.c.ostergaard@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OC\Lock;

use OC\DB\QueryBuilder\Literal;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

/**
 * Locking provider that stores the locks in the database
 */
class DBLockingProvider extends AbstractLockingProvider {
	private IDBConnection $connection;
	private ITimeFactory $timeFactory;
	private array $sharedLocks = [];
	private bool $cacheSharedLocks;

	public function __construct(
		IDBConnection $connection,
		ITimeFactory $timeFactory,
		int $ttl = 3600,
		bool $cacheSharedLocks = true
	) {
		$this->connection = $connection;
		$this->timeFactory = $timeFactory;
		$this->ttl = $ttl;
		$this->cacheSharedLocks = $cacheSharedLocks;
	}

	/**
	 * Check if we have an open shared lock for a path
	 */
	protected function isLocallyLocked(string $path): bool {
		return isset($this->sharedLocks[$path]) && $this->sharedLocks[$path];
	}

	/** @inheritDoc */
	protected function markAcquire(string $path, int $targetType): void {
		parent::markAcquire($path, $targetType);
		if ($this->cacheSharedLocks) {
			if ($targetType === self::LOCK_SHARED) {
				$this->sharedLocks[$path] = true;
			}
		}
	}

	/**
	 * Change the type of an existing tracked lock
	 */
	protected function markChange(string $path, int $targetType): void {
		parent::markChange($path, $targetType);
		if ($this->cacheSharedLocks) {
			if ($targetType === self::LOCK_SHARED) {
				$this->sharedLocks[$path] = true;
			} elseif ($targetType === self::LOCK_EXCLUSIVE) {
				$this->sharedLocks[$path] = false;
			}
		}
	}

	/**
	 * Insert a file locking row if it does not exists.
	 */
	protected function initLockField(string $path, int $lock = 0): int {
		$expire = $this->getExpireTime();
		return $this->connection->insertIgnoreConflict('file_locks', [
			'key' => $path,
			'lock' => $lock,
			'ttl' => $expire
		]);
	}

	protected function getExpireTime(): int {
		return $this->timeFactory->getTime() + $this->ttl;
	}

	/** @inheritDoc */
	public function isLocked(string $path, int $type): bool {
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
			if ($this->isLocallyLocked($path)) {
				// if we have a shared lock we kept open locally but it's released we always have at least 1 shared lock in the db
				return $lockValue > 1;
			} else {
				return $lockValue > 0;
			}
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			return $lockValue === -1;
		} else {
			return false;
		}
	}

	/** @inheritDoc */
	public function acquireLock(string $path, int $type, ?string $readablePath = null): void {
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
		} else {
			$existing = 0;
			if ($this->hasAcquiredLock($path, ILockingProvider::LOCK_SHARED) === false && $this->isLocallyLocked($path)) {
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

	/** @inheritDoc */
	public function releaseLock(string $path, int $type): void {
		$this->markRelease($path, $type);

		// we keep shared locks till the end of the request so we can re-use them
		if ($type === self::LOCK_EXCLUSIVE) {
			$qb = $this->connection->getQueryBuilder();
			$qb->update('file_locks')
				->set('lock', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->eq('key', $qb->createNamedParameter($path)))
				->andWhere($qb->expr()->eq('lock', $qb->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));
			$qb->executeStatement();
		} elseif (!$this->cacheSharedLocks) {
			$qb = $this->connection->getQueryBuilder();
			$qb->update('file_locks')
				->set('lock', $qb->func()->subtract('lock', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
				->where($qb->expr()->eq('key', $qb->createNamedParameter($path)))
				->andWhere($qb->expr()->gt('lock', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
			$qb->executeStatement();
		}
	}

	/** @inheritDoc */
	public function changeLock(string $path, int $targetType): void {
		$expire = $this->getExpireTime();
		if ($targetType === self::LOCK_SHARED) {
			$qb = $this->connection->getQueryBuilder();
			$result = $qb->update('file_locks')
				->set('lock', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
				->set('ttl', $qb->createNamedParameter($expire, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->andX(
					$qb->expr()->eq('key', $qb->createNamedParameter($path)),
					$qb->expr()->eq('lock', $qb->createNamedParameter(-1, IQueryBuilder::PARAM_INT))
				))->executeStatement();
		} else {
			// since we only keep one shared lock in the db we need to check if we have more then one shared lock locally manually
			if (isset($this->acquiredLocks['shared'][$path]) && $this->acquiredLocks['shared'][$path] > 1) {
				throw new LockedException($path);
			}
			$qb = $this->connection->getQueryBuilder();
			$result = $qb->update('file_locks')
				->set('lock', $qb->createNamedParameter(-1, IQueryBuilder::PARAM_INT))
				->set('ttl', $qb->createNamedParameter($expire, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->andX(
					$qb->expr()->eq('key', $qb->createNamedParameter($path)),
					$qb->expr()->eq('lock', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
				))->executeStatement();
		}
		if ($result !== 1) {
			throw new LockedException($path);
		}
		$this->markChange($path, $targetType);
	}

	/** @inheritDoc */
	public function cleanExpiredLocks(): void {
		$expire = $this->timeFactory->getTime();
		try {
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('file_locks')
				->where($qb->expr()->lt('ttl', $qb->createNamedParameter($expire, IQueryBuilder::PARAM_INT)))
				->executeStatement();
		} catch (\Exception $e) {
			// If the table is missing, the clean up was successful
			if ($this->connection->tableExists('file_locks')) {
				throw $e;
			}
		}
	}

	/** @inheritDoc */
	public function releaseAll(): void {
		parent::releaseAll();

		if (!$this->cacheSharedLocks) {
			return;
		}
		// since we keep shared locks we need to manually clean those
		$lockedPaths = array_keys($this->sharedLocks);
		$lockedPaths = array_filter($lockedPaths, function ($path) {
			return $this->sharedLocks[$path];
		});

		$chunkedPaths = array_chunk($lockedPaths, 100);

		$qb = $this->connection->getQueryBuilder();
		$qb->update('file_locks')
			->set('lock', $qb->func()->subtract('lock', $qb->expr()->literal(1)))
			->where($qb->expr()->in('key', $qb->createParameter('chunk')))
			->andWhere($qb->expr()->gt('lock', new Literal(0)));

		foreach ($chunkedPaths as $chunk) {
			$qb->setParameter('chunk', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$qb->executeStatement();
		}
	}
}
