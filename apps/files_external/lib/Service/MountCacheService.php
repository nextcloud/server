<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Service;

use OC\Files\Cache\CacheEntry;
use OC\Files\Storage\FailedStorage;
use OC\User\LazyUser;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Event\StorageUpdatedEvent;
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
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
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
		private readonly IUserManager $userManager,
		private readonly IGroupManager $groupManager,
		private readonly GlobalStoragesService $storagesService,
	) {
		$this->storageRootCache = new CappedMemoryCache();
	}

	public function handle(Event $event): void {
		if ($event instanceof StorageCreatedEvent) {
			$this->registerAddedStorage($event->getNewConfig());
		}
		if ($event instanceof StorageDeletedEvent) {
			$this->registerDeletedStorage($event->getOldConfig());
		}
		if ($event instanceof StorageUpdatedEvent) {
			$this->registerUpdatedStorage($event->getOldConfig(), $event->getNewConfig());
		}
		if ($event instanceof UserAddedEvent) {
			$this->addUserToGroup($event->getGroup(), $event->getUser());
		}
		if ($event instanceof UserRemovedEvent) {
			$this->removeUserFromGroup($event->getGroup(), $event->getUser());
		}
		if ($event instanceof BeforeGroupDeletedEvent) {
			$this->removeGroup($event->getGroup());
		}
		if ($event instanceof UserCreatedEvent) {
			$this->addUser($event->getUser());
		}
		if ($event instanceof PostLoginEvent) {
			$this->onLogin($event->getUser());
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
			if ($group = $this->groupManager->get($groupId)) {
				foreach ($group->searchUsers('') as $user) {
					if (!isset($yielded[$user->getUID()])) {
						$yielded[$user->getUID()] = true;
						yield $user->getUID() => $user;
					}
				}
			}
		}
	}

	public function registerDeletedStorage(StorageConfig $storage): void {
		foreach ($this->getUsersForStorage($storage) as $user) {
			$this->userMountCache->removeMount($storage->getMountPointForUser($user));
		}
	}

	public function registerAddedStorage(StorageConfig $storage): void {
		foreach ($this->getUsersForStorage($storage) as $user) {
			$this->registerForUser($user, $storage);
		}
	}

	public function registerUpdatedStorage(StorageConfig $oldStorage, StorageConfig $newStorage): void {
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
		try {
			$userStorage = $this->configAdapter->constructStorageForUser($user, clone $storage);
		} catch (\Exception $e) {
			$userStorage = new FailedStorage(['exception' => $e]);
		}

		if ($cachedEntry = $this->storageRootCache->get($userStorage->getId())) {
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

	private function removeUserFromGroup(IGroup $group, IUser $user): void {
		$storages = $this->storagesService->getAllStoragesForGroup($group);
		foreach ($storages as $storage) {
			if (!in_array($user->getUID(), $storage->getApplicableUsers())) {
				$this->userMountCache->removeMount($storage->getMountPointForUser($user));
			}
		}
	}

	private function addUserToGroup(IGroup $group, IUser $user): void {
		$storages = $this->storagesService->getAllStoragesForGroup($group);
		foreach ($storages as $storage) {
			$this->registerForUser($user, $storage);
		}
	}

	private function removeGroup(IGroup $group): void {
		$storages = $this->storagesService->getAllStoragesForGroup($group);
		foreach ($storages as $storage) {
			foreach ($group->searchUsers('') as $user) {
				if (!in_array($user->getUID(), $storage->getApplicableUsers())) {
					$this->userMountCache->removeMount($storage->getMountPointForUser($user));
				}
			}
		}
	}

	private function addUser(IUser $user): void {
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
