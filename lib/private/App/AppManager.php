<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\App;

use OC\AppConfig;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Config\ConfigManager;
use OC\DB\MigrationService;
use OC\Migration\BackgroundRepair;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\AppPathNotFoundException;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppUpdateEvent;
use OCP\App\IAppManager;
use OCP\App\ManagerEvent;
use OCP\BackgroundJob\IJobList;
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
use OCP\Server;
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

	/** @var array<string, array{path: string, url: string}> $appId => approot information */
	private array $appsDirCache = [];

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
	 * so it needs to work before installation. See how AppConfig and IURLGenerator are injected
	 * for reference.
	 */
	public function __construct(
		private IUserSession $userSession,
		private IConfig $config,
		private IGroupManager $groupManager,
		private ICacheFactory $memCacheFactory,
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
		private ServerVersion $serverVersion,
		private ConfigManager $configManager,
		private DependencyAnalyzer $dependencyAnalyzer,
	) {
	}

	private function getNavigationManager(): INavigationManager {
		if ($this->navigationManager === null) {
			$this->navigationManager = Server::get(INavigationManager::class);
		}
		return $this->navigationManager;
	}

	#[\Override]
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
		$this->appConfig = Server::get(AppConfig::class);
		return $this->appConfig;
	}

	private function getUrlGenerator(): IURLGenerator {
		if ($this->urlGenerator !== null) {
			return $this->urlGenerator;
		}
		if (!$this->config->getSystemValueBool('installed', false)) {
			throw new \Exception('Nextcloud is not installed yet, AppConfig is not available');
		}
		$this->urlGenerator = Server::get(IURLGenerator::class);
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
	 * @deprecated 32.0.0 Use either {@see self::getEnabledApps} or {@see self::getEnabledAppsForUser}
	 */
	#[\Override]
	public function getInstalledApps() {
		return $this->getEnabledApps();
	}

	#[\Override]
	public function getEnabledApps(): array {
		return array_keys($this->getEnabledAppsValues());
	}

	#[\Override]
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
						$file[0] !== '.'
						&& is_dir($apps_dir['path'] . '/' . $file)
						&& is_file($apps_dir['path'] . '/' . $file . '/appinfo/info.xml')
					) {
						$apps[] = $file;
					}
				}
			}
		}

		return array_values(array_unique($apps));
	}

	#[\Override]
	public function getEnabledAppsForUser(IUser $user) {
		$apps = $this->getEnabledAppsValues();
		$appsForUser = array_filter($apps, function ($enabled) use ($user) {
			return $this->checkAppForUser($enabled, $user);
		});
		return array_keys($appsForUser);
	}

	#[\Override]
	public function getEnabledAppsForGroup(IGroup $group): array {
		$apps = $this->getEnabledAppsValues();
		$appsForGroups = array_filter($apps, function ($enabled) use ($group) {
			return $this->checkAppForGroups($enabled, $group);
		});
		return array_keys($appsForGroups);
	}

	#[\Override]
	public function loadApps(array $types = []): bool {
		if ($this->config->getSystemValueBool('maintenance', false)) {
			return false;
		}
		// Load the enabled apps here
		$apps = \OC_App::getEnabledApps();

		// Add each apps' folder as allowed class path
		foreach ($apps as $app) {
			// If the app is already loaded then autoloading it makes no sense
			if (!$this->isAppLoaded($app) && ($types === [] || $this->isType($app, $types))) {
				try {
					$path = $this->getAppPath($app);
					\OC_App::registerAutoloading($app, $path);
				} catch (AppPathNotFoundException $e) {
					$this->logger->info('Error during app loading: ' . $e->getMessage(), [
						'exception' => $e,
						'app' => $app,
					]);
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

	#[\Override]
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

	public function getAutoDisabledApps(): array {
		return $this->autoDisabledApps;
	}

	#[\Override]
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

	#[\Override]
	public function isEnabledForUser(string $appId, ?IUser $user = null): bool {
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

	#[\Override]
	public function isInstalled(string $appId): bool {
		return $this->isEnabledForAnyone($appId);
	}

	#[\Override]
	public function isEnabledForAnyone(string $appId): bool {
		$enabledAppsValues = $this->getEnabledAppsValues();
		return isset($enabledAppsValues[$appId]);
	}

	/**
	 * Disables Nextcloud version compatibility checks for a specific app.
	 *
	 * Adds the app to the 'app_install_overwrite' list, allowing it to be 
	 * enabled even if the current Nextcloud version exceeds the app's 
	 * defined 'max-version'.
	 */
	public function overwriteNextcloudRequirement(string $appId): void {
		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		if (!in_array($appId, $ignoreMaxApps, true)) {
			$ignoreMaxApps[] = $appId;
		}
		$this->config->setSystemValue('app_install_overwrite', $ignoreMaxApps);
	}

	/**
	 * Restores Nextcloud version compatibility checks for a specific app.
	 *
	 * This removes the app from the 'app_install_overwrite' list, meaning it can
	 * no longer be enabled if its maximum supported version is lower than the
	 * current Nextcloud version.
	 */
	public function removeOverwriteNextcloudRequirement(string $appId): void {
		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		$ignoreMaxApps = array_filter($ignoreMaxApps, fn (string $id) => $id !== $appId);
		$this->config->setSystemValue('app_install_overwrite', $ignoreMaxApps);
	}

	#[\Override]
	public function loadApp(string $app): void {
		if (isset($this->loadedApps[$app])) {
			return;
		}
		$this->loadedApps[$app] = true;
		try {
			$appPath = $this->getAppPath($app);
		} catch (AppPathNotFoundException $e) {
			$this->logger->info('Error during app loading: ' . $e->getMessage(), [
				'exception' => $e,
				'app' => $app,
			]);
			return;
		}
		$eventLogger = Server::get(IEventLogger::class);
		$eventLogger->start("bootstrap:load_app:$app", "Load app: $app");

		// in case someone calls loadApp() directly
		\OC_App::registerAutoloading($app, $appPath);

		if (is_file($appPath . '/appinfo/app.php')) {
			$this->logger->error('/appinfo/app.php is not supported anymore, use \OCP\AppFramework\Bootstrap\IBootstrap on the application class instead.', [
				'app' => $app,
			]);
		}

		$coordinator = Server::get(Coordinator::class);
		$coordinator->bootApp($app);

		$eventLogger->start("bootstrap:load_app:$app:info", "Load info.xml for $app and register any services defined in it");
		$info = $this->getAppInfo($app);

		if (!empty($info['activity'])) {
			$activityManager = Server::get(IActivityManager::class);
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
			$settingsManager = Server::get(ISettingsManager::class);
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
			if (!empty($info['settings']['admin-delegation'])) {
				foreach ($info['settings']['admin-delegation'] as $setting) {
					$settingsManager->registerSetting(ISettingsManager::SETTINGS_DELEGATION, $setting);
				}
			}
			if (!empty($info['settings']['admin-delegation-section'])) {
				foreach ($info['settings']['admin-delegation-section'] as $section) {
					$settingsManager->registerSection(ISettingsManager::SETTINGS_DELEGATION, $section);
				}
			}
		}

		if (!empty($info['collaboration']['plugins'])) {
			// deal with one or many plugin entries
			$plugins = isset($info['collaboration']['plugins']['plugin']['@value'])
				? [$info['collaboration']['plugins']['plugin']] : $info['collaboration']['plugins']['plugin'];
			$collaboratorSearch = null;
			$autoCompleteManager = null;
			foreach ($plugins as $plugin) {
				if ($plugin['@attributes']['type'] === 'collaborator-search') {
					$pluginInfo = [
						'shareType' => $plugin['@attributes']['share-type'],
						'class' => $plugin['@value'],
					];
					$collaboratorSearch ??= Server::get(ICollaboratorSearch::class);
					$collaboratorSearch->registerPlugin($pluginInfo);
				} elseif ($plugin['@attributes']['type'] === 'autocomplete-sort') {
					$autoCompleteManager ??= Server::get(IAutoCompleteManager::class);
					$autoCompleteManager->registerSorter($plugin['@value']);
				}
			}
		}
		$eventLogger->end("bootstrap:load_app:$app:info");

		$eventLogger->end("bootstrap:load_app:$app");
	}

	#[\Override]
	public function isAppLoaded(string $app): bool {
		return isset($this->loadedApps[$app]);
	}

	/**
	 * @throws \InvalidArgumentException if the application is not installed yet
	 */
	#[\Override]
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

		$this->configManager->migrateConfigLexiconKeys($appId);
	}

	#[\Override]
	public function hasProtectedAppType(array $types): bool {
		if (empty($types)) {
			return false;
		}

		$protectedTypes = array_intersect($this->protectedAppTypes, $types);
		return !empty($protectedTypes);
	}

	/**
	 * @param IGroup[]|string[] $groups
	 * @throws \InvalidArgumentException if app can't be enabled for groups
	 * @throws AppPathNotFoundException
	 */
	#[\Override]
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
			/** @var IGroup|string $group */
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

		$this->configManager->migrateConfigLexiconKeys($appId);
	}

	/**
	 * @throws \Exception if app can't be disabled
	 */
	#[\Override]
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
	 * @psalm-taint-specialize
	 */
	#[\Override]
	public function getAppPath(string $appId, bool $ignoreCache = false): string {
		$appId = $this->cleanAppId($appId);
		if ($appId === '') {
			throw new AppPathNotFoundException('App id is empty');
		} elseif ($appId === 'core') {
			return __DIR__ . '/../../../core';
		}

		if (($dir = $this->findAppInDirectories($appId, $ignoreCache)) !== false) {
			return $dir['path'] . '/' . $appId;
		}
		throw new AppPathNotFoundException('Could not find path for ' . $appId);
	}

	#[\Override]
	public function getAppWebPath(string $appId): string {
		if (($dir = $this->findAppInDirectories($appId)) !== false) {
			return \OC::$WEBROOT . $dir['url'] . '/' . $appId;
		}
		throw new AppPathNotFoundException('Could not find web path for ' . $appId);
	}

	/**
	 * Find the apps root for an app id.
	 *
	 * If multiple copies are found, the apps root the latest version is returned.
	 *
	 * @param bool $ignoreCache ignore cache and rebuild it
	 * @return false|array{path: string, url: string} the apps root shape
	 *
	 * @internal
	 *
	 * TODO: Make private when OC_App::findAppInDirectories() is dropped.
	 */
	public function findAppInDirectories(string $appId, bool $ignoreCache = false): array|false {
		$sanitizedAppId = $this->cleanAppId($appId);
		if ($sanitizedAppId !== $appId) {
			return false;
		}

		if (isset($this->appsDirCache[$appId]) && !$ignoreCache) {
			return $this->appsDirCache[$appId];
		}

		$possibleApps = [];
		foreach (\OC::$APPSROOTS as $dir) {
			if (file_exists($dir['path'] . '/' . $appId)) {
				$possibleApps[] = $dir;
			}
		}

		if (empty($possibleApps)) {
			return false;
		} elseif (count($possibleApps) === 1) {
			$dir = array_shift($possibleApps);
			$this->appsDirCache[$appId] = $dir;
			return $dir;
		} else {
			$versionToLoad = [];
			foreach ($possibleApps as $possibleApp) {
				$appData = $this->getAppInfoByPath($possibleApp['path'] . '/' . $appId . '/appinfo/info.xml');
				$version = $appData['version'] ?? '';
				if (empty($versionToLoad) || version_compare($version, $versionToLoad['version'], '>')) {
					$versionToLoad = [
						'dir' => $possibleApp,
						'version' => $version,
					];
				}
			}
			if (!isset($versionToLoad['dir'])) {
				return false;
			}
			$this->appsDirCache[$appId] = $versionToLoad['dir'];
			return $versionToLoad['dir'];
		}
	}

	#[\Override]
	public function clearAppsCache(): void {
		$this->appInfos = [];
	}

	/**
	 * Returns a list of apps that need an upgrade for a specific Nextcloud version.
	 *
	 * @param string $version The Nextcloud version to check compatibility against (e.g., '28.0.1')
	 * @return array[] A list of app info arrays for apps that require an upgrade
	 *
	 * @internal
	 */
	public function getAppsNeedingUpgrade(string $version): array {
		$appsToUpgrade = [];
		$apps = $this->getEnabledApps();
		foreach ($apps as $appId) {
			$appInfo = $this->getAppInfo($appId);
			$appDbVersion = $this->getAppConfig()->getValue($appId, 'installed_version');
			if ($appDbVersion
				&& isset($appInfo['version'])
				&& version_compare($appInfo['version'], $appDbVersion, '>')
				&& $this->isAppCompatible($version, $appInfo)
			) {
				$appsToUpgrade[] = $appInfo;
			}
		}

		return $appsToUpgrade;
	}

	#[\Override]
	public function getAppInfo(string $appId, bool $path = false, string|null $lang = null): array|null {
		if ($path) {
			throw new \InvalidArgumentException(
				'IAppManager::getAppInfo() no longer accepts paths. Use getAppInfoByPath() ' .
				'and validate the path before calling.'
			);
		}

		if ($lang === null && isset($this->appInfos[$appId])) {
			return $this->appInfos[$appId];
		}

		try {
			$appPath = $this->getAppPath($appId);
		} catch (AppPathNotFoundException) {
			return null;
		}

		$infoPath = $appPath . '/appinfo/info.xml';
		$appInfo = $this->getAppInfoByPath($infoPath, $lang);

		if ($lang === null) {
			$this->appInfos[$appId] = $appInfo;
		}

		return $appInfo;
	}

	#[\Override]
	public function getAppInfoByPath(string $path, string|null $lang = null): array|null {
		if (!str_ends_with($path, '/appinfo/info.xml')) {
			return null;
		}

		$parser = new InfoParser($this->memCacheFactory->createLocal('core.appinfo'));
		$appInfo = $parser->parse($path);

		if ($appInfo === null) {
			return null; // info file parsing error of some sort
		}

		$appInfo = $parser->applyL10N($appInfo, $lang);
		return $appInfo;
	}

	#[\Override]
	public function getAppVersion(string $appId, bool $useCache = true): string {
		if ($useCache && isset($this->appVersions[$appId])) {
			return $this->appVersions[$appId];
		}

		if ($appId === 'core') {
			return $this->appVersions[$appId] = $this->serverVersion->getVersionString();
		}

		$appInfo = $this->getAppInfo($appId);
		return $this->appVersions[$appId] = $appInfo['version'] ?? '0';
	}

	#[\Override]
	public function getAppInstalledVersions(bool $onlyEnabled = false): array {
		return $this->getAppConfig()->getAppInstalledVersions($onlyEnabled);
	}

	/**
	 * Returns a list of enabled apps incompatible with the given Nextcloud version.
	 *
	 * @param string $version The Nextcloud version to check compatibility against (e.g., '28.0.1')
	 * @return array[] A list of app info arrays for apps that are incompatible
	 *
	 * @internal
	 */
	public function getIncompatibleApps(string $version): array {
		$enabledAppIds = $this->getEnabledApps();
		$incompatibleApps = [];

		foreach ($enabledAppIds as $appId) {
			$appInfo = $this->getAppInfo($appId);

			if ($appInfo === null) {
				// assume incompatible if unable to load app info
				// FIXME: This seems fragile; consider throwing instead?
				$incompatibleApps[] = [
					'id' => $appId,
					'name' => $appId,
				];
			} elseif (!$this->isAppCompatible($version, $appInfo)) {
				$incompatibleApps[] = $appInfo;
			}
		}

		return $incompatibleApps;
	}

	/**
	 * @throws \Exception if shipped apps inventory file cannot be loaded.
	 */
	#[\Override]
	public function isShipped(string $appId): bool {
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
	 * @throws \Exception if shipped apps inventory file cannot be loaded.
	 */
	private function loadShippedJson(): void {
		if ($this->shippedApps === null) {
			$filePath = \OC::$SERVERROOT . '/core/shipped.json';

			if (!file_exists($filePath)) {
				throw new \Exception("File not found: $filePath");
			}

			$data = json_decode(file_get_contents($filePath), true);

			$this->shippedApps = $data['shippedApps'];
			$this->alwaysEnabled = $data['alwaysEnabled'];
			$this->defaultEnabled = $data['defaultEnabled'];
		}
	}

	#[\Override]
	public function getAlwaysEnabledApps(): array {
		$this->loadShippedJson();
		return $this->alwaysEnabled;
	}

	#[\Override]
	public function isDefaultEnabled(string $appId): bool {
		return (in_array($appId, $this->getDefaultEnabledApps()));
	}

	#[\Override]
	public function getDefaultEnabledApps(): array {
		$this->loadShippedJson();
		return $this->defaultEnabled;
	}

	#[\Override]
	public function getDefaultAppForUser(?IUser $user = null, bool $withFallbacks = true): string {
		$navigationManager = $this->getNavigationManager();

		$entryId = $navigationManager->getDefaultEntryIdForUser($user, $withFallbacks);
		$entry = $navigationManager->get($entryId);

		return (string)$entry['app'];
	}

	#[\Override]
	public function getDefaultApps(): array {
		$navigationManager = $this->getNavigationManager();

		$entryIds = $navigationManager->getDefaultEntryIds();

		$apps = array_map(
			fn(string $entryId) => (string)($navigationManager->get($entryId)['app']),
			$entryIds
		);

		return array_values(array_unique(array_filter($apps)));
	}

	#[\Override]
	public function setDefaultApps(array $defaultApps): void {
		$navigationManager = $this->getNavigationManager();
		
		$entries = $navigationManager->getAll(); // technically this gets only 'link' entries not 'all'

		// Create a lookup map: ['appName' => 'entryId']
		// TODO: switch to array_column(); only concern is in theory I think we permit all numeric app ids/names though rare
		$appToEntryMap = [];
		foreach ($entries as $entry) {
			$appName = (string)($entry['app']);
			$appToEntryMap[$appName] = (string)($entry['id']);
		}

		// Map the requested app names to their corresponding entry IDs
		$entryIds = [];
		foreach ($defaultApps as $appName) {
			if (isset($appToEntryMap[$appName])) {
				$entryids[] = $appToEntryMap[$appName];
			}
		}

		$navigationManager->setDefaultEntryIds($entryIds);
	}

	#[\Override]
	public function isBackendRequired(string $backend): bool {
		foreach ($this->appInfos as $appInfo) {
			if (
				isset($appInfo['dependencies']['backend'])
				&& is_array($appInfo['dependencies']['backend'])
				&& in_array($backend, $appInfo['dependencies']['backend'], true)
			) {
				return true;
			}
		}

		return false;
	}

	/**
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
	#[\Override]
	public function cleanAppId(string $app): string {
		/* Only lowercase alphanumeric is allowed */
		$cleanAppId = preg_replace('/(^[0-9_-]+|[^a-z0-9_-]+|[_-]+$)/', '', $app, -1, $count);
		if ($count > 0) {
			$this->logger->debug('Only lowercase alphanumeric characters are allowed in appIds; check paths of installed app [' . $count . ' characters replaced]', [
				'app' => $cleanAppId, // safer to log $cleanAppId even if it makes more challenging to troubleshooting (part of why character count is at least logged)
			]);
		}

		return $cleanAppId;
	}

	/**
	 * Store the app's types in config and, if a protected app, ensure it always has a valid enabled state. 
	 *
	 * Protected apps are not permitted to have group restrictions, so any non-yes/non-no enabled state is
	 * normalized to 'yes'.
	 *
	 * @param string $app The app ID.
	 * @param array{types?: string[]} $appData App metadata containing the types list.
	 *
	 * @internal
	 */
	public function setAppTypes(string $app, array $appData): void {
		$types = $appData['types'] ?? [];
		$this->config->setAppValue($app, 'types', implode(',', $types));

		if ($this->hasProtectedAppType($types)) {
			$enabled = $this->config->getAppValue($app, 'enabled', 'yes');
			// If enabled is a group list (not 'yes' or 'no'), force it to 'yes'
			if ($enabled !== 'yes' && $enabled !== 'no') {
				$this->config->setAppValue($app, 'enabled', 'yes');
			}
		}
	}

	#[\Override]
	public function upgradeApp(string $appId): bool {
		// for apps distributed with core, we refresh app path in case the downloaded version
		// have been installed in custom apps and not in the default path
		$appPath = $this->getAppPath($appId, true);

		$this->clearAppsCache();
		$l = \OC::$server->getL10N('core');
		$appData = $this->getAppInfo($appId, false, $l->getLanguageCode());
		if ($appData === null) {
			throw new AppPathNotFoundException('Could not find ' . $appId);
		}

		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		$ignoreMax = in_array($appId, $ignoreMaxApps, true);
		\OC_App::checkAppDependencies(
			$this->config,
			$l,
			$appData,
			$ignoreMax
		);

		\OC_App::registerAutoloading($appId, $appPath, true);
		\OC_App::executeRepairSteps($appId, $appData['repair-steps']['pre-migration']);

		$ms = new MigrationService($appId, Server::get(\OC\DB\Connection::class));
		$ms->migrate();

		\OC_App::executeRepairSteps($appId, $appData['repair-steps']['post-migration']);
		$queue = Server::get(IJobList::class);
		foreach ($appData['repair-steps']['live-migration'] as $step) {
			$queue->add(BackgroundRepair::class, [
				'app' => $appId,
				'step' => $step]);
		}

		// update appversion in app manager
		$this->clearAppsCache();
		$this->getAppVersion($appId, false);

		// Setup background jobs
		foreach ($appData['background-jobs'] as $job) {
			$queue->add($job);
		}

		//set remote/public handlers
		foreach ($appData['remote'] as $name => $path) {
			$this->config->setAppValue('core', 'remote_' . $name, $appId . '/' . $path);
		}
		foreach ($appData['public'] as $name => $path) {
			$this->config->setAppValue('core', 'public_' . $name, $appId . '/' . $path);
		}

		$this->setAppTypes($appId, $appData);

		$version = $this->getAppVersion($appId);
		$this->config->setAppValue($appId, 'installed_version', $version);

		// migrate eventual new config keys in the process
		/** @psalm-suppress InternalMethod */
		$this->configManager->migrateConfigLexiconKeys($appId);
		$this->configManager->updateLexiconEntries($appId);

		$this->dispatcher->dispatchTyped(new AppUpdateEvent($appId));
		$this->dispatcher->dispatch(ManagerEvent::EVENT_APP_UPDATE, new ManagerEvent(
			ManagerEvent::EVENT_APP_UPDATE, $appId
		));

		return true;
	}

	#[\Override]
	public function isUpgradeRequired(string $appId): bool {
		$versions = $this->getAppInstalledVersions();
		$currentVersion = $this->getAppVersion($appId);
		if ($currentVersion && isset($versions[$appId])) {
			$installedVersion = $versions[$appId];
			if (!version_compare($currentVersion, $installedVersion, '=')) {
				$this->logger->info('{appId} needs and upgrade from {from} to {to}',
					[
						'appId' => $appId,
						'from' => $installedVersion,
						'to' => $currentVersion,
					]
				);
				return true;
			}
		}
		return false;
	}

	#[\Override]
	public function isAppCompatible(string $serverVersion, array $appInfo, bool $ignoreMax = false): bool {
		return count($this->dependencyAnalyzer->analyzeServerVersion($serverVersion, $appInfo, $ignoreMax)) === 0;
	}
}
