<?php
/**
 * @author Individual IT Services <info@individual-it.net>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Lock;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

/**
 * Locking provider that stores the locks in the database
 */
class DBLockingProvider extends AbstractLockingProvider {
	/**
	 * @var \OCP\IDBConnection
	 */
	private $connection;

	/**
	 * @var \OCP\ILogger
	 */
	private $logger;

	/**
	 * @var \OCP\AppFramework\Utility\ITimeFactory
	 */
	private $timeFactory;

	private $sharedLocks = [];

	/**
	 * Check if we have an open shared lock for a path
	 *
	 * @param string $path
	 * @return bool
	 */
	protected function isLocallyLocked($path) {
		return isset($this->sharedLocks[$path]) && $this->sharedLocks[$path];
	}

	/**
	 * Mark a locally acquired lock
	 *
	 * @param string $path
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 */
	protected function markAcquire($path, $type) {
		parent::markAcquire($path, $type);
		if ($type === self::LOCK_SHARED) {
			$this->sharedLocks[$path] = true;
		}
	}

	/**
	 * Change the type of an existing tracked lock
	 *
	 * @param string $path
	 * @param int $targetType self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 */
	protected function markChange($path, $targetType) {
		parent::markChange($path, $targetType);
		if ($targetType === self::LOCK_SHARED) {
			$this->sharedLocks[$path] = true;
		} else if ($targetType === self::LOCK_EXCLUSIVE) {
			$this->sharedLocks[$path] = false;
		}
	}

	/**
	 * @param \OCP\IDBConnection $connection
	 * @param \OCP\ILogger $logger
	 * @param \OCP\AppFramework\Utility\ITimeFactory $timeFactory
	 */
	public function __construct(IDBConnection $connection, ILogger $logger, ITimeFactory $timeFactory) {
		$this->connection = $connection;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * Insert a file locking row if it does not exists.
	 *
	 * @param string $path
	 * @param int $lock
	 * @return int number of inserted rows
	 */

	protected function initLockField($path, $lock = 0) {
		$expire = $this->getExpireTime();
		return $this->connection->insertIfNotExist('*PREFIX*file_locks', ['key' => $path, 'lock' => $lock, 'ttl' => $expire], ['key']);
	}

	/**
	 * @return int
	 */
	protected function getExpireTime() {
		return $this->timeFactory->getTime() + self::TTL;
	}

	/**
	 * @param string $path
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @return bool
	 */
	public function isLocked($path, $type) {
		if ($this->hasAcquiredLock($path, $type)) {
			return true;
		}
		$query = $this->connection->prepare('SELECT `lock` from `*PREFIX*file_locks` WHERE `key` = ?');
		$query->execute([$path]);
		$lockValue = (int)$query->fetchColumn();
		if ($type === self::LOCK_SHARED) {
			if ($this->isLocallyLocked($path)) {
				// if we have a shared lock we kept open locally but it's released we always have at least 1 shared lock in the db
				return $lockValue > 1;
			} else {
				return $lockValue > 0;
			}
		} else if ($type === self::LOCK_EXCLUSIVE) {
			return $lockValue === -1;
		} else {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function acquireLock($path, $type) {
		if (strlen($path) > 64) { // max length in file_locks
			throw new \InvalidArgumentException("Lock key length too long");
		}
		$expire = $this->getExpireTime();
		if ($type === self::LOCK_SHARED) {
			if (!$this->isLocallyLocked($path)) {
				$result = $this->initLockField($path, 1);
				if ($result <= 0) {
					$result = $this->connection->executeUpdate(
						'UPDATE `*PREFIX*file_locks` SET `lock` = `lock` + 1, `ttl` = ? WHERE `key` = ? AND `lock` >= 0',
						[$expire, $path]
					);
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
				$result = $this->connection->executeUpdate(
					'UPDATE `*PREFIX*file_locks` SET `lock` = -1, `ttl` = ? WHERE `key` = ? AND `lock` = ?',
					[$expire, $path, $existing]
				);
			}
		}
		if ($result !== 1) {
			throw new LockedException($path);
		}
		$this->markAcquire($path, $type);
	}

	/**
	 * @param string $path
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 */
	public function releaseLock($path, $type) {
		$this->markRelease($path, $type);

		// we keep shared locks till the end of the request so we can re-use them
		if ($type === self::LOCK_EXCLUSIVE) {
			$this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = 0 WHERE `key` = ? AND `lock` = -1',
				[$path]
			);
		}
	}

	/**
	 * Change the type of an existing lock
	 *
	 * @param string $path
	 * @param int $targetType self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function changeLock($path, $targetType) {
		$expire = $this->getExpireTime();
		if ($targetType === self::LOCK_SHARED) {
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = 1, `ttl` = ? WHERE `key` = ? AND `lock` = -1',
				[$expire, $path]
			);
		} else {
			// since we only keep one shared lock in the db we need to check if we have more then one shared lock locally manually
			if (isset($this->acquiredLocks['shared'][$path]) && $this->acquiredLocks['shared'][$path] > 1) {
				throw new LockedException($path);
			}
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = -1, `ttl` = ? WHERE `key` = ? AND `lock` = 1',
				[$expire, $path]
			);
		}
		if ($result !== 1) {
			throw new LockedException($path);
		}
		$this->markChange($path, $targetType);
	}

	/**
	 * cleanup empty locks
	 */
	public function cleanExpiredLocks() {
		$expire = $this->timeFactory->getTime();
		try {
			$this->connection->executeUpdate(
				'DELETE FROM `*PREFIX*file_locks` WHERE `ttl` < ?',
				[$expire]
			);
		} catch (\Exception $e) {
			// If the table is missing, the clean up was successful
			if ($this->connection->tableExists('file_locks')) {
				throw $e;
			}
		}
	}

	/**
	 * release all lock acquired by this instance which were marked using the mark* methods
	 */
	public function releaseAll() {
		parent::releaseAll();

		// since we keep shared locks we need to manually clean those
		foreach ($this->sharedLocks as $path => $lock) {
			if ($lock) {
				$this->connection->executeUpdate(
					'UPDATE `*PREFIX*file_locks` SET `lock` = `lock` - 1 WHERE `key` = ? AND `lock` > 0',
					[$path]
				);
			}
		}
	}
}
