<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Schaefer "christophł@wolkesicher.de"
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Daniel Rudolf <github.com@daniel-rudolf.de>
 * @author Greta Doci <gretadoci@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobia De Koninck <tobia@ledfan.be>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\App;

use InvalidArgumentException;
use OC\AppConfig;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\ServerNotAvailableException;
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
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
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

	private IUserSession $userSession;
	private IConfig $config;
	private AppConfig $appConfig;
	private IGroupManager $groupManager;
	private ICacheFactory $memCacheFactory;
	private IEventDispatcher $dispatcher;
	private LoggerInterface $logger;

	/** @var string[] $appId => $enabled */
	private array $installedAppsCache = [];

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

	public function __construct(IUserSession $userSession,
		IConfig $config,
		AppConfig $appConfig,
		IGroupManager $groupManager,
		ICacheFactory $memCacheFactory,
		IEventDispatcher $dispatcher,
		LoggerInterface $logger) {
		$this->userSession = $userSession;
		$this->config = $config;
		$this->appConfig = $appConfig;
		$this->groupManager = $groupManager;
		$this->memCacheFactory = $memCacheFactory;
		$this->dispatcher = $dispatcher;
		$this->logger = $logger;
	}

	/**
	 * @return string[] $appId => $enabled
	 */
	private function getInstalledAppsValues(): array {
		if (!$this->installedAppsCache) {
			$values = $this->appConfig->getValues(false, 'enabled');

			$alwaysEnabledApps = $this->getAlwaysEnabledApps();
			foreach ($alwaysEnabledApps as $appId) {
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
	 * @param IGroup $group
	 * @return array
	 */
	public function getEnabledAppsForGroup(IGroup $group): array {
		$apps = $this->getInstalledAppsValues();
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

		// prevent app.php from printing output
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
			$this->appTypes = $this->appConfig->getValues(false, 'types') ?: [];
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

	/**
	 * @param string $appId
	 * @return array
	 */
	public function getAppRestriction(string $appId): array {
		$values = $this->getInstalledAppsValues();

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
		$installedApps = $this->getInstalledAppsValues();
		if (isset($installedApps[$appId])) {
			return $this->checkAppForUser($installedApps[$appId], $user);
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
				$this->logger->warning('AppManger::checkAppForUser - can\'t decode group IDs: ' . print_r($enabled, true) . ' - json error code: ' . $jsonError);
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
				$this->logger->warning('AppManger::checkAppForUser - can\'t decode group IDs: ' . print_r($enabled, true) . ' - json error code: ' . $jsonError);
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
	 * @param IGroup[]|String[] $groups
	 * @return bool
	 */
	public function isInstalled($appId) {
		$installedApps = $this->getInstalledAppsValues();
		return isset($installedApps[$appId]);
	}

	public function ignoreNextcloudRequirementForApp(string $appId): void {
		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		if (!in_array($appId, $ignoreMaxApps, true)) {
			$ignoreMaxApps[] = $appId;
			$this->config->setSystemValue('app_install_overwrite', $ignoreMaxApps);
		}
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
		$eventLogger = \OC::$server->get(\OCP\Diagnostics\IEventLogger::class);
		$eventLogger->start("bootstrap:load_app:$app", "Load $app");

		// in case someone calls loadApp() directly
		\OC_App::registerAutoloading($app, $appPath);

		/** @var Coordinator $coordinator */
		$coordinator = \OC::$server->get(Coordinator::class);
		$isBootable = $coordinator->isBootable($app);

		$hasAppPhpFile = is_file($appPath . '/appinfo/app.php');

		$eventLogger = \OC::$server->get(IEventLogger::class);
		$eventLogger->start('bootstrap:load_app_' . $app, 'Load app: ' . $app);
		if ($isBootable && $hasAppPhpFile) {
			$this->logger->error('/appinfo/app.php is not loaded when \OCP\AppFramework\Bootstrap\IBootstrap on the application class is used. Migrate everything from app.php to the Application class.', [
				'app' => $app,
			]);
		} elseif ($hasAppPhpFile) {
			$eventLogger->start("bootstrap:load_app:$app:app.php", "Load legacy app.php app $app");
			$this->logger->debug('/appinfo/app.php is deprecated, use \OCP\AppFramework\Bootstrap\IBootstrap on the application class instead.', [
				'app' => $app,
			]);
			try {
				self::requireAppFile($appPath);
			} catch (\Throwable $ex) {
				if ($ex instanceof ServerNotAvailableException) {
					throw $ex;
				}
				if (!$this->isShipped($app) && !$this->isType($app, ['authentication'])) {
					$this->logger->error("App $app threw an error during app.php load and will be disabled: " . $ex->getMessage(), [
						'exception' => $ex,
					]);

					// Only disable apps which are not shipped and that are not authentication apps
					$this->disableApp($app, true);
				} else {
					$this->logger->error("App $app threw an error during app.php load: " . $ex->getMessage(), [
						'exception' => $ex,
					]);
				}
			}
			$eventLogger->end("bootstrap:load_app:$app:app.php");
		}

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
	 * Load app.php from the given app
	 *
	 * @param string $app app name
	 * @throws \Error
	 */
	private static function requireAppFile(string $app): void {
		// encapsulated here to avoid variable scope conflicts
		require_once $app . '/appinfo/app.php';
	}

	/**
	 * Enable an app for every user
	 *
	 * @param string $appId
	 * @param bool $forceEnable
	 * @throws AppPathNotFoundException
	 */
	public function enableApp(string $appId, bool $forceEnable = false): void {
		// Check if app exists
		$this->getAppPath($appId);

		if ($forceEnable) {
			$this->ignoreNextcloudRequirementForApp($appId);
		}

		$this->installedAppsCache[$appId] = 'yes';
		$this->appConfig->setValue($appId, 'enabled', 'yes');
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

		if ($forceEnable) {
			$this->ignoreNextcloudRequirementForApp($appId);
		}

		/** @var string[] $groupIds */
		$groupIds = array_map(function ($group) {
			/** @var IGroup $group */
			return ($group instanceof IGroup)
				? $group->getGID()
				: $group;
		}, $groups);

		$this->installedAppsCache[$appId] = json_encode($groupIds);
		$this->appConfig->setValue($appId, 'enabled', json_encode($groupIds));
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
	public function disableApp($appId, $automaticDisabled = false) {
		if ($this->isAlwaysEnabled($appId)) {
			throw new \Exception("$appId can't be disabled.");
		}

		if ($automaticDisabled) {
			$previousSetting = $this->appConfig->getValue($appId, 'enabled', 'yes');
			if ($previousSetting !== 'yes' && $previousSetting !== 'no') {
				$previousSetting = json_decode($previousSetting, true);
			}
			$this->autoDisabledApps[$appId] = $previousSetting;
		}

		unset($this->installedAppsCache[$appId]);
		$this->appConfig->setValue($appId, 'enabled', 'no');

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
	 * @param string $appId
	 * @return string
	 * @throws AppPathNotFoundException if app folder can't be found
	 */
	public function getAppPath($appId) {
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
	public function clearAppsCache() {
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
		$apps = $this->getInstalledApps();
		foreach ($apps as $appId) {
			$appInfo = $this->getAppInfo($appId);
			$appDbVersion = $this->appConfig->getValue($appId, 'installed_version');
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
			$file = $appId;
		} else {
			if ($lang === null && isset($this->appInfos[$appId])) {
				return $this->appInfos[$appId];
			}
			try {
				$appPath = $this->getAppPath($appId);
			} catch (AppPathNotFoundException $e) {
				return null;
			}
			$file = $appPath . '/appinfo/info.xml';
		}

		$parser = new InfoParser($this->memCacheFactory->createLocal('core.appinfo'));
		$data = $parser->parse($file);

		if (is_array($data)) {
			$data = \OC_App::parseAppInfo($data, $lang);
		}

		if ($lang === null) {
			$this->appInfos[$appId] = $data;
		}

		return $data;
	}

	public function getAppVersion(string $appId, bool $useCache = true): string {
		if (!$useCache || !isset($this->appVersions[$appId])) {
			$appInfo = $this->getAppInfo($appId);
			$this->appVersions[$appId] = ($appInfo !== null && isset($appInfo['version'])) ? $appInfo['version'] : '0';
		}
		return $this->appVersions[$appId];
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
		$apps = $this->getInstalledApps();
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

	public function getDefaultAppForUser(?IUser $user = null, bool $withFallbacks = true): string {
		// Set fallback to always-enabled files app
		$appId = $withFallbacks ? 'files' : '';
		$defaultApps = explode(',', $this->config->getSystemValueString('defaultapp', ''));
		$defaultApps = array_filter($defaultApps);

		$user ??= $this->userSession->getUser();

		if ($user !== null) {
			$userDefaultApps = explode(',', $this->config->getUserValue($user->getUID(), 'core', 'defaultapp'));
			$defaultApps = array_filter(array_merge($userDefaultApps, $defaultApps));
			if (empty($defaultApps) && $withFallbacks) {
				/* Fallback on user defined apporder */
				$customOrders = json_decode($this->config->getUserValue($user->getUID(), 'core', 'apporder', '[]'), true, flags:JSON_THROW_ON_ERROR);
				if (!empty($customOrders)) {
					// filter only entries with app key (when added using closures or NavigationManager::add the app is not guranteed to be set)
					$customOrders = array_filter($customOrders, fn ($entry) => isset($entry['app']));
					// sort apps by order
					usort($customOrders, fn ($a, $b) => $a['order'] - $b['order']);
					// set default apps to sorted apps
					$defaultApps = array_map(fn ($entry) => $entry['app'], $customOrders);
				}
			}
		}

		if (empty($defaultApps) && $withFallbacks) {
			$defaultApps = ['dashboard','files'];
		}

		// Find the first app that is enabled for the current user
		foreach ($defaultApps as $defaultApp) {
			$defaultApp = \OC_App::cleanAppId(strip_tags($defaultApp));
			if ($this->isEnabledForUser($defaultApp, $user)) {
				$appId = $defaultApp;
				break;
			}
		}

		return $appId;
	}

	public function getDefaultApps(): array {
		return explode(',', $this->config->getSystemValueString('defaultapp', 'dashboard,files'));
	}

	public function setDefaultApps(array $defaultApps): void {
		foreach ($defaultApps as $app) {
			if (!$this->isInstalled($app)) {
				$this->logger->debug('Can not set not installed app as default app', ['missing_app' => $app]);
				throw new InvalidArgumentException('App is not installed');
			}
		}

		$this->config->setSystemValue('defaultapp', join(',', $defaultApps));
	}
}
