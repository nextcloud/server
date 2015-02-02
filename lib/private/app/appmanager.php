<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\App;

use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;

class AppManager implements IAppManager {
	/**
	 * @var \OCP\IUserSession
	 */
	private $userSession;

	/**
	 * @var \OCP\IAppConfig
	 */
	private $appConfig;

	/**
	 * @var \OCP\IGroupManager
	 */
	private $groupManager;

	/**
	 * @var string[] $appId => $enabled
	 */
	private $installedAppsCache;

	/**
	 * @param \OCP\IUserSession $userSession
	 * @param \OCP\IAppConfig $appConfig
	 * @param \OCP\IGroupManager $groupManager
	 */
	public function __construct(IUserSession $userSession, IAppConfig $appConfig, IGroupManager $groupManager) {
		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
		$this->groupManager = $groupManager;
	}

	/**
	 * @return string[] $appId => $enabled
	 */
	private function getInstalledApps() {
		if (!$this->installedAppsCache) {
			$values = $this->appConfig->getValues(false, 'enabled');
			$this->installedAppsCache = array_filter($values, function ($value) {
				return $value !== 'no';
			});
			ksort($this->installedAppsCache);
		}
		return $this->installedAppsCache;
	}

	/**
	 * Check if an app is enabled for user
	 *
	 * @param string $appId
	 * @param \OCP\IUser $user (optional) if not defined, the currently logged in user will be used
	 * @return bool
	 */
	public function isEnabledForUser($appId, $user = null) {
		if (is_null($user)) {
			$user = $this->userSession->getUser();
		}
		$installedApps = $this->getInstalledApps();
		if (isset($installedApps[$appId])) {
			$enabled = $installedApps[$appId];
			if ($enabled === 'yes') {
				return true;
			} elseif (is_null($user)) {
				return false;
			} else {
				$groupIds = json_decode($enabled);
				$userGroups = $this->groupManager->getUserGroupIds($user);
				foreach ($userGroups as $groupId) {
					if (array_search($groupId, $groupIds) !== false) {
						return true;
					}
				}
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Check if an app is installed in the instance
	 *
	 * @param string $appId
	 * @return bool
	 */
	public function isInstalled($appId) {
		$installedApps = $this->getInstalledApps();
		return isset($installedApps[$appId]);
	}

	/**
	 * Enable an app for every user
	 *
	 * @param string $appId
	 */
	public function enableApp($appId) {
		$this->appConfig->setValue($appId, 'enabled', 'yes');
	}

	/**
	 * Enable an app only for specific groups
	 *
	 * @param string $appId
	 * @param \OCP\IGroup[] $groups
	 */
	public function enableAppForGroups($appId, $groups) {
		$groupIds = array_map(function ($group) {
			/** @var \OCP\IGroup $group */
			return $group->getGID();
		}, $groups);
		$this->appConfig->setValue($appId, 'enabled', json_encode($groupIds));
	}

	/**
	 * Disable an app for every user
	 *
	 * @param string $appId
	 * @throws \Exception if app can't be disabled
	 */
	public function disableApp($appId) {
		if($appId === 'files') {
			throw new \Exception("files can't be disabled.");
		}
		$this->appConfig->setValue($appId, 'enabled', 'no');
	}
}
