<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Service;

use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Event\StorageUpdatedEvent;
use OCA\Files_External\Lib\StorageConfig;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\IGroup;
use Override;

/**
 * Service class to manage global external storage
 */
class GlobalStoragesService extends StoragesService {
	#[Override]
	protected function dispatchChangeEvents(StorageConfig $oldStorage, StorageConfig $newStorage): void {
		if ($oldStorage->getMountPoint() !== $newStorage->getMountPoint()) {
			$this->eventDispatcher->dispatchTyped(new StorageDeletedEvent($oldStorage));
			$this->eventDispatcher->dispatchTyped(new StorageCreatedEvent($newStorage));
		} else {
			$this->eventDispatcher->dispatchTyped(new StorageUpdatedEvent($oldStorage, $newStorage));
		}
		$this->eventDispatcher->dispatchTyped(new InvalidateMountCacheEvent(null));
	}

	#[Override]
	public function getVisibilityType(): int {
		return BackendService::VISIBILITY_ADMIN;
	}

	#[Override]
	protected function isApplicable(StorageConfig $config): bool {
		return true;
	}

	/**
	 * Get all configured admin and personal mounts
	 *
	 * @return StorageConfig[] map of storage id to storage config
	 */
	public function getStorageForAllUsers(): array {
		$mounts = $this->dbConfig->getAllMounts();
		$configs = array_map([$this, 'getStorageConfigFromDBMount'], $mounts);
		$configs = array_filter($configs, function ($config) {
			return $config instanceof StorageConfig;
		});

		$keys = array_map(static fn (StorageConfig $config) => $config->getId(), $configs);

		return array_combine($keys, $configs);
	}

	/**
	 * Gets all storages for the group, not including any global storages
	 * @return StorageConfig[]
	 */
	public function getAllStoragesForGroup(IGroup $group): array {
		$mounts = $this->dbConfig->getMountsForGroups([$group->getGID()]);
		$configs = array_map($this->getStorageConfigFromDBMount(...), $mounts);
		$configs = array_filter($configs, static fn (?StorageConfig $config): bool => $config instanceof StorageConfig);
		$keys = array_map(static fn (StorageConfig $config) => $config->getId(), $configs);

		$storages = array_combine($keys, $configs);
		return array_filter($storages, $this->validateStorage(...));
	}

	/**
	 * @return StorageConfig[]
	 */
	public function getAllGlobalStorages(): array {
		$mounts = $this->dbConfig->getGlobalMounts();

		$configs = array_map($this->getStorageConfigFromDBMount(...), $mounts);
		$configs = array_filter($configs, static fn (?StorageConfig $config): bool => $config instanceof StorageConfig);
		$keys = array_map(static fn (StorageConfig $config) => $config->getId(), $configs);
		$storages = array_combine($keys, $configs);
		return array_filter($storages, $this->validateStorage(...));
	}
}
