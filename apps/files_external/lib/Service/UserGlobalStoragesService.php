<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Service;

use OCA\Files_External\Lib\StorageConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Override;

/**
 * Service class to read global storages applicable to the user
 * Read-only access available, attempting to write will throw DomainException
 */
class UserGlobalStoragesService extends GlobalStoragesService {
	use UserTrait;

	public function __construct(
		BackendService $backendService,
		DBConfigService $dbConfig,
		IUserSession $userSession,
		protected IGroupManager $groupManager,
		IEventDispatcher $eventDispatcher,
		IAppConfig $appConfig,
	) {
		parent::__construct($backendService, $dbConfig, $eventDispatcher, $appConfig);
		$this->userSession = $userSession;
	}

	#[Override]
	protected function readDBConfig(): array {
		$userMounts = $this->dbConfig->getAdminMountsFor(DBConfigService::APPLICABLE_TYPE_USER, $this->getUser()->getUID());
		$globalMounts = $this->dbConfig->getAdminMountsFor(DBConfigService::APPLICABLE_TYPE_GLOBAL, null);
		$groups = $this->groupManager->getUserGroupIds($this->getUser());
		if (count($groups) !== 0) {
			$groupMounts = $this->dbConfig->getAdminMountsForMultiple(DBConfigService::APPLICABLE_TYPE_GROUP, $groups);
		} else {
			$groupMounts = [];
		}
		return array_merge($userMounts, $groupMounts, $globalMounts);
	}

	#[Override]
	public function addStorage(StorageConfig $newStorage): never {
		throw new \DomainException('UserGlobalStoragesService writing disallowed');
	}

	#[Override]
	public function updateStorage(StorageConfig $updatedStorage): never {
		throw new \DomainException('UserGlobalStoragesService writing disallowed');
	}

	#[Override]
	public function removeStorage(int $id): never {
		throw new \DomainException('UserGlobalStoragesService writing disallowed');
	}

	/**
	 * Get unique storages, in case two are defined with the same mountpoint
	 * Higher priority storages take precedence
	 *
	 * @return StorageConfig[]
	 */
	public function getUniqueStorages(): array {
		$storages = $this->getStorages();

		$storagesByMountpoint = [];
		foreach ($storages as $storage) {
			$storagesByMountpoint[$storage->getMountPoint()][] = $storage;
		}

		$result = [];
		foreach ($storagesByMountpoint as $storageList) {
			$storage = array_reduce($storageList, function ($carry, $item) {
				if (isset($carry)) {
					$carryPriorityType = $this->getPriorityType($carry);
					$itemPriorityType = $this->getPriorityType($item);
					if ($carryPriorityType > $itemPriorityType) {
						return $carry;
					} elseif ($carryPriorityType === $itemPriorityType) {
						if ($carry->getPriority() > $item->getPriority()) {
							return $carry;
						}
					}
				}
				return $item;
			});
			$result[$storage->getID()] = $storage;
		}

		return $result;
	}

	/**
	 * Get a priority 'type', where a bigger number means higher priority
	 * user applicable > group applicable > 'all'
	 *
	 * @param StorageConfig $storage
	 * @return int
	 */
	protected function getPriorityType(StorageConfig $storage): int {
		$applicableUsers = $storage->getApplicableUsers();
		$applicableGroups = $storage->getApplicableGroups();

		if ($applicableUsers && $applicableUsers[0] !== 'all') {
			return 2;
		}
		return $applicableGroups ? 1 : 0;
	}

	#[\Override]
	protected function isApplicable(StorageConfig $config): bool {
		$applicableUsers = $config->getApplicableUsers();
		$applicableGroups = $config->getApplicableGroups();

		if (count($applicableUsers) === 0 && count($applicableGroups) === 0) {
			return true;
		}
		if (in_array($this->getUser()->getUID(), $applicableUsers, true)) {
			return true;
		}
		$groupIds = $this->groupManager->getUserGroupIds($this->getUser());
		foreach ($groupIds as $groupId) {
			if (in_array($groupId, $applicableGroups, true)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Gets all storages for the user, admin, personal, global, etc
	 *
	 * @param IUser|null $user user to get the storages for, if not set the currently logged in user will be used
	 * @return StorageConfig[] array of storage configs
	 */
	public function getAllStoragesForUser(?IUser $user = null): array {
		if (is_null($user)) {
			$user = $this->getUser();
		}
		if (is_null($user)) {
			return [];
		}
		$groupIds = $this->groupManager->getUserGroupIds($user);
		$mounts = $this->dbConfig->getMountsForUser($user->getUID(), $groupIds);
		$configs = array_map($this->getStorageConfigFromDBMount(...), $mounts);
		$configs = array_filter($configs, static fn ($config) => $config instanceof StorageConfig);

		$keys = array_map(static fn (StorageConfig $config) => $config->getId(), $configs);

		$storages = array_combine($keys, $configs);
		return array_filter($storages, $this->validateStorage(...));
	}


	/**
	 * @return StorageConfig[]
	 */
	public function getAllStoragesForUserWithPath(string $path, bool $forChildren): array {
		$user = $this->getUser();

		if (is_null($user)) {
			return [];
		}

		$groupIds = $this->groupManager->getUserGroupIds($user);
		$mounts = $this->dbConfig->getMountsForUserAndPath($user->getUID(), $groupIds, $path, $forChildren);
		$configs = array_map($this->getStorageConfigFromDBMount(...), $mounts);
		$configs = array_filter($configs, static fn ($config) => $config instanceof StorageConfig);
		$keys = array_map(static fn (StorageConfig $config) => $config->getId(), $configs);

		$storages = array_combine($keys, $configs);
		return array_filter($storages, $this->validateStorage(...));
	}
}
