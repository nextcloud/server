<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Service;

use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Lib\StorageConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\InvalidateMountCacheEvent;
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

	#[\Override]
	protected function dispatchChangeEvents(StorageConfig $oldStorage, StorageConfig $newStorage): void {
		if ($oldStorage->getMountPoint() !== $newStorage->getMountPoint()) {
			$this->eventDispatcher->dispatchTyped(new StorageDeletedEvent($oldStorage));
			$this->eventDispatcher->dispatchTyped(new StorageCreatedEvent($newStorage));
			$this->eventDispatcher->dispatchTyped(new InvalidateMountCacheEvent(null));
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
