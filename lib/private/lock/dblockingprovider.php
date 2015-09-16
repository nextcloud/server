<?php
/**
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

use OCP\IDBConnection;
use OCP\ILogger;
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
	 * @param \OCP\IDBConnection $connection
	 * @param \OCP\ILogger $logger
	 */
	public function __construct(IDBConnection $connection, ILogger $logger) {
		$this->connection = $connection;
		$this->logger = $logger;
	}

	protected function initLockField($path) {
		$this->connection->insertIfNotExist('*PREFIX*file_locks', ['key' => $path, 'lock' => 0, 'ttl' => 0], ['key']);
	}

	/**
	 * @param string $path
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @return bool
	 */
	public function isLocked($path, $type) {
		$query = $this->connection->prepare('SELECT `lock` from `*PREFIX*file_locks` WHERE `key` = ?');
		$query->execute([$path]);
		$lockValue = (int)$query->fetchColumn();
		if ($type === self::LOCK_SHARED) {
			return $lockValue > 0;
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
		if ($this->connection->inTransaction()) {
			$this->logger->warning("Trying to acquire a lock for '$path' while inside a transition");
		}

		$this->initLockField($path);
		if ($type === self::LOCK_SHARED) {
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = `lock` + 1 WHERE `key` = ? AND `lock` >= 0',
				[$path]
			);
		} else {
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = -1 WHERE `key` = ? AND `lock` = 0',
				[$path]
			);
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
		$this->initLockField($path);
		if ($type === self::LOCK_SHARED) {
			$this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = `lock` - 1 WHERE `key` = ? AND `lock` > 0',
				[$path]
			);
		} else {
			$this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = 0 WHERE `key` = ? AND `lock` = -1',
				[$path]
			);
		}

		$this->markRelease($path, $type);
	}

	/**
	 * Change the type of an existing lock
	 *
	 * @param string $path
	 * @param int $targetType self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function changeLock($path, $targetType) {
		$this->initLockField($path);
		if ($targetType === self::LOCK_SHARED) {
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = 1 WHERE `key` = ? AND `lock` = -1',
				[$path]
			);
		} else {
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*file_locks` SET `lock` = -1 WHERE `key` = ? AND `lock` = 1',
				[$path]
			);
		}
		if ($result !== 1) {
			throw new LockedException($path);
		}
		$this->markChange($path, $targetType);
	}
}
