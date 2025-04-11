<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Testing\Controller;

use OC\Lock\DBLockingProvider;
use OC\User\NoUserException;
use OCA\Testing\Locking\FakeDBLockingProvider;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

class LockingController extends OCSController {

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ILockingProvider $lockingProvider
	 * @param FakeDBLockingProvider $fakeDBLockingProvider
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
		$appName,
		IRequest $request,
		protected ILockingProvider $lockingProvider,
		protected FakeDBLockingProvider $fakeDBLockingProvider,
		protected IDBConnection $connection,
		protected IConfig $config,
		protected IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws \RuntimeException
	 */
	protected function getLockingProvider(): ILockingProvider {
		if ($this->lockingProvider instanceof DBLockingProvider) {
			return $this->fakeDBLockingProvider;
		}
		throw new \RuntimeException('Lock provisioning is only possible using the DBLockingProvider');
	}

	/**
	 * @throws NotFoundException
	 */
	protected function getPath(string $user, string $path): string {
		$node = $this->rootFolder->getUserFolder($user)->get($path);
		return 'files/' . md5($node->getStorage()->getId() . '::' . trim($node->getInternalPath(), '/'));
	}

	/**
	 * @throws OCSException
	 */
	public function isLockingEnabled(): DataResponse {
		try {
			$this->getLockingProvider();
			return new DataResponse();
		} catch (\RuntimeException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_NOT_IMPLEMENTED, $e);
		}
	}

	/**
	 * @throws OCSException
	 */
	public function acquireLock(int $type, string $user, string $path): DataResponse {
		try {
			$path = $this->getPath($user, $path);
		} catch (NoUserException $e) {
			throw new OCSException('User not found', Http::STATUS_NOT_FOUND, $e);
		} catch (NotFoundException $e) {
			throw new OCSException('Path not found', Http::STATUS_NOT_FOUND, $e);
		}

		$lockingProvider = $this->getLockingProvider();

		try {
			$lockingProvider->acquireLock($path, $type);
			$this->config->setAppValue('testing', 'locking_' . $path, (string)$type);
			return new DataResponse();
		} catch (LockedException $e) {
			throw new OCSException('', Http::STATUS_LOCKED, $e);
		}
	}

	/**
	 * @throws OCSException
	 */
	public function changeLock(int $type, string $user, string $path): DataResponse {
		try {
			$path = $this->getPath($user, $path);
		} catch (NoUserException $e) {
			throw new OCSException('User not found', Http::STATUS_NOT_FOUND, $e);
		} catch (NotFoundException $e) {
			throw new OCSException('Path not found', Http::STATUS_NOT_FOUND, $e);
		}

		$lockingProvider = $this->getLockingProvider();

		try {
			$lockingProvider->changeLock($path, $type);
			$this->config->setAppValue('testing', 'locking_' . $path, (string)$type);
			return new DataResponse();
		} catch (LockedException $e) {
			throw new OCSException('', Http::STATUS_LOCKED, $e);
		}
	}

	/**
	 * @throws OCSException
	 */
	public function releaseLock(int $type, string $user, string $path): DataResponse {
		try {
			$path = $this->getPath($user, $path);
		} catch (NoUserException $e) {
			throw new OCSException('User not found', Http::STATUS_NOT_FOUND, $e);
		} catch (NotFoundException $e) {
			throw new OCSException('Path not found', Http::STATUS_NOT_FOUND, $e);
		}

		$lockingProvider = $this->getLockingProvider();

		try {
			$lockingProvider->releaseLock($path, $type);
			$this->config->deleteAppValue('testing', 'locking_' . $path);
			return new DataResponse();
		} catch (LockedException $e) {
			throw new OCSException('', Http::STATUS_LOCKED, $e);
		}
	}

	/**
	 * @throws OCSException
	 */
	public function isLocked(int $type, string $user, string $path): DataResponse {
		try {
			$path = $this->getPath($user, $path);
		} catch (NoUserException $e) {
			throw new OCSException('User not found', Http::STATUS_NOT_FOUND, $e);
		} catch (NotFoundException $e) {
			throw new OCSException('Path not found', Http::STATUS_NOT_FOUND, $e);
		}

		$lockingProvider = $this->getLockingProvider();

		if ($lockingProvider->isLocked($path, $type)) {
			return new DataResponse();
		}

		throw new OCSException('', Http::STATUS_LOCKED);
	}

	public function releaseAll(?int $type = null): DataResponse {
		$lockingProvider = $this->getLockingProvider();

		foreach ($this->config->getAppKeys('testing') as $lock) {
			if (strpos($lock, 'locking_') === 0) {
				$path = substr($lock, strlen('locking_'));

				if ($type === ILockingProvider::LOCK_EXCLUSIVE && (int)$this->config->getAppValue('testing', $lock) === ILockingProvider::LOCK_EXCLUSIVE) {
					$lockingProvider->releaseLock($path, (int)$this->config->getAppValue('testing', $lock));
				} elseif ($type === ILockingProvider::LOCK_SHARED && (int)$this->config->getAppValue('testing', $lock) === ILockingProvider::LOCK_SHARED) {
					$lockingProvider->releaseLock($path, (int)$this->config->getAppValue('testing', $lock));
				} else {
					$lockingProvider->releaseLock($path, (int)$this->config->getAppValue('testing', $lock));
				}
			}
		}

		return new DataResponse();
	}
}
