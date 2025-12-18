<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Service;

use OC\Files\Cache\CacheEntry;
use OC\User\LazyUser;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Event\StorageUpdatedEvent;
use OCA\Files_External\Lib\StorageConfig;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\Event as T;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\IUserMountCache;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\UserCreatedEvent;

/**
 * Listens to config events and update the mounts for the applicable users
 *
 * @template-implements IEventListener<StorageCreatedEvent|StorageDeletedEvent|StorageUpdatedEvent|BeforeGroupDeletedEvent|UserCreatedEvent|UserAddedEvent|UserRemovedEvent|Event>
 */
class MountCacheService implements IEventListener {
	private CappedMemoryCache $storageRootCache;

	public function __construct(
		private readonly IUserMountCache $userMountCache,
		private readonly ConfigAdapter $configAdapter,
		private readonly IUserManager $userManager,
		private readonly IGroupManager $groupManager,
		private readonly GlobalStoragesService $storagesService,
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
	}


	/**
	 * Get all users that have access to a storage, either directly or through a group
	 *
	 * @param StorageConfig $storage
	 * @return \Iterator<string, IUser>
	 */
	private function getUsersForStorage(StorageConfig $storage): \Iterator {
		$yielded = [];
		if (count($storage->getApplicableUsers()) + count($storage->getApplicableGroups()) === 0) {
			yield from $this->userManager->getSeenUsers();
		}
		foreach ($storage->getApplicableUsers() as $userId) {
			$yielded[$userId] = true;
			yield $userId => new LazyUser($userId, $this->userManager);
		}
		foreach ($storage->getApplicableGroups() as $groupId) {
			$group = $this->groupManager->get($groupId);
			if ($group !== null) {
				foreach ($group->searchUsers('') as $user) {
					if (!isset($yielded[$user->getUID()])) {
						$yielded[$user->getUID()] = true;
						yield $user->getUID() => $user;
					}
				}
			}
		}
	}

	public function handleDeletedStorage(StorageConfig $storage): void {
		foreach ($this->getUsersForStorage($storage) as $user) {
			$this->userMountCache->removeMount($storage->getMountPointForUser($user));
		}
	}

	public function handleAddedStorage(StorageConfig $storage): void {
		foreach ($this->getUsersForStorage($storage) as $user) {
			$this->registerForUser($user, $storage);
		}
	}

	public function handleUpdatedStorage(StorageConfig $oldStorage, StorageConfig $newStorage): void {
		/** @var array<string, IUser> $oldApplicable */
		$oldApplicable = iterator_to_array($this->getUsersForStorage($oldStorage));
		/** @var array<string, IUser> $newApplicable */
		$newApplicable = iterator_to_array($this->getUsersForStorage($newStorage));

		foreach ($oldApplicable as $oldUser) {
			if (!isset($newApplicable[$oldUser->getUID()])) {
				$this->userMountCache->removeMount($oldStorage->getMountPointForUser($oldUser));
			}
		}

		foreach ($newApplicable as $newUser) {
			if (!isset($oldApplicable[$newUser->getUID()])) {
				$this->registerForUser($newUser, $newStorage);
			}
		}
	}

	private function getCacheEntryForRoot(IUser $user, StorageConfig $storage): ICacheEntry {
		$storage = $this->configAdapter->constructStorageForUser($user, $storage);

		if ($cachedEntry = $this->storageRootCache->get($storage->getId())) {
			return $cachedEntry;
		}

		$cache = $storage->getCache();
		$entry = $cache->get('');
		if ($entry) {
			$this->storageRootCache->set($storage->getId(), $entry);
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
		$data['fileid'] = $cache->insert('', $data);

		$entry = new CacheEntry($data);
		$this->storageRootCache->set($storage->getId(), $entry);
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
			if (!in_array($user->getUID(), $storage->getApplicableUsers())) {
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
			foreach ($group->searchUsers('') as $user) {
				if (!in_array($user->getUID(), $storage->getApplicableUsers())) {
					$this->userMountCache->removeMount($storage->getMountPointForUser($user));
				}
			}
		}
	}

	private function handleUserCreated(IUser $user): void {
		$storages = $this->storagesService->getAllGlobalStorages();
		foreach ($storages as $storage) {
			$this->registerForUser($user, $storage);
		}
	}
}
