<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Share20;

use OCP\Cache\CappedMemoryCache;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * split of from the share manager to allow using it with minimal DI
 */
class ShareDisableChecker {
	private CappedMemoryCache $sharingDisabledForUsersCache;

	public function __construct(
		private IConfig $config,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
	) {
		$this->sharingDisabledForUsersCache = new CappedMemoryCache();
	}

	public function sharingDisabledForUser(?string $userId): bool {
		if ($userId === null) {
			return false;
		}

		if (isset($this->sharingDisabledForUsersCache[$userId])) {
			return $this->sharingDisabledForUsersCache[$userId];
		}

		$excludeGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups', 'no');

		if ($excludeGroups && $excludeGroups !== 'no') {
			$groupsList = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = json_decode($groupsList);
			if (is_null($excludedGroups)) {
				$excludedGroups = explode(',', $groupsList);
				$newValue = json_encode($excludedGroups);
				$this->config->setAppValue('core', 'shareapi_exclude_groups_list', $newValue);
			}
			$user = $this->userManager->get($userId);
			if (!$user) {
				return false;
			}
			$usersGroups = $this->groupManager->getUserGroupIds($user);
			if ($excludeGroups !== 'allow') {
				if (!empty($usersGroups)) {
					$remainingGroups = array_diff($usersGroups, $excludedGroups);
					// if the user is only in groups which are disabled for sharing then
					// sharing is also disabled for the user
					if (empty($remainingGroups)) {
						$this->sharingDisabledForUsersCache[$userId] = true;
						return true;
					}
				}
			} else {
				if (!empty($usersGroups)) {
					$remainingGroups = array_intersect($usersGroups, $excludedGroups);
					// if the user is in any group which is allowed for sharing then
					// sharing is also allowed for the user
					if (!empty($remainingGroups)) {
						$this->sharingDisabledForUsersCache[$userId] = false;
						return false;
					}
				}
				$this->sharingDisabledForUsersCache[$userId] = true;
				return true;
			}
		}

		$this->sharingDisabledForUsersCache[$userId] = false;
		return false;
	}
}
