<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Share20;

use OCP\Cache\CappedMemoryCache;
use OCP\IAppConfig;
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
		private IAppConfig $appConfig,
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

		$excludeGroups = $this->appConfig->getValue('core', 'shareapi_exclude_groups', 'no');

		if ($excludeGroups && $excludeGroups !== 'no') {
			$groupsList = $this->appConfig->getValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = json_decode($groupsList, true);
			if (is_null($excludedGroups)) {
				$excludedGroups = explode(',', $groupsList);
				$newValue = json_encode($excludedGroups);
				$this->appConfig->setValue('core', 'shareapi_exclude_groups_list', $newValue);
			}
			$user = $this->userManager->get($userId);
			if (!$user) {
				return false;
			}
			$usersGroups = $this->groupManager->getUserGroupIds($user);
			$intersectingGroups = array_intersect($usersGroups, $excludedGroups);

			// 1. If the user is in a group which is disabled for sharing then
			//    sharing is also disabled for the user.
			// 2. If the user is in a group which is allowed for sharing then
			//    sharing is also allowed for the user.
			$isInList = $intersectingGroups !== [];
			$isBlockList = $excludeGroups !== 'allow';
			$sharingDisabled = $isBlockList ? $isInList : !$isInList;
			$this->sharingDisabledForUsersCache[$userId] = $sharingDisabled;
			return $sharingDisabled;
		}

		$this->sharingDisabledForUsersCache[$userId] = false;
		return false;
	}
}
