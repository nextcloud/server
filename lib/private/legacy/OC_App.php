<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OC\App\AppManager;
use OC\Installer;
use OC\SystemConfig;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

/**
 * This class manages the apps. It allows them to register and integrate in the
 * Nextcloud ecosystem. Furthermore, this class is responsible for installing,
 * upgrading and removing apps.
 * @deprecated 36.0.0 There are no more non-deprecated methods in there
 */
class OC_App {
	/**
	 * @deprecated 36.0.0 use your own constant
	 */
	public const supportedApp = 300;
	/**
	 * @deprecated 36.0.0 use your own constant
	 */
	public const officialApp = 200;

	/**
	 * Check if an app is loaded
	 *
	 * @deprecated 27.0.0 use IAppManager::isAppLoaded
	 */
	public static function isAppLoaded(string $app): bool {
		return Server::get(IAppManager::class)->isAppLoaded($app);
	}

	/**
	 * loads all apps
	 *
	 * @param string[] $types
	 *
	 * This function walks through the Nextcloud directory and loads all apps
	 * it can find. A directory contains an app if the file /appinfo/info.xml
	 * exists.
	 *
	 * if $types is set to non-empty array, only apps of those types will be loaded
	 *
	 * @deprecated 29.0.0 use IAppManager::loadApps instead
	 */
	public static function loadApps(array $types = []): bool {
		if (!Server::get(SystemConfig::class)->getValue('installed', false)) {
			// This should be done before calling this method so that appmanager can be used
			return false;
		}
		return Server::get(IAppManager::class)->loadApps($types);
	}

	/**
	 * load a single app
	 *
	 * @throws Exception
	 * @deprecated 27.0.0 use IAppManager::loadApp
	 */
	public static function loadApp(string $app): void {
		Server::get(IAppManager::class)->loadApp($app);
	}

	/**
	 * Returns apps enabled for the current user.
	 *
	 * @param bool $forceRefresh whether to refresh the cache
	 * @param bool $all whether to return apps for all users, not only the
	 *                  currently logged in one
	 * @return list<string>
	 * @deprecated 32.0.0 - use {@see \OCP\App\IAppManager::getEnabledAppsForUser} or {@see \OC\OCP\AppIAppManager::getEnabledApps} instead
	 */
	public static function getEnabledApps(bool $forceRefresh = false, bool $all = false): array {
		if (!Server::get(SystemConfig::class)->getValue('installed', false)) {
			return [];
		}
		// in incognito mode or when logged out, $user will be false,
		// which is also the case during an upgrade
		$appManager = Server::get(IAppManager::class);
		if ($all) {
			$user = null;
		} else {
			$user = Server::get(IUserSession::class)->getUser();
		}

		if (is_null($user)) {
			$apps = $appManager->getEnabledApps();
		} else {
			$apps = $appManager->getEnabledAppsForUser($user);
		}
		$apps = array_filter($apps, function ($app) {
			return $app !== 'files';//we add this manually
		});
		sort($apps);
		array_unshift($apps, 'files');
		return $apps;
	}

	/**
	 * enables an app
	 *
	 * @param array $groups (optional) when set, only these groups will have access to the app
	 * @throws \Exception
	 * @deprecated 32.0.0 Use the installer and the app manager instead
	 *
	 * This function set an app as enabled in appconfig.
	 */
	public function enable(string $appId, array $groups = []): void {
		// Check if app is already downloaded
		/** @var Installer $installer */
		$installer = Server::get(Installer::class);
		$isDownloaded = $installer->isDownloaded($appId);

		if (!$isDownloaded) {
			$installer->downloadApp($appId);
		}

		$installer->installApp($appId);

		$appManager = Server::get(IAppManager::class);
		if ($groups !== []) {
			$groupManager = Server::get(IGroupManager::class);
			$groupsList = [];
			foreach ($groups as $group) {
				$groupItem = $groupManager->get($group);
				if ($groupItem instanceof IGroup) {
					$groupsList[] = $groupManager->get($group);
				}
			}
			$appManager->enableAppForGroups($appId, $groupsList);
		} else {
			$appManager->enableApp($appId);
		}
	}

	/**
	 * List all supported apps
	 *
	 * @deprecated 32.0.0 Use \OCP\Support\Subscription\IRegistry::delegateGetSupportedApps instead
	 */
	public function getSupportedApps(): array {
		$subscriptionRegistry = Server::get(IRegistry::class);
		$supportedApps = $subscriptionRegistry->delegateGetSupportedApps();
		return $supportedApps;
	}

	/**
	 * List all apps, this is used in apps.php
	 * @deprecated 36.0.0 Use your own logic around IAppManager, this is legacy specific formatting
	 */
	public function listAllApps(): array {
		$appManager = Server::get(IAppManager::class);

		$installedApps = $appManager->getAllAppsInAppsFolders();
		//we don't want to show configuration for these
		$blacklist = $appManager->getAlwaysEnabledApps();
		$appList = [];
		$langCode = Server::get(IFactory::class)->get('core')->getLanguageCode();
		$urlGenerator = Server::get(IURLGenerator::class);
		$supportedApps = $this->getSupportedApps();

		foreach ($installedApps as $app) {
			if (!in_array($app, $blacklist, true)) {
				$info = $appManager->getAppInfo($app, false, $langCode);
				if (!is_array($info)) {
					Server::get(LoggerInterface::class)->error('Could not read app info file for app "' . $app . '"', ['app' => 'core']);
					continue;
				}

				if (!isset($info['name'])) {
					Server::get(LoggerInterface::class)->error('App id "' . $app . '" has no name in appinfo', ['app' => 'core']);
					continue;
				}

				$enabled = Server::get(IConfig::class)->getAppValue($app, 'enabled', 'no');
				$info['groups'] = null;
				if ($enabled === 'yes') {
					$active = true;
				} elseif ($enabled === 'no') {
					$active = false;
				} else {
					$active = true;
					$info['groups'] = $enabled;
				}

				$info['active'] = $active;

				if ($appManager->isShipped($app)) {
					$info['internal'] = true;
					$info['level'] = self::officialApp;
					$info['removable'] = false;
				} else {
					$info['internal'] = false;
					$info['removable'] = true;
				}

				if (in_array($app, $supportedApps, true)) {
					$info['level'] = self::supportedApp;
				}

				$info['icon'] = $appManager->getAppIcon($app, dark: true)
					?? $appManager->getAppIcon($app);

				// fix documentation
				if (isset($info['documentation']) && is_array($info['documentation'])) {
					foreach ($info['documentation'] as $key => $url) {
						// If it is not an absolute URL we assume it is a key
						// i.e. admin-ldap will get converted to go.php?to=admin-ldap
						if (stripos($url, 'https://') !== 0 && stripos($url, 'http://') !== 0) {
							$url = $urlGenerator->linkToDocs($url);
						}

						$info['documentation'][$key] = $url;
					}
				}

				$info['license'] = $info['licence'];
				$info['version'] = $appManager->getAppVersion($app);
				$appList[] = $info;
			}
		}

		return $appList;
	}

	/**
	 * get the installed version of all apps
	 * @deprecated 32.0.0 Use IAppManager::getAppInstalledVersions or IAppConfig::getAppInstalledVersions instead
	 */
	public static function getAppVersions(): array {
		return Server::get(IAppConfig::class)->getAppInstalledVersions();
	}

	/**
	 * update the database for the app and call the update script
	 *
	 * @deprecated 32.0.0 Use IAppManager::upgradeApp instead
	 */
	public static function updateApp(string $appId): bool {
		try {
			return Server::get(AppManager::class)->upgradeApp($appId);
		} catch (AppPathNotFoundException $e) {
			return false;
		}
	}
}
