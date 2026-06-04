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
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IUserSession;
use Override;

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
		IEventDispatcher $eventDispatcher,
		IAppConfig $appConfig,
	) {
		$this->userSession = $userSession;
		parent::__construct($backendService, $dbConfig, $eventDispatcher, $appConfig);
	}

	#[Override]
	protected function readDBConfig(): array {
		return $this->dbConfig->getUserMountsFor(DBConfigService::APPLICABLE_TYPE_USER, $this->getUser()->getUID());
	}

	/**
	 * Triggers $signal for all applicable users of the given
	 * storage
	 *
	 * @param StorageConfig $storage storage data
	 * @param string $signal signal to trigger
	 */
	#[\Override]
	protected function triggerHooks(StorageConfig $storage, string $signal): void {
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
	#[\Override]
	protected function triggerChangeHooks(StorageConfig $oldStorage, StorageConfig $newStorage): void {
		// if mount point changed, it's like a deletion + creation
		if ($oldStorage->getMountPoint() !== $newStorage->getMountPoint()) {
			$this->eventDispatcher->dispatchTyped(new StorageDeletedEvent($oldStorage));
			$this->eventDispatcher->dispatchTyped(new StorageCreatedEvent($newStorage));
			$this->triggerHooks($oldStorage, Filesystem::signal_delete_mount);
			$this->triggerHooks($newStorage, Filesystem::signal_create_mount);
		}
	}

	#[Override]
	protected function getType(): int {
		return DBConfigService::MOUNT_TYPE_PERSONAL;
	}

	#[Override]
	public function addStorage(StorageConfig $newStorage): StorageConfig {
		$newStorage->setApplicableUsers([$this->getUser()->getUID()]);
		return parent::addStorage($newStorage);
	}

	#[Override]
	public function updateStorage(StorageConfig $updatedStorage): StorageConfig {
		// verify ownership through $this->isApplicable() and otherwise throws an exception
		$this->getStorage($updatedStorage->getId());

		$updatedStorage->setApplicableUsers([$this->getUser()->getUID()]);
		return parent::updateStorage($updatedStorage);
	}

	#[Override]
	public function getVisibilityType(): int {
		return BackendService::VISIBILITY_PERSONAL;
	}

	#[Override]
	protected function isApplicable(StorageConfig $config): bool {
		return ($config->getApplicableUsers() === [$this->getUser()->getUID()]) && $config->getType() === StorageConfig::MOUNT_TYPE_PERSONAL;
	}

	#[Override]
	public function removeStorage(int $id): void {
		// verify ownership through $this->isApplicable() and otherwise throws an exception
		$this->getStorage($id);
		parent::removeStorage($id);
	}
}
