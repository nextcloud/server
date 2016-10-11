<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Schaefer <christophł@wolkesicher.de>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\App;

use OCP\App\IAppManager;
use OCP\App\ManagerEvent;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AppManager implements IAppManager {

	/**
	 * Apps with these types can not be enabled for certain groups only
	 * @var string[]
	 */
	protected $protectedAppTypes = [
		'filesystem',
		'prelogin',
		'authentication',
		'logging',
		'prevent_group_restriction',
	];

	/** @var \OCP\IUserSession */
	private $userSession;

	/** @var \OCP\IAppConfig */
	private $appConfig;

	/** @var \OCP\IGroupManager */
	private $groupManager;

	/** @var \OCP\ICacheFactory */
	private $memCacheFactory;

	/** @var string[] $appId => $enabled */
	private $installedAppsCache;

	/** @var string[] */
	private $shippedApps;

	/** @var string[] */
	private $alwaysEnabled;

	/** @var EventDispatcherInterface */
	private $dispatcher;

	/**
	 * @param \OCP\IUserSession $userSession
	 * @param \OCP\IAppConfig $appConfig
	 * @param \OCP\IGroupManager $groupManager
	 * @param \OCP\ICacheFactory $memCacheFactory
	 */
	public function __construct(IUserSession $userSession,
								IAppConfig $appConfig,
								IGroupManager $groupManager,
								ICacheFactory $memCacheFactory,
								EventDispatcherInterface $dispatcher) {
		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
		$this->groupManager = $groupManager;
		$this->memCacheFactory = $memCacheFactory;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @return string[] $appId => $enabled
	 */
	private function getInstalledAppsValues() {
		if (!$this->installedAppsCache) {
			$values = $this->appConfig->getValues(false, 'enabled');

			$alwaysEnabledApps = $this->getAlwaysEnabledApps();
			foreach($alwaysEnabledApps as $appId) {
				$values[$appId] = 'yes';
			}

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
	public function getEnabledAppsForUser(IUser $user) {
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
		if ($this->isAlwaysEnabled($appId)) {
			return true;
		}
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
			if(empty($enabled)){
				return false;
			}

			$groupIds = json_decode($enabled);

			if (!is_array($groupIds)) {
				$jsonError = json_last_error();
				\OC::$server->getLogger()->warning('AppManger::checkAppForUser - can\'t decode group IDs: ' . print_r($enabled, true) . ' - json error code: ' . $jsonError, ['app' => 'lib']);
				return false;
			}

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
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_ENABLE, new ManagerEvent(
			ManagerEvent::EVENT_APP_ENABLE, $appId
		));
		$this->clearAppsCache();
	}

	/**
	 * Enable an app only for specific groups
	 *
	 * @param string $appId
	 * @param \OCP\IGroup[] $groups
	 * @throws \Exception if app can't be enabled for groups
	 */
	public function enableAppForGroups($appId, $groups) {
		$info = $this->getAppInfo($appId);
		if (!empty($info['types'])) {
			$protectedTypes = array_intersect($this->protectedAppTypes, $info['types']);
			if (!empty($protectedTypes)) {
				throw new \Exception("$appId can't be enabled for groups.");
			}
		}

		$groupIds = array_map(function ($group) {
			/** @var \OCP\IGroup $group */
			return $group->getGID();
		}, $groups);
		$this->installedAppsCache[$appId] = json_encode($groupIds);
		$this->appConfig->setValue($appId, 'enabled', json_encode($groupIds));
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, new ManagerEvent(
			ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, $appId, $groups
		));
		$this->clearAppsCache();
	}

	/**
	 * Disable an app for every user
	 *
	 * @param string $appId
	 * @throws \Exception if app can't be disabled
	 */
	public function disableApp($appId) {
		if ($this->isAlwaysEnabled($appId)) {
			throw new \Exception("$appId can't be disabled.");
		}
		unset($this->installedAppsCache[$appId]);
		$this->appConfig->setValue($appId, 'enabled', 'no');
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_DISABLE, new ManagerEvent(
			ManagerEvent::EVENT_APP_DISABLE, $appId
		));
		$this->clearAppsCache();
	}

	/**
	 * Clear the cached list of apps when enabling/disabling an app
	 */
	public function clearAppsCache() {
		$settingsMemCache = $this->memCacheFactory->create('settings');
		$settingsMemCache->clear('listApps');
	}

	/**
	 * Returns a list of apps that need upgrade
	 *
	 * @param array $version ownCloud version as array of version components
	 * @return array list of app info from apps that need an upgrade
	 *
	 * @internal
	 */
	public function getAppsNeedingUpgrade($ocVersion) {
		$appsToUpgrade = [];
		$apps = $this->getInstalledApps();
		foreach ($apps as $appId) {
			$appInfo = $this->getAppInfo($appId);
			$appDbVersion = $this->appConfig->getValue($appId, 'installed_version');
			if ($appDbVersion
				&& isset($appInfo['version'])
				&& version_compare($appInfo['version'], $appDbVersion, '>')
				&& \OC_App::isAppCompatible($ocVersion, $appInfo)
			) {
				$appsToUpgrade[] = $appInfo;
			}
		}

		return $appsToUpgrade;
	}

	/**
	 * Returns the app information from "appinfo/info.xml".
	 *
	 * @param string $appId app id
	 *
	 * @return array app info
	 *
	 * @internal
	 */
	public function getAppInfo($appId) {
		$appInfo = \OC_App::getAppInfo($appId);
		if (!isset($appInfo['version'])) {
			// read version from separate file
			$appInfo['version'] = \OC_App::getAppVersion($appId);
		}
		return $appInfo;
	}

	/**
	 * Returns a list of apps incompatible with the given version
	 *
	 * @param array $version ownCloud version as array of version components
	 *
	 * @return array list of app info from incompatible apps
	 *
	 * @internal
	 */
	public function getIncompatibleApps($version) {
		$apps = $this->getInstalledApps();
		$incompatibleApps = array();
		foreach ($apps as $appId) {
			$info = $this->getAppInfo($appId);
			if (!\OC_App::isAppCompatible($version, $info)) {
				$incompatibleApps[] = $info;
			}
		}
		return $incompatibleApps;
	}

	/**
	 * @inheritdoc
	 */
	public function isShipped($appId) {
		$this->loadShippedJson();
		return in_array($appId, $this->shippedApps);
	}

	private function isAlwaysEnabled($appId) {
		$alwaysEnabled = $this->getAlwaysEnabledApps();
		return in_array($appId, $alwaysEnabled);
	}

	private function loadShippedJson() {
		if (is_null($this->shippedApps)) {
			$shippedJson = \OC::$SERVERROOT . '/core/shipped.json';
			if (!file_exists($shippedJson)) {
				throw new \Exception("File not found: $shippedJson");
			}
			$content = json_decode(file_get_contents($shippedJson), true);
			$this->shippedApps = $content['shippedApps'];
			$this->alwaysEnabled = $content['alwaysEnabled'];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getAlwaysEnabledApps() {
		$this->loadShippedJson();
		return $this->alwaysEnabled;
	}
}
