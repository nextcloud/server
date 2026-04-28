<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Appstore\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\App\AppStore\Version\VersionParser;
use OC\App\DependencyAnalyzer;
use OC\Installer;
use OCA\Appstore\AppInfo\Application;
use OCP\App\AppPathNotFoundException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
class ApiController extends OCSController {

	/** @var array */
	private $allApps = [];

	public function __construct(
		IRequest $request,
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly AppManager $appManager,
		private readonly DependencyAnalyzer $dependencyAnalyzer,
		private readonly CategoryFetcher $categoryFetcher,
		private readonly AppFetcher $appFetcher,
		private readonly IFactory $l10nFactory,
		private readonly BundleFetcher $bundleFetcher,
		private readonly Installer $installer,
		private readonly IRegistry $subscriptionRegistry,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Get all available categories
	 *
	 * @return DataResponse<Http::STATUS_OK, list<array{id: string, displayName: string}>, array{}>
	 *
	 * 200: The categories were found successfully
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/apps/categories')]
	public function listCategories(): DataResponse {
		$currentLanguage = substr($this->l10nFactory->findLanguage(), 0, 2);

		$categories = $this->categoryFetcher->get();
		$categories = array_map(fn (array $category): array => [
			'id' => $category['id'],
			'displayName' => $category['translations'][$currentLanguage]['name'] ?? $category['translations']['en']['name'],
		], $categories);

		return new DataResponse($categories);
	}

	/**
	 * Get all available apps
	 *
	 * @return DataResponse<Http::STATUS_OK, list<array{id: string, name: string, groups: list<string>, internal: bool, isCompatible: bool, missingDependencies?: list<string>, missingMaxNextcloudVersion: bool, missingMinNextcloudVersion: bool, ...<array-key, mixed>}>, array{}>
	 *
	 * 200: The apps were found successfully
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/apps')]
	public function listApps(): DataResponse {
		$apps = $this->getAllApps();

		/** @var array<string>|mixed $ignoreMaxApps */
		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		if (!is_array($ignoreMaxApps)) {
			$this->logger->warning('The value given for app_install_overwrite is not an array. Ignoring...');
			$ignoreMaxApps = [];
		}

		// Extend existing app details
		$apps = array_map(function (array $appData) use ($ignoreMaxApps): array {
			if (isset($appData['appstoreData'])) {
				$appstoreData = $appData['appstoreData'];
				$appData['screenshot'] = $this->createProxyPreviewUrl($appstoreData['screenshots'][0]['url'] ?? '');
				$appData['category'] = $appstoreData['categories'];
				$appData['releases'] = $appstoreData['releases'];
			}

			$newVersion = $this->installer->isUpdateAvailable($appData['id']);
			if ($newVersion !== false) {
				$appData['update'] = $newVersion;
			}

			// fix groups to be an array
			$groups = [];
			if (is_string($appData['groups'])) {
				/** @var list<string>|string $groups */
				$groups = json_decode($appData['groups']);
				// ensure 'groups' is an array
				if (!is_array($groups)) {
					$groups = [$groups];
				}
			}

			$appData['groups'] = $groups;
			$appData['canUninstall'] = !$appData['active'] && $appData['removable'];

			// analyze dependencies
			$ignoreMax = in_array($appData['id'], $ignoreMaxApps);
			$missing = $this->dependencyAnalyzer->analyze($appData, $ignoreMax);
			$appData['canInstall'] = empty($missing);
			$appData['missingDependencies'] = $missing;

			$appData['missingMinNextcloudVersion'] = !isset($appData['dependencies']['nextcloud']['@attributes']['min-version']);
			$appData['missingMaxNextcloudVersion'] = !isset($appData['dependencies']['nextcloud']['@attributes']['max-version']);
			$appData['isCompatible'] = $this->dependencyAnalyzer->isMarkedCompatible($appData);

			return $appData;
		}, $apps);

		/**
		 * @var list<array{id: string, name: string, groups: list<string>, internal: bool, isCompatible: bool, missingDependencies?: list<string>, missingMaxNextcloudVersion: bool, missingMinNextcloudVersion: bool, ...<array-key, mixed>}> $apps
		 */
		usort($apps, $this->sortApps(...));
		return new DataResponse($apps);
	}

	/**
	 * Enable one apps
	 *
	 * App will be enabled for specific groups only if $groups is defined
	 *
	 * @param string $appId - The app to enable
	 * @param list<string> $groups - The groups to enable the app for
	 * @param bool $force - Whether to force enable the app even if Nextcloud version requirements are not met
	 *
	 * @return DataResponse<Http::STATUS_OK, array{update_required: bool}, array{}>
	 * @throws OCSException - if the app could not be enabled
	 *
	 * 200: App successfully enabled
	 */
	#[PasswordConfirmationRequired(strict: true)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/apps/enable')]
	public function enableApp(string $appId, array $groups = [], bool $force = false): DataResponse {
		try {
			$appId = $this->appManager->cleanAppId($appId);
			if ($force) {
				$this->appManager->overwriteNextcloudRequirement($appId);
			}

			// Check if app is already downloaded
			if (!$this->installer->isDownloaded($appId)) {
				$this->installer->downloadApp($appId);
			}

			$this->installer->installApp($appId);

			if ($groups !== []) {
				$this->appManager->enableAppForGroups($appId, $this->getGroupList($groups));
			} else {
				$this->appManager->enableApp($appId);
			}

			$updateRequired = $this->appManager->isUpgradeRequired($appId);
			return new DataResponse(['update_required' => $updateRequired]);
		} catch (\Throwable $throwable) {
			$this->logger->error('could not enable app', ['exception' => $throwable]);
			throw new OCSException('could not enable app', Http::STATUS_INTERNAL_SERVER_ERROR, $throwable);
		}
	}

	/**
	 * Disable an app
	 *
	 * @param string $appId - The app to disable
	 *
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 * @throws OCSException - if the app could not be disabled
	 *
	 * 200: App successfully disabled
	 */
	#[PasswordConfirmationRequired(strict: false)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/apps/disable')]
	public function disableApp(string $appId): DataResponse {
		try {
			$appId = $this->appManager->cleanAppId($appId);
			$this->appManager->disableApp($appId);
			return new DataResponse([]);
		} catch (\Exception $exception) {
			$this->logger->error('could not disable app', ['exception' => $exception]);
			throw new OCSException('could not disable app', Http::STATUS_INTERNAL_SERVER_ERROR, $exception);
		}
	}

	/**
	 * Uninstall an app.
	 * This will disable the app - if needed - and then remove the app from the system
	 *
	 * @param string $appId - The app to uninstall
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 * @throws OCSException - if the app could not be uninstalled
	 *
	 * 200: App successfully uninstalled
	 */
	#[PasswordConfirmationRequired(strict: true)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/apps/uninstall')]
	public function uninstallApp(string $appId): DataResponse {
		$appId = $this->appManager->cleanAppId($appId);
		$result = $this->installer->removeApp($appId);
		if ($result !== false) {
			// If this app was force enabled, remove the force-enabled-state
			$this->appManager->removeOverwriteNextcloudRequirement($appId);
			$this->appManager->clearAppsCache();
			return new DataResponse([]);
		}

		throw new OCSException('could not remove app', Http::STATUS_INTERNAL_SERVER_ERROR);
	}

	/**
	 * Update an app
	 *
	 * @param string $appId - The app to update
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 * @throws OCSException - if the app could not be updated
	 *
	 * 200: App successfully updated
	 */
	#[PasswordConfirmationRequired(strict: true)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/apps/update')]
	public function updateApp(string $appId): DataResponse {
		$appId = $this->appManager->cleanAppId($appId);

		$this->config->setSystemValue('maintenance', true);
		try {
			$result = $this->installer->updateAppstoreApp($appId);
			$this->config->setSystemValue('maintenance', false);
			if ($result === false) {
				throw new \Exception('Update failed');
			}
		} catch (\Exception $exception) {
			$this->config->setSystemValue('maintenance', false);
			throw new OCSException('could not update app', Http::STATUS_INTERNAL_SERVER_ERROR, $exception);
		}

		return new DataResponse([]);
	}

	/**
	 * Enable all apps of a bundle
	 *
	 * @param string $bundleId - The bundle to enable
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 * @throws OCSException - if the bundle, or one app within, could not be enabled
	 *
	 * 200: Bundle successfully enabled
	 */
	#[PasswordConfirmationRequired(strict: true)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/bundles/enable')]
	public function enableBundle(string $bundleId): DataResponse {
		try {
			$bundle = $this->bundleFetcher->getBundleByIdentifier($bundleId);
			$this->config->setSystemValue('maintenance', true);
			$this->installer->installAppBundle($bundle);
		} catch (\BadMethodCallException $e) {
			throw new OCSNotFoundException('Bundle not found', $e);
		} catch (\Exception $exception) {
			$this->logger->error('could not enable bundle', ['bundleId' => $bundleId, 'exception' => $exception]);
			throw new OCSException('could not enable bundle', Http::STATUS_INTERNAL_SERVER_ERROR, $exception);
		} finally {
			$this->config->setSystemValue('maintenance', false);
		}

		return new DataResponse([]);
	}

	/**
	 * Convert URL to proxied URL so CSP is no problem
	 */
	private function createProxyPreviewUrl(string $url): string {
		if ($url === '') {
			return '';
		}

		return 'https://usercontent.apps.nextcloud.com/' . base64_encode($url);
	}

	private function fetchApps(): void {
		$appClass = new \OC_App();
		$apps = $appClass->listAllApps();
		foreach ($apps as $app) {
			$app['installed'] = true;

			if (isset($app['screenshot'][0])) {
				$appScreenshot = $app['screenshot'][0] ?? null;
				if (is_array($appScreenshot)) {
					// Screenshot with thumbnail
					$appScreenshot = $appScreenshot['@value'];
				}

				$app['screenshot'] = $this->createProxyPreviewUrl($appScreenshot);
			}

			$this->allApps[$app['id']] = $app;
		}

		$apps = $this->getAppsForCategory('');
		$supportedApps = $this->subscriptionRegistry->delegateGetSupportedApps();
		foreach ($apps as $app) {
			$app['appstore'] = true;
			if (!array_key_exists($app['id'], $this->allApps)) {
				$this->allApps[$app['id']] = $app;
			} else {
				$this->allApps[$app['id']] = array_merge($app, $this->allApps[$app['id']]);
			}

			if (in_array($app['id'], $supportedApps)) {
				$this->allApps[$app['id']]['level'] = \OC_App::supportedApp;
			}
		}

		// add bundle information
		$bundles = $this->bundleFetcher->getBundles();
		foreach ($bundles as $bundle) {
			foreach ($bundle->getAppIdentifiers() as $identifier) {
				foreach ($this->allApps as &$app) {
					if ($app['id'] === $identifier) {
						$app['bundleIds'][] = $bundle->getIdentifier();
						continue;
					}
				}
			}
		}
	}

	private function getAllApps() {
		if (empty($this->allApps)) {
			$this->fetchApps();
		}

		return $this->allApps;
	}

	/**
	 * Get all apps for a category from the app store
	 *
	 * @throws \Exception
	 */
	private function getAppsForCategory(string $requestedCategory = ''): array {
		$versionParser = new VersionParser();
		$formattedApps = [];
		$apps = $this->appFetcher->get();
		foreach ($apps as $app) {
			// Skip all apps not in the requested category
			if ($requestedCategory !== '') {
				$isInCategory = false;
				foreach ($app['categories'] as $category) {
					if ($category === $requestedCategory) {
						$isInCategory = true;
					}
				}

				if (!$isInCategory) {
					continue;
				}
			}

			if (!isset($app['releases'][0]['rawPlatformVersionSpec'])) {
				continue;
			}

			$nextcloudVersion = $versionParser->getVersion($app['releases'][0]['rawPlatformVersionSpec']);
			$nextcloudVersionDependencies = [];
			if ($nextcloudVersion->getMinimumVersion() !== '') {
				$nextcloudVersionDependencies['nextcloud']['@attributes']['min-version'] = $nextcloudVersion->getMinimumVersion();
			}

			if ($nextcloudVersion->getMaximumVersion() !== '') {
				$nextcloudVersionDependencies['nextcloud']['@attributes']['max-version'] = $nextcloudVersion->getMaximumVersion();
			}

			$phpVersion = $versionParser->getVersion($app['releases'][0]['rawPhpVersionSpec']);

			try {
				$this->appManager->getAppPath($app['id']);
				$existsLocally = true;
			} catch (AppPathNotFoundException) {
				$existsLocally = false;
			}

			$phpDependencies = [];
			if ($phpVersion->getMinimumVersion() !== '') {
				$phpDependencies['php']['@attributes']['min-version'] = $phpVersion->getMinimumVersion();
			}

			if ($phpVersion->getMaximumVersion() !== '') {
				$phpDependencies['php']['@attributes']['max-version'] = $phpVersion->getMaximumVersion();
			}

			if (isset($app['releases'][0]['minIntSize'])) {
				$phpDependencies['php']['@attributes']['min-int-size'] = $app['releases'][0]['minIntSize'];
			}

			$authors = '';
			foreach ($app['authors'] as $key => $author) {
				$authors .= $author['name'];
				if ($key !== count($app['authors']) - 1) {
					$authors .= ', ';
				}
			}

			$currentLanguage = substr($this->l10nFactory->findLanguage(), 0, 2);
			$enabledValue = $this->appConfig->getValueString($app['id'], 'enabled', 'no');
			$groups = null;
			if ($enabledValue !== 'no' && $enabledValue !== 'yes') {
				$groups = $enabledValue;
			}

			$currentVersion = '';
			if ($this->appManager->isEnabledForAnyone($app['id'])) {
				$currentVersion = $this->appManager->getAppVersion($app['id']);
			} else {
				$currentVersion = $app['releases'][0]['version'];
			}

			$formattedApps[] = [
				'id' => $app['id'],
				'app_api' => false,
				'name' => $app['translations'][$currentLanguage]['name'] ?? $app['translations']['en']['name'],
				'description' => $app['translations'][$currentLanguage]['description'] ?? $app['translations']['en']['description'],
				'summary' => $app['translations'][$currentLanguage]['summary'] ?? $app['translations']['en']['summary'],
				'license' => $app['releases'][0]['licenses'],
				'author' => $authors,
				'shipped' => $this->appManager->isShipped($app['id']),
				'version' => $currentVersion,
				'types' => [],
				'documentation' => [
					'admin' => $app['adminDocs'],
					'user' => $app['userDocs'],
					'developer' => $app['developerDocs']
				],
				'website' => $app['website'],
				'bugs' => $app['issueTracker'],
				'dependencies' => array_merge(
					$nextcloudVersionDependencies,
					$phpDependencies
				),
				'level' => ($app['isFeatured'] === true) ? 200 : 100,
				'missingMaxNextcloudVersion' => false,
				'missingMinNextcloudVersion' => false,
				'canInstall' => true,
				'screenshot' => isset($app['screenshots'][0]['url']) ? 'https://usercontent.apps.nextcloud.com/' . base64_encode($app['screenshots'][0]['url']) : '',
				'score' => $app['ratingOverall'],
				'ratingNumOverall' => $app['ratingNumOverall'],
				'ratingNumThresholdReached' => $app['ratingNumOverall'] > 5,
				'removable' => $existsLocally,
				'active' => $this->appManager->isEnabledForUser($app['id']),
				'needsDownload' => !$existsLocally,
				'groups' => $groups,
				'fromAppStore' => true,
				'appstoreData' => $app,
			];
		}

		return $formattedApps;
	}

	/**
	 * @param string[] $groups - The group ids to fetch
	 * @return list<IGroup> - The list of groups matching the given group ids
	 */
	private function getGroupList(array $groups): array {
		$groupManager = Server::get(IGroupManager::class);
		$groupsList = [];
		foreach ($groups as $group) {
			$groupItem = $groupManager->get($group);
			if ($groupItem instanceof IGroup) {
				$groupsList[] = $groupItem;
			}
		}

		return $groupsList;
	}

	/**
	 * @param array{name: string, ...} $a
	 * @param array{name: string, ...} $b
	 */
	private function sortApps(array $a, array $b): int {
		return $a['name'] <=> $b['name'];
	}
}
