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
use OCP\IUser;
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
	private function getInstalledAppsValues() {
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
	 * List all installed apps
	 *
	 * @return string[]
	 */
	public function getInstalledApps() {
		return array_keys($this->getInstalledAppsValues());
	}

	/**
	 * List all apps enabled for a user
	 *
	 * @param \OCP\IUser $user
	 * @return string[]
	 */
	public function getAppsEnabledForUser(IUser $user) {
		$apps = $this->getInstalledAppsValues();
		$appsForUser = array_filter($apps, function ($enabled) use ($user) {
			return $this->checkAppForUser($enabled, $user);
		});
		return array_keys($appsForUser);
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
		$installedApps = $this->getInstalledAppsValues();
		if (isset($installedApps[$appId])) {
			return $this->checkAppForUser($installedApps[$appId], $user);
		} else {
			return false;
		}
	}

	/**
	 * @param string $enabled
	 * @param IUser $user
	 * @return bool
	 */
	private function checkAppForUser($enabled, $user) {
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
	}

	/**
	 * Check if an app is installed in the instance
	 *
	 * @param string $appId
	 * @return bool
	 */
	public function isInstalled($appId) {
		$installedApps = $this->getInstalledAppsValues();
		return isset($installedApps[$appId]);
	}

	/**
	 * Enable an app for every user
	 *
	 * @param string $appId
	 */
	public function enableApp($appId) {
		$this->installedAppsCache[$appId] = 'yes';
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
		$this->installedAppsCache[$appId] = json_encode($groupIds);
		$this->appConfig->setValue($appId, 'enabled', json_encode($groupIds));
	}

	/**
	 * Disable an app for every user
	 *
	 * @param string $appId
	 * @throws \Exception if app can't be disabled
	 */
	public function disableApp($appId) {
		if ($appId === 'files') {
			throw new \Exception("files can't be disabled.");
		}
		unset($this->installedAppsCache[$appId]);
		$this->appConfig->setValue($appId, 'enabled', 'no');
	}
}
