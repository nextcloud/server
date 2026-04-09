<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Service;

use OC\Files\Cache\CacheEntry;
use OC\Files\Storage\FailedStorage;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Event\StorageUpdatedEvent;
use OCA\Files_External\Lib\ApplicableHelper;
use OCA\Files_External\Lib\StorageConfig;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\IUserMountCache;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroup;
use OCP\IUser;
use OCP\User\Events\PostLoginEvent;
use OCP\User\Events\UserCreatedEvent;

/**
 * Listens to config events and update the mounts for the applicable users
 *
 * @template-implements IEventListener<StorageCreatedEvent|StorageDeletedEvent|StorageUpdatedEvent|BeforeGroupDeletedEvent|UserCreatedEvent|UserAddedEvent|UserRemovedEvent|PostLoginEvent|Event>
 */
class MountCacheService implements IEventListener {
	private CappedMemoryCache $storageRootCache;

	public function __construct(
		private readonly IUserMountCache $userMountCache,
		private readonly ConfigAdapter $configAdapter,
		private readonly GlobalStoragesService $storagesService,
		private readonly ApplicableHelper $applicableHelper,
	) {
		$this->storageRootCache = new CappedMemoryCache();
	}

	public function handle(Event $event): void {
		if ($event instanceof StorageCreatedEvent) {
			$this->handleAddedStorage($event->getNewConfig());
		}
		if ($event instanceof StorageDeletedEvent) {
			$this->handleDeletedStorage($event->getOldConfig());
		}
		if ($event instanceof StorageUpdatedEvent) {
			$this->handleUpdatedStorage($event->getOldConfig(), $event->getNewConfig());
		}
		if ($event instanceof UserAddedEvent) {
			$this->handleUserAdded($event->getGroup(), $event->getUser());
		}
		if ($event instanceof UserRemovedEvent) {
			$this->handleUserRemoved($event->getGroup(), $event->getUser());
		}
		if ($event instanceof BeforeGroupDeletedEvent) {
			$this->handleGroupDeleted($event->getGroup());
		}
		if ($event instanceof UserCreatedEvent) {
			$this->handleUserCreated($event->getUser());
		}
		if ($event instanceof PostLoginEvent) {
			$this->onLogin($event->getUser());
		}
	}

	public function handleDeletedStorage(StorageConfig $storage): void {
		foreach ($this->applicableHelper->getUsersForStorage($storage) as $user) {
			$this->userMountCache->removeMount($storage->getMountPointForUser($user));
		}
	}

	public function handleAddedStorage(StorageConfig $storage): void {
		foreach ($this->applicableHelper->getUsersForStorage($storage) as $user) {
			$this->registerForUser($user, $storage);
		}
	}

	public function handleUpdatedStorage(StorageConfig $oldStorage, StorageConfig $newStorage): void {
		foreach ($this->applicableHelper->diffApplicable($oldStorage, $newStorage) as $user) {
			$this->userMountCache->removeMount($oldStorage->getMountPointForUser($user));
		}
		foreach ($this->applicableHelper->diffApplicable($newStorage, $oldStorage) as $user) {
			$this->registerForUser($user, $newStorage);
		}
	}

	private function getCacheEntryForRoot(IUser $user, StorageConfig $storage): ICacheEntry {
		try {
			$userStorage = $this->configAdapter->constructStorageForUser($user, clone $storage);
		} catch (\Exception $e) {
			$userStorage = new FailedStorage(['exception' => $e]);
		}

		$cachedEntry = $this->storageRootCache->get($userStorage->getId());
		if ($cachedEntry !== null) {
			return $cachedEntry;
		}

		$cache = $userStorage->getCache();
		$entry = $cache->get('');
		if ($entry && $entry->getId() !== -1) {
			$this->storageRootCache->set($userStorage->getId(), $entry);
			return $entry;
		}

		// create a "fake" root entry so we have a fileid so we don't have to interact with the remote service
		// this will be scanned on first access
		$data = [
			'path' => '',
			'path_hash' => md5(''),
			'size' => 0,
			'unencrypted_size' => 0,
			'mtime' => 0,
			'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE,
			'parent' => -1,
			'name' => '',
			'storage_mtime' => 0,
			'permissions' => 31,
			'storage' => $cache->getNumericStorageId(),
			'etag' => '',
			'encrypted' => 0,
			'checksum' => '',
		];
		if ($cache->getNumericStorageId() !== -1) {
			$data['fileid'] = $cache->insert('', $data);
		} else {
			$data['fileid'] = -1;
		}

		$entry = new CacheEntry($data);
		$this->storageRootCache->set($userStorage->getId(), $entry);
		return $entry;
	}

	private function registerForUser(IUser $user, StorageConfig $storage): void {
		$this->userMountCache->addMount(
			$user,
			$storage->getMountPointForUser($user),
			$this->getCacheEntryForRoot($user, $storage),
			ConfigAdapter::class,
			$storage->getId(),
		);
	}

	private function handleUserRemoved(IGroup $group, IUser $user): void {
		$storages = $this->storagesService->getAllStoragesForGroup($group);
		foreach ($storages as $storage) {
			if (!$this->applicableHelper->isApplicableForUser($storage, $user)) {
				$this->userMountCache->removeMount($storage->getMountPointForUser($user));
			}
		}
	}

	private function handleUserAdded(IGroup $group, IUser $user): void {
		$storages = $this->storagesService->getAllStoragesForGroup($group);
		foreach ($storages as $storage) {
			$this->registerForUser($user, $storage);
		}
	}

	private function handleGroupDeleted(IGroup $group): void {
		$storages = $this->storagesService->getAllStoragesForGroup($group);
		foreach ($storages as $storage) {
			$this->removeGroupFromStorage($storage, $group);
		}
	}

	/**
	 * Remove mounts from users in a group, if they don't have access to the storage trough other means
	 */
	private function removeGroupFromStorage(StorageConfig $storage, IGroup $group): void {
		foreach ($group->searchUsers('') as $user) {
			if (!$this->applicableHelper->isApplicableForUser($storage, $user)) {
				$this->userMountCache->removeMount($storage->getMountPointForUser($user));
			}
		}
	}

	private function handleUserCreated(IUser $user): void {
		$storages = $this->storagesService->getAllGlobalStorages();
		foreach ($storages as $storage) {
			$this->registerForUser($user, $storage);
		}
	}

	/**
	 * Since storage config can rely on login credentials, we might need to update the config
	 */
	private function onLogin(IUser $user): void {
		$storages = $this->storagesService->getAllGlobalStorages();
		foreach ($storages as $storage) {
			$this->registerForUser($user, $storage);
		}
	}
}
