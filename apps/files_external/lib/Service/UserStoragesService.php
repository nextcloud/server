<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Service;

use OC\Files\Filesystem;
use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\MountConfig;
use OCA\Files_External\NotFoundException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\IAppConfig;
use OCP\IUserSession;

/**
 * Service class to manage user external storage
 * (aka personal storages)
 */
class UserStoragesService extends StoragesService {
	use UserTrait;

	/**
	 * Create a user storages service
	 */
	public function __construct(
		BackendService $backendService,
		DBConfigService $dbConfig,
		IUserSession $userSession,
		IUserMountCache $userMountCache,
		IEventDispatcher $eventDispatcher,
		IAppConfig $appConfig,
	) {
		$this->userSession = $userSession;
		parent::__construct($backendService, $dbConfig, $userMountCache, $eventDispatcher, $appConfig);
	}

	protected function readDBConfig() {
		return $this->dbConfig->getUserMountsFor(DBConfigService::APPLICABLE_TYPE_USER, $this->getUser()->getUID());
	}

	/**
	 * Triggers $signal for all applicable users of the given
	 * storage
	 *
	 * @param StorageConfig $storage storage data
	 * @param string $signal signal to trigger
	 */
	protected function triggerHooks(StorageConfig $storage, $signal) {
		$user = $this->getUser()->getUID();

		// trigger hook for the current user
		$this->triggerApplicableHooks(
			$signal,
			$storage->getMountPoint(),
			MountConfig::MOUNT_TYPE_USER,
			[$user]
		);
	}

	/**
	 * Triggers signal_create_mount or signal_delete_mount to
	 * accommodate for additions/deletions in applicableUsers
	 * and applicableGroups fields.
	 *
	 * @param StorageConfig $oldStorage old storage data
	 * @param StorageConfig $newStorage new storage data
	 */
	protected function triggerChangeHooks(StorageConfig $oldStorage, StorageConfig $newStorage) {
		// if mount point changed, it's like a deletion + creation
		if ($oldStorage->getMountPoint() !== $newStorage->getMountPoint()) {
			$this->eventDispatcher->dispatchTyped(new StorageDeletedEvent($oldStorage));
			$this->eventDispatcher->dispatchTyped(new StorageCreatedEvent($newStorage));
			$this->triggerHooks($oldStorage, Filesystem::signal_delete_mount);
			$this->triggerHooks($newStorage, Filesystem::signal_create_mount);
		}
	}

	protected function getType() {
		return DBConfigService::MOUNT_TYPE_PERSONAL;
	}

	/**
	 * Add new storage to the configuration
	 *
	 * @param StorageConfig $newStorage storage attributes
	 *
	 * @return StorageConfig storage config, with added id
	 */
	public function addStorage(StorageConfig $newStorage) {
		$newStorage->setApplicableUsers([$this->getUser()->getUID()]);
		return parent::addStorage($newStorage);
	}

	/**
	 * Update storage to the configuration
	 *
	 * @param StorageConfig $updatedStorage storage attributes
	 *
	 * @return StorageConfig storage config
	 * @throws NotFoundException if the given storage does not exist in the config
	 */
	public function updateStorage(StorageConfig $updatedStorage) {
		// verify ownership through $this->isApplicable() and otherwise throws an exception
		$this->getStorage($updatedStorage->getId());

		$updatedStorage->setApplicableUsers([$this->getUser()->getUID()]);
		return parent::updateStorage($updatedStorage);
	}

	/**
	 * Get the visibility type for this controller, used in validation
	 *
	 * @return int BackendService::VISIBILITY_* constants
	 */
	public function getVisibilityType() {
		return BackendService::VISIBILITY_PERSONAL;
	}

	protected function isApplicable(StorageConfig $config) {
		return ($config->getApplicableUsers() === [$this->getUser()->getUID()]) && $config->getType() === StorageConfig::MOUNT_TYPE_PERSONAL;
	}

	public function removeStorage($id) {
		// verify ownership through $this->isApplicable() and otherwise throws an exception
		$this->getStorage($id);
		parent::removeStorage($id);
	}
}
