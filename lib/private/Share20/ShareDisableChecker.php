<?php

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


	/**
	 * @param ?string $userId
	 * @return bool
	 */
	public function sharingDisabledForUser(?string $userId) {
		if ($userId === null) {
			return false;
		}

		if (isset($this->sharingDisabledForUsersCache[$userId])) {
			return $this->sharingDisabledForUsersCache[$userId];
		}

		if ($this->config->getAppValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
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
			if (!empty($usersGroups)) {
				$remainingGroups = array_diff($usersGroups, $excludedGroups);
				// if the user is only in groups which are disabled for sharing then
				// sharing is also disabled for the user
				if (empty($remainingGroups)) {
					$this->sharingDisabledForUsersCache[$userId] = true;
					return true;
				}
			}
		}

		$this->sharingDisabledForUsersCache[$userId] = false;
		return false;
	}
}
