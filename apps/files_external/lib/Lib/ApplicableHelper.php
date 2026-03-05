<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Lib;

use OC\User\LazyUser;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

class ApplicableHelper {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IGroupManager $groupManager,
	) {
	}

	/**
	 * Get all users that have access to a storage
	 *
	 * @return \Iterator<string, IUser>
	 */
	public function getUsersForStorage(StorageConfig $storage): \Iterator {
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
				foreach ($group->getUsers() as $user) {
					if (!isset($yielded[$user->getUID()])) {
						$yielded[$user->getUID()] = true;
						yield $user->getUID() => $user;
					}
				}
			}
		}
	}

	public function isApplicableForUser(StorageConfig $storage, IUser $user): bool {
		if (count($storage->getApplicableUsers()) + count($storage->getApplicableGroups()) === 0) {
			return true;
		}
		if (in_array($user->getUID(), $storage->getApplicableUsers())) {
			return true;
		}
		$groupIds = $this->groupManager->getUserGroupIds($user);
		foreach ($groupIds as $groupId) {
			if (in_array($groupId, $storage->getApplicableGroups())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return all users that are applicable for storage $a, but not for $b
	 *
	 * @return \Iterator<IUser>
	 */
	public function diffApplicable(StorageConfig $a, StorageConfig $b): \Iterator {
		$aIsAll = count($a->getApplicableUsers()) + count($a->getApplicableGroups()) === 0;
		$bIsAll = count($b->getApplicableUsers()) + count($b->getApplicableGroups()) === 0;
		if ($bIsAll) {
			return;
		}

		if ($aIsAll) {
			foreach ($this->getUsersForStorage($a) as $user) {
				if (!$this->isApplicableForUser($b, $user)) {
					yield $user;
				}
			}
		} else {
			$yielded = [];
			foreach ($a->getApplicableGroups() as $groupId) {
				if (!in_array($groupId, $b->getApplicableGroups())) {
					$group = $this->groupManager->get($groupId);
					if ($group) {
						foreach ($group->getUsers() as $user) {
							if (!$this->isApplicableForUser($b, $user)) {
								if (!isset($yielded[$user->getUID()])) {
									$yielded[$user->getUID()] = true;
									yield $user;
								}
							}
						}
					}
				}
			}
			foreach ($a->getApplicableUsers() as $userId) {
				if (!in_array($userId, $b->getApplicableUsers())) {
					$user = $this->userManager->get($userId);
					if ($user && !$this->isApplicableForUser($b, $user)) {
						if (!isset($yielded[$user->getUID()])) {
							$yielded[$user->getUID()] = true;
							yield $user;
						}
					}
				}
			}
		}
	}
}
