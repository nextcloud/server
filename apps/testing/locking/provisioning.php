<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Testing\Locking;

use OC\Lock\DBLockingProvider;
use OC\Lock\MemcacheLockingProvider;
use OC\User\NoUserException;
use OCP\AppFramework\Http;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

class Provisioning {

	/** @var ILockingProvider */
	protected $lockingProvider;

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	/** @var IRequest */
	protected $request;

	/**
	 * @param ILockingProvider $lockingProvider
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 * @param IRequest $request
	 */
	public function __construct(ILockingProvider $lockingProvider, IDBConnection $connection, IConfig $config, IRequest $request) {
		$this->lockingProvider = $lockingProvider;
		$this->connection = $connection;
		$this->config = $config;
		$this->request = $request;
	}

	/**
	 * @return ILockingProvider
	 */
	protected function getLockingProvider() {
		if ($this->lockingProvider instanceof DBLockingProvider) {
			return \OC::$server->query('OCA\Testing\Locking\FakeDBLockingProvider');
		} else {
			throw new \RuntimeException('Lock provisioning is only possible using the DBLockingProvider');
		}
	}

	/**
	 * @param array $parameters
	 * @return int
	 */
	protected function getType($parameters) {
		return isset($parameters['type']) ? (int) $parameters['type'] : 0;
	}

	/**
	 * @param array $parameters
	 * @return int
	 */
	protected function getPath($parameters) {
		$node = \OC::$server->getRootFolder()
			->getUserFolder($parameters['user'])
			->get($this->request->getParam('path'));
		return 'files/' . md5($node->getStorage()->getId() . '::' . trim($node->getInternalPath(), '/'));
	}

	/**
	 * @return \OC_OCS_Result
	 */
	public function isLockingEnabled() {
		try {
			$this->getLockingProvider();
			return new \OC_OCS_Result(null, 100);
		} catch (\RuntimeException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_IMPLEMENTED, $e->getMessage());
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function acquireLock(array $parameters) {
		try {
			$path = $this->getPath($parameters);
		} catch (NoUserException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'User not found');
		} catch (NotFoundException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'Path not found');
		}
		$type = $this->getType($parameters);

		$lockingProvider = $this->getLockingProvider();

		try {
			$lockingProvider->acquireLock($path, $type);
			$this->config->setAppValue('testing', 'locking_' . $path, $type);
			return new \OC_OCS_Result(null, 100);
		} catch (LockedException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_LOCKED);
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function changeLock(array $parameters) {
		try {
			$path = $this->getPath($parameters);
		} catch (NoUserException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'User not found');
		} catch (NotFoundException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'Path not found');
		}
		$type = $this->getType($parameters);

		$lockingProvider = $this->getLockingProvider();

		try {
			$lockingProvider->changeLock($path, $type);
			$this->config->setAppValue('testing', 'locking_' . $path, $type);
			return new \OC_OCS_Result(null, 100);
		} catch (LockedException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_LOCKED);
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function releaseLock(array $parameters) {
		try {
			$path = $this->getPath($parameters);
		} catch (NoUserException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'User not found');
		} catch (NotFoundException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'Path not found');
		}
		$type = $this->getType($parameters);

		$lockingProvider = $this->getLockingProvider();

		try {
			$lockingProvider->releaseLock($path, $type);
			$this->config->deleteAppValue('testing', 'locking_' . $path);
			return new \OC_OCS_Result(null, 100);
		} catch (LockedException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_LOCKED);
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function isLocked(array $parameters) {
		try {
			$path = $this->getPath($parameters);
		} catch (NoUserException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'User not found');
		} catch (NotFoundException $e) {
			return new \OC_OCS_Result(null, Http::STATUS_NOT_FOUND, 'Path not found');
		}
		$type = $this->getType($parameters);

		$lockingProvider = $this->getLockingProvider();

		if ($lockingProvider->isLocked($path, $type)) {
			return new \OC_OCS_Result(null, 100);
		}

		return new \OC_OCS_Result(null, Http::STATUS_LOCKED);
	}

	/**
	 * @param array $parameters
	 * @return \OC_OCS_Result
	 */
	public function releaseAll(array $parameters) {
		$type = $this->getType($parameters);

		$lockingProvider = $this->getLockingProvider();

		foreach ($this->config->getAppKeys('testing') as $lock) {
			if (strpos($lock, 'locking_') === 0) {
				$path = substr($lock, strlen('locking_'));

				if ($type === ILockingProvider::LOCK_EXCLUSIVE && $this->config->getAppValue('testing', $lock) == ILockingProvider::LOCK_EXCLUSIVE) {
					$lockingProvider->releaseLock($path, $this->config->getAppValue('testing', $lock));
				} else if ($type === ILockingProvider::LOCK_SHARED && $this->config->getAppValue('testing', $lock) == ILockingProvider::LOCK_SHARED) {
					$lockingProvider->releaseLock($path, $this->config->getAppValue('testing', $lock));
				} else {
					$lockingProvider->releaseLock($path, $this->config->getAppValue('testing', $lock));
				}
			}
		}

		return new \OC_OCS_Result(null, 100);
	}
}
