<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\App;

use OC\AppConfig;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\AppPathNotFoundException;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\IAppManager;
use OCP\App\ManagerEvent;
use OCP\Collaboration\AutoComplete\IManager as IAutoCompleteManager;
use OCP\Collaboration\Collaborators\ISearch as ICollaboratorSearch;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\ServerVersion;
use OCP\Settings\IManager as ISettingsManager;
use Psr\Log\LoggerInterface;

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

	/** @var string[] $appId => $enabled */
	private array $enabledAppsCache = [];

	/** @var string[]|null */
	private ?array $shippedApps = null;

	private array $alwaysEnabled = [];
	private array $defaultEnabled = [];

	/** @var array */
	private array $appInfos = [];

	/** @var array */
	private array $appVersions = [];

	/** @var array */
	private array $autoDisabledApps = [];
	private array $appTypes = [];

	/** @var array<string, true> */
	private array $loadedApps = [];

	private ?AppConfig $appConfig = null;
	private ?IURLGenerator $urlGenerator = null;
	private ?INavigationManager $navigationManager = null;

	/**
	 * Be extremely careful when injecting classes here. The AppManager is used by the installer,
	 * so it needs to work before installation. See how AppConfig and IURLGenerator are injected for reference
	 */
	public function __construct(
		private IUserSession $userSession,
		private IConfig $config,
		private IGroupManager $groupManager,
		private ICacheFactory $memCacheFactory,
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
		private ServerVersion $serverVersion,
	) {
	}

	private function getNavigationManager(): INavigationManager {
		if ($this->navigationManager === null) {
			$this->navigationManager = \OCP\Server::get(INavigationManager::class);
		}
		return $this->navigationManager;
	}

	public function getAppIcon(string $appId, bool $dark = false): ?string {
		$possibleIcons = $dark ? [$appId . '-dark.svg', 'app-dark.svg'] : [$appId . '.svg', 'app.svg'];
		$icon = null;
		foreach ($possibleIcons as $iconName) {
			try {
				$icon = $this->getUrlGenerator()->imagePath($appId, $iconName);
				break;
			} catch (\RuntimeException $e) {
				// ignore
			}
		}
		return $icon;
	}

	private function getAppConfig(): AppConfig {
		if ($this->appConfig !== null) {
			return $this->appConfig;
		}
		if (!$this->config->getSystemValueBool('installed', false)) {
			throw new \Exception('Nextcloud is not installed yet, AppConfig is not available');
		}
		$this->appConfig = \OCP\Server::get(AppConfig::class);
		return $this->appConfig;
	}

	private function getUrlGenerator(): IURLGenerator {
		if ($this->urlGenerator !== null) {
			return $this->urlGenerator;
		}
		if (!$this->config->getSystemValueBool('installed', false)) {
			throw new \Exception('Nextcloud is not installed yet, AppConfig is not available');
		}
		$this->urlGenerator = \OCP\Server::get(IURLGenerator::class);
		return $this->urlGenerator;
	}

	/**
	 * For all enabled apps, return the value of their 'enabled' config key.
	 *
	 * @return array<string,string> appId => enabled (may be 'yes', or a json encoded list of group ids)
	 */
	private function getEnabledAppsValues(): array {
		if (!$this->enabledAppsCache) {
			/** @var array<string,string> */
			$values = $this->getAppConfig()->searchValues('enabled', false, IAppConfig::VALUE_STRING);

			$alwaysEnabledApps = $this->getAlwaysEnabledApps();
			foreach ($alwaysEnabledApps as $appId) {
				$values[$appId] = 'yes';
			}

			$this->enabledAppsCache = array_filter($values, function ($value) {
				return $value !== 'no';
			});
			ksort($this->enabledAppsCache);
		}
		return $this->enabledAppsCache;
	}

	/**
	 * Deprecated alias
	 *
	 * @return string[]
	 */
	public function getInstalledApps() {
		return $this->getEnabledApps();
	}

	/**
	 * List all enabled apps, either for everyone or for some groups
	 *
	 * @return list<string>
	 */
	public function getEnabledApps(): array {
		return array_keys($this->getEnabledAppsValues());
	}

	/**
	 * Get a list of all apps in the apps folder
	 *
	 * @return list<string> an array of app names (string IDs)
	 */
	public function getAllAppsInAppsFolders(): array {
		$apps = [];

		foreach (\OC::$APPSROOTS as $apps_dir) {
			if (!is_readable($apps_dir['path'])) {
				$this->logger->warning('unable to read app folder : ' . $apps_dir['path'], ['app' => 'core']);
				continue;
			}
			$dh = opendir($apps_dir['path']);

			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					if (
						$file[0] != '.' &&
						is_dir($apps_dir['path'] . '/' . $file) &&
						is_file($apps_dir['path'] . '/' . $file . '/appinfo/info.xml')
					) {
						$apps[] = $file;
					}
				}
			}
		}

		return array_values(array_unique($apps));
	}

	/**
	 * List all apps enabled for a user
	 *
	 * @param \OCP\IUser $user
	 * @return list<string>
	 */
	public function getEnabledAppsForUser(IUser $user) {
		$apps = $this->getEnabledAppsValues();
		$appsForUser = array_filter($apps, function ($enabled) use ($user) {
			return $this->checkAppForUser($enabled, $user);
		});
		return array_keys($appsForUser);
	}

	public function getEnabledAppsForGroup(IGroup $group): array {
		$apps = $this->getEnabledAppsValues();
		$appsForGroups = array_filter($apps, function ($enabled) use ($group) {
			return $this->checkAppForGroups($enabled, $group);
		});
		return array_keys($appsForGroups);
	}

	/**
	 * Loads all apps
	 *
	 * @param string[] $types
	 * @return bool
	 *
	 * This function walks through the Nextcloud directory and loads all apps
	 * it can find. A directory contains an app if the file /appinfo/info.xml
	 * exists.
	 *
	 * if $types is set to non-empty array, only apps of those types will be loaded
	 */
	public function loadApps(array $types = []): bool {
		if ($this->config->getSystemValueBool('maintenance', false)) {
			return false;
		}
		// Load the enabled apps here
		$apps = \OC_App::getEnabledApps();

		// Add each apps' folder as allowed class path
		foreach ($apps as $app) {
			// If the app is already loaded then autoloading it makes no sense
			if (!$this->isAppLoaded($app)) {
				$path = \OC_App::getAppPath($app);
				if ($path !== false) {
					\OC_App::registerAutoloading($app, $path);
				}
			}
		}

		// prevent app loading from printing output
		ob_start();
		foreach ($apps as $app) {
			if (!$this->isAppLoaded($app) && ($types === [] || $this->isType($app, $types))) {
				try {
					$this->loadApp($app);
				} catch (\Throwable $e) {
					$this->logger->emergency('Error during app loading: ' . $e->getMessage(), [
						'exception' => $e,
						'app' => $app,
					]);
				}
			}
		}
		ob_end_clean();

		return true;
	}

	/**
	 * check if an app is of a specific type
	 *
	 * @param string $app
	 * @param array $types
	 * @return bool
	 */
	public function isType(string $app, array $types): bool {
		$appTypes = $this->getAppTypes($app);
		foreach ($types as $type) {
			if (in_array($type, $appTypes, true)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * get the types of an app
	 *
	 * @param string $app
	 * @return string[]
	 */
	private function getAppTypes(string $app): array {
		//load the cache
		if (count($this->appTypes) === 0) {
			$this->appTypes = $this->getAppConfig()->getValues(false, 'types') ?: [];
		}

		if (isset($this->appTypes[$app])) {
			return explode(',', $this->appTypes[$app]);
		}

		return [];
	}

	/**
	 * @return array
	 */
	public function getAutoDisabledApps(): array {
		return $this->autoDisabledApps;
	}

	public function getAppRestriction(string $appId): array {
		$values = $this->getEnabledAppsValues();

		if (!isset($values[$appId])) {
			return [];
		}

		if ($values[$appId] === 'yes' || $values[$appId] === 'no') {
			return [];
		}
		return json_decode($values[$appId], true);
	}

	/**
	 * Check if an app is enabled for user
	 *
	 * @param string $appId
	 * @param \OCP\IUser|null $user (optional) if not defined, the currently logged in user will be used
	 * @return bool
	 */
	public function isEnabledForUser($appId, $user = null) {
		if ($this->isAlwaysEnabled($appId)) {
			return true;
		}
		if ($user === null) {
			$user = $this->userSession->getUser();
		}
		$enabledAppsValues = $this->getEnabledAppsValues();
		if (isset($enabledAppsValues[$appId])) {
			return $this->checkAppForUser($enabledAppsValues[$appId], $user);
		} else {
			return false;
		}
	}

	private function checkAppForUser(string $enabled, ?IUser $user): bool {
		if ($enabled === 'yes') {
			return true;
		} elseif ($user === null) {
			return false;
		} else {
			if (empty($enabled)) {
				return false;
			}

			$groupIds = json_decode($enabled);

			if (!is_array($groupIds)) {
				$jsonError = json_last_error();
				$jsonErrorMsg = json_last_error_msg();
				// this really should never happen (if it does, the admin should check the `enabled` key value via `occ config:list` because it's bogus for some reason)
				$this->logger->warning('AppManager::checkAppForUser - can\'t decode group IDs listed in app\'s enabled config key: ' . print_r($enabled, true) . ' - JSON error (' . $jsonError . ') ' . $jsonErrorMsg);
				return false;
			}

			$userGroups = $this->groupManager->getUserGroupIds($user);
			foreach ($userGroups as $groupId) {
				if (in_array($groupId, $groupIds, true)) {
					return true;
				}
			}
			return false;
		}
	}

	private function checkAppForGroups(string $enabled, IGroup $group): bool {
		if ($enabled === 'yes') {
			return true;
		} else {
			if (empty($enabled)) {
				return false;
			}

			$groupIds = json_decode($enabled);

			if (!is_array($groupIds)) {
				$jsonError = json_last_error();
				$jsonErrorMsg = json_last_error_msg();
				// this really should never happen (if it does, the admin should check the `enabled` key value via `occ config:list` because it's bogus for some reason)
				$this->logger->warning('AppManager::checkAppForGroups - can\'t decode group IDs listed in app\'s enabled config key: ' . print_r($enabled, true) . ' - JSON error (' . $jsonError . ') ' . $jsonErrorMsg);
				return false;
			}

			return in_array($group->getGID(), $groupIds);
		}
	}

	/**
	 * Check if an app is enabled in the instance
	 *
	 * Notice: This actually checks if the app is enabled and not only if it is installed.
	 *
	 * @param string $appId
	 */
	public function isInstalled($appId): bool {
		return $this->isEnabledForAnyone($appId);
	}

	public function isEnabledForAnyone(string $appId): bool {
		$enabledAppsValues = $this->getEnabledAppsValues();
		return isset($enabledAppsValues[$appId]);
	}

	/**
	 * Overwrite the `max-version` requirement for this app.
	 */
	public function overwriteNextcloudRequirement(string $appId): void {
		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		if (!in_array($appId, $ignoreMaxApps, true)) {
			$ignoreMaxApps[] = $appId;
		}
		$this->config->setSystemValue('app_install_overwrite', $ignoreMaxApps);
	}

	/**
	 * Remove the `max-version` overwrite for this app.
	 * This means this app now again can not be enabled if the `max-version` is smaller than the current Nextcloud version.
	 */
	public function removeOverwriteNextcloudRequirement(string $appId): void {
		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		$ignoreMaxApps = array_filter($ignoreMaxApps, fn (string $id) => $id !== $appId);
		$this->config->setSystemValue('app_install_overwrite', $ignoreMaxApps);
	}

	public function loadApp(string $app): void {
		if (isset($this->loadedApps[$app])) {
			return;
		}
		$this->loadedApps[$app] = true;
		$appPath = \OC_App::getAppPath($app);
		if ($appPath === false) {
			return;
		}
		$eventLogger = \OC::$server->get(IEventLogger::class);
		$eventLogger->start("bootstrap:load_app:$app", "Load app: $app");

		// in case someone calls loadApp() directly
		\OC_App::registerAutoloading($app, $appPath);

		if (is_file($appPath . '/appinfo/app.php')) {
			$this->logger->error('/appinfo/app.php is not supported anymore, use \OCP\AppFramework\Bootstrap\IBootstrap on the application class instead.', [
				'app' => $app,
			]);
		}

		$coordinator = \OCP\Server::get(Coordinator::class);
		$coordinator->bootApp($app);

		$eventLogger->start("bootstrap:load_app:$app:info", "Load info.xml for $app and register any services defined in it");
		$info = $this->getAppInfo($app);
		if (!empty($info['activity'])) {
			$activityManager = \OC::$server->get(IActivityManager::class);
			if (!empty($info['activity']['filters'])) {
				foreach ($info['activity']['filters'] as $filter) {
					$activityManager->registerFilter($filter);
				}
			}
			if (!empty($info['activity']['settings'])) {
				foreach ($info['activity']['settings'] as $setting) {
					$activityManager->registerSetting($setting);
				}
			}
			if (!empty($info['activity']['providers'])) {
				foreach ($info['activity']['providers'] as $provider) {
					$activityManager->registerProvider($provider);
				}
			}
		}

		if (!empty($info['settings'])) {
			$settingsManager = \OC::$server->get(ISettingsManager::class);
			if (!empty($info['settings']['admin'])) {
				foreach ($info['settings']['admin'] as $setting) {
					$settingsManager->registerSetting('admin', $setting);
				}
			}
			if (!empty($info['settings']['admin-section'])) {
				foreach ($info['settings']['admin-section'] as $section) {
					$settingsManager->registerSection('admin', $section);
				}
			}
			if (!empty($info['settings']['personal'])) {
				foreach ($info['settings']['personal'] as $setting) {
					$settingsManager->registerSetting('personal', $setting);
				}
			}
			if (!empty($info['settings']['personal-section'])) {
				foreach ($info['settings']['personal-section'] as $section) {
					$settingsManager->registerSection('personal', $section);
				}
			}
		}

		if (!empty($info['collaboration']['plugins'])) {
			// deal with one or many plugin entries
			$plugins = isset($info['collaboration']['plugins']['plugin']['@value']) ?
				[$info['collaboration']['plugins']['plugin']] : $info['collaboration']['plugins']['plugin'];
			$collaboratorSearch = null;
			$autoCompleteManager = null;
			foreach ($plugins as $plugin) {
				if ($plugin['@attributes']['type'] === 'collaborator-search') {
					$pluginInfo = [
						'shareType' => $plugin['@attributes']['share-type'],
						'class' => $plugin['@value'],
					];
					$collaboratorSearch ??= \OC::$server->get(ICollaboratorSearch::class);
					$collaboratorSearch->registerPlugin($pluginInfo);
				} elseif ($plugin['@attributes']['type'] === 'autocomplete-sort') {
					$autoCompleteManager ??= \OC::$server->get(IAutoCompleteManager::class);
					$autoCompleteManager->registerSorter($plugin['@value']);
				}
			}
		}
		$eventLogger->end("bootstrap:load_app:$app:info");

		$eventLogger->end("bootstrap:load_app:$app");
	}

	/**
	 * Check if an app is loaded
	 * @param string $app app id
	 * @since 26.0.0
	 */
	public function isAppLoaded(string $app): bool {
		return isset($this->loadedApps[$app]);
	}

	/**
	 * Enable an app for every user
	 *
	 * @param string $appId
	 * @param bool $forceEnable
	 * @throws AppPathNotFoundException
	 * @throws \InvalidArgumentException if the application is not installed yet
	 */
	public function enableApp(string $appId, bool $forceEnable = false): void {
		// Check if app exists
		$this->getAppPath($appId);

		if ($this->config->getAppValue($appId, 'installed_version', '') === '') {
			throw new \InvalidArgumentException("$appId is not installed, cannot be enabled.");
		}

		if ($forceEnable) {
			$this->overwriteNextcloudRequirement($appId);
		}

		$this->enabledAppsCache[$appId] = 'yes';
		$this->getAppConfig()->setValue($appId, 'enabled', 'yes');
		$this->dispatcher->dispatchTyped(new AppEnableEvent($appId));
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_ENABLE, new ManagerEvent(
			ManagerEvent::EVENT_APP_ENABLE, $appId
		));
		$this->clearAppsCache();
	}

	/**
	 * Whether a list of types contains a protected app type
	 *
	 * @param string[] $types
	 * @return bool
	 */
	public function hasProtectedAppType($types) {
		if (empty($types)) {
			return false;
		}

		$protectedTypes = array_intersect($this->protectedAppTypes, $types);
		return !empty($protectedTypes);
	}

	/**
	 * Enable an app only for specific groups
	 *
	 * @param string $appId
	 * @param IGroup[] $groups
	 * @param bool $forceEnable
	 * @throws \InvalidArgumentException if app can't be enabled for groups
	 * @throws AppPathNotFoundException
	 */
	public function enableAppForGroups(string $appId, array $groups, bool $forceEnable = false): void {
		// Check if app exists
		$this->getAppPath($appId);

		$info = $this->getAppInfo($appId);
		if (!empty($info['types']) && $this->hasProtectedAppType($info['types'])) {
			throw new \InvalidArgumentException("$appId can't be enabled for groups.");
		}

		if ($this->config->getAppValue($appId, 'installed_version', '') === '') {
			throw new \InvalidArgumentException("$appId is not installed, cannot be enabled.");
		}

		if ($forceEnable) {
			$this->overwriteNextcloudRequirement($appId);
		}

		/** @var string[] $groupIds */
		$groupIds = array_map(function ($group) {
			/** @var IGroup $group */
			return ($group instanceof IGroup)
				? $group->getGID()
				: $group;
		}, $groups);

		$this->enabledAppsCache[$appId] = json_encode($groupIds);
		$this->getAppConfig()->setValue($appId, 'enabled', json_encode($groupIds));
		$this->dispatcher->dispatchTyped(new AppEnableEvent($appId, $groupIds));
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, new ManagerEvent(
			ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS, $appId, $groups
		));
		$this->clearAppsCache();
	}

	/**
	 * Disable an app for every user
	 *
	 * @param string $appId
	 * @param bool $automaticDisabled
	 * @throws \Exception if app can't be disabled
	 */
	public function disableApp($appId, $automaticDisabled = false): void {
		if ($this->isAlwaysEnabled($appId)) {
			throw new \Exception("$appId can't be disabled.");
		}

		if ($automaticDisabled) {
			$previousSetting = $this->getAppConfig()->getValue($appId, 'enabled', 'yes');
			if ($previousSetting !== 'yes' && $previousSetting !== 'no') {
				$previousSetting = json_decode($previousSetting, true);
			}
			$this->autoDisabledApps[$appId] = $previousSetting;
		}

		unset($this->enabledAppsCache[$appId]);
		$this->getAppConfig()->setValue($appId, 'enabled', 'no');

		// run uninstall steps
		$appData = $this->getAppInfo($appId);
		if (!is_null($appData)) {
			\OC_App::executeRepairSteps($appId, $appData['repair-steps']['uninstall']);
		}

		$this->dispatcher->dispatchTyped(new AppDisableEvent($appId));
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_DISABLE, new ManagerEvent(
			ManagerEvent::EVENT_APP_DISABLE, $appId
		));
		$this->clearAppsCache();
	}

	/**
	 * Get the directory for the given app.
	 *
	 * @throws AppPathNotFoundException if app folder can't be found
	 */
	public function getAppPath(string $appId): string {
		$appPath = \OC_App::getAppPath($appId);
		if ($appPath === false) {
			throw new AppPathNotFoundException('Could not find path for ' . $appId);
		}
		return $appPath;
	}

	/**
	 * Get the web path for the given app.
	 *
	 * @param string $appId
	 * @return string
	 * @throws AppPathNotFoundException if app path can't be found
	 */
	public function getAppWebPath(string $appId): string {
		$appWebPath = \OC_App::getAppWebPath($appId);
		if ($appWebPath === false) {
			throw new AppPathNotFoundException('Could not find web path for ' . $appId);
		}
		return $appWebPath;
	}

	/**
	 * Clear the cached list of apps when enabling/disabling an app
	 */
	public function clearAppsCache(): void {
		$this->appInfos = [];
	}

	/**
	 * Returns a list of apps that need upgrade
	 *
	 * @param string $version Nextcloud version as array of version components
	 * @return array list of app info from apps that need an upgrade
	 *
	 * @internal
	 */
	public function getAppsNeedingUpgrade($version) {
		$appsToUpgrade = [];
		$apps = $this->getEnabledApps();
		foreach ($apps as $appId) {
			$appInfo = $this->getAppInfo($appId);
			$appDbVersion = $this->getAppConfig()->getValue($appId, 'installed_version');
			if ($appDbVersion
				&& isset($appInfo['version'])
				&& version_compare($appInfo['version'], $appDbVersion, '>')
				&& \OC_App::isAppCompatible($version, $appInfo)
			) {
				$appsToUpgrade[] = $appInfo;
			}
		}

		return $appsToUpgrade;
	}

	/**
	 * Returns the app information from "appinfo/info.xml".
	 *
	 * @param string|null $lang
	 * @return array|null app info
	 */
	public function getAppInfo(string $appId, bool $path = false, $lang = null) {
		if ($path) {
			throw new \InvalidArgumentException('Calling IAppManager::getAppInfo() with a path is no longer supported. Please call IAppManager::getAppInfoByPath() instead and verify that the path is good before calling.');
		}
		if ($lang === null && isset($this->appInfos[$appId])) {
			return $this->appInfos[$appId];
		}
		try {
			$appPath = $this->getAppPath($appId);
		} catch (AppPathNotFoundException) {
			return null;
		}
		$file = $appPath . '/appinfo/info.xml';

		$data = $this->getAppInfoByPath($file, $lang);

		if ($lang === null) {
			$this->appInfos[$appId] = $data;
		}

		return $data;
	}

	public function getAppInfoByPath(string $path, ?string $lang = null): ?array {
		if (!str_ends_with($path, '/appinfo/info.xml')) {
			return null;
		}

		$parser = new InfoParser($this->memCacheFactory->createLocal('core.appinfo'));
		$data = $parser->parse($path);

		if (is_array($data)) {
			$data = \OC_App::parseAppInfo($data, $lang);
		}

		return $data;
	}

	public function getAppVersion(string $appId, bool $useCache = true): string {
		if (!$useCache || !isset($this->appVersions[$appId])) {
			if ($appId === 'core') {
				$this->appVersions[$appId] = $this->serverVersion->getVersionString();
			} else {
				$appInfo = $this->getAppInfo($appId);
				$this->appVersions[$appId] = ($appInfo !== null && isset($appInfo['version'])) ? $appInfo['version'] : '0';
			}
		}
		return $this->appVersions[$appId];
	}

	/**
	 * Returns the installed versions of all apps
	 *
	 * @return array<string, string>
	 */
	public function getAppInstalledVersions(bool $onlyEnabled = false): array {
		return $this->getAppConfig()->getAppInstalledVersions($onlyEnabled);
	}

	/**
	 * Returns a list of apps incompatible with the given version
	 *
	 * @param string $version Nextcloud version as array of version components
	 *
	 * @return array list of app info from incompatible apps
	 *
	 * @internal
	 */
	public function getIncompatibleApps(string $version): array {
		$apps = $this->getEnabledApps();
		$incompatibleApps = [];
		foreach ($apps as $appId) {
			$info = $this->getAppInfo($appId);
			if ($info === null) {
				$incompatibleApps[] = ['id' => $appId, 'name' => $appId];
			} elseif (!\OC_App::isAppCompatible($version, $info)) {
				$incompatibleApps[] = $info;
			}
		}
		return $incompatibleApps;
	}

	/**
	 * @inheritdoc
	 * In case you change this method, also change \OC\App\CodeChecker\InfoChecker::isShipped()
	 */
	public function isShipped($appId) {
		$this->loadShippedJson();
		return in_array($appId, $this->shippedApps, true);
	}

	private function isAlwaysEnabled(string $appId): bool {
		if ($appId === 'core') {
			return true;
		}

		$alwaysEnabled = $this->getAlwaysEnabledApps();
		return in_array($appId, $alwaysEnabled, true);
	}

	/**
	 * In case you change this method, also change \OC\App\CodeChecker\InfoChecker::loadShippedJson()
	 * @throws \Exception
	 */
	private function loadShippedJson(): void {
		if ($this->shippedApps === null) {
			$shippedJson = \OC::$SERVERROOT . '/core/shipped.json';
			if (!file_exists($shippedJson)) {
				throw new \Exception("File not found: $shippedJson");
			}
			$content = json_decode(file_get_contents($shippedJson), true);
			$this->shippedApps = $content['shippedApps'];
			$this->alwaysEnabled = $content['alwaysEnabled'];
			$this->defaultEnabled = $content['defaultEnabled'];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getAlwaysEnabledApps() {
		$this->loadShippedJson();
		return $this->alwaysEnabled;
	}

	/**
	 * @inheritdoc
	 */
	public function isDefaultEnabled(string $appId): bool {
		return (in_array($appId, $this->getDefaultEnabledApps()));
	}

	/**
	 * @inheritdoc
	 */
	public function getDefaultEnabledApps(): array {
		$this->loadShippedJson();

		return $this->defaultEnabled;
	}

	/**
	 * @inheritdoc
	 */
	public function getDefaultAppForUser(?IUser $user = null, bool $withFallbacks = true): string {
		$id = $this->getNavigationManager()->getDefaultEntryIdForUser($user, $withFallbacks);
		$entry = $this->getNavigationManager()->get($id);
		return (string)$entry['app'];
	}

	/**
	 * @inheritdoc
	 */
	public function getDefaultApps(): array {
		$ids = $this->getNavigationManager()->getDefaultEntryIds();

		return array_values(array_unique(array_map(function (string $id) {
			$entry = $this->getNavigationManager()->get($id);
			return (string)$entry['app'];
		}, $ids)));
	}

	/**
	 * @inheritdoc
	 */
	public function setDefaultApps(array $defaultApps): void {
		$entries = $this->getNavigationManager()->getAll();
		$ids = [];
		foreach ($defaultApps as $defaultApp) {
			foreach ($entries as $entry) {
				if ((string)$entry['app'] === $defaultApp) {
					$ids[] = (string)$entry['id'];
					break;
				}
			}
		}
		$this->getNavigationManager()->setDefaultEntryIds($ids);
	}

	public function isBackendRequired(string $backend): bool {
		foreach ($this->appInfos as $appInfo) {
			foreach ($appInfo['dependencies']['backend'] as $appBackend) {
				if ($backend === $appBackend) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Clean the appId from forbidden characters
	 *
	 * @psalm-taint-escape callable
	 * @psalm-taint-escape cookie
	 * @psalm-taint-escape file
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape header
	 * @psalm-taint-escape html
	 * @psalm-taint-escape include
	 * @psalm-taint-escape ldap
	 * @psalm-taint-escape shell
	 * @psalm-taint-escape sql
	 * @psalm-taint-escape unserialize
	 */
	public function cleanAppId(string $app): string {
		/* Only lowercase alphanumeric is allowed */
		return preg_replace('/(^[0-9_]|[^a-z0-9_]+|_$)/', '', $app);
	}
}
