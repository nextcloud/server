<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Fetcher\AppDiscoverFetcher;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\App\AppStore\Version\VersionParser;
use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OC\Installer;
use OCP\App\AppPathNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AppSettingsController extends Controller {

	/** @var array */
	private $allApps = [];

	private IAppData $appData;

	public function __construct(
		string $appName,
		IRequest $request,
		IAppDataFactory $appDataFactory,
		private IL10N $l10n,
		private IConfig $config,
		private INavigationManager $navigationManager,
		private AppManager $appManager,
		private CategoryFetcher $categoryFetcher,
		private AppFetcher $appFetcher,
		private IFactory $l10nFactory,
		private BundleFetcher $bundleFetcher,
		private Installer $installer,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
		private IInitialState $initialState,
		private AppDiscoverFetcher $discoverFetcher,
		private IClientService $clientService,
	) {
		parent::__construct($appName, $request);
		$this->appData = $appDataFactory->get('appstore');
	}

	/**
	 * @psalm-suppress UndefinedClass AppAPI is shipped since 30.0.1
	 *
	 * @return TemplateResponse
	 */
	#[NoCSRFRequired]
	public function viewApps(): TemplateResponse {
		$this->navigationManager->setActiveEntry('core_apps');

		$this->initialState->provideInitialState('appstoreEnabled', $this->config->getSystemValueBool('appstoreenabled', true));
		$this->initialState->provideInitialState('appstoreBundles', $this->getBundles());
		$this->initialState->provideInitialState('appstoreDeveloperDocs', $this->urlGenerator->linkToDocs('developer-manual'));
		$this->initialState->provideInitialState('appstoreUpdateCount', count($this->getAppsWithUpdates()));

		if ($this->appManager->isInstalled('app_api')) {
			try {
				Server::get(\OCA\AppAPI\Service\ExAppsPageService::class)->provideAppApiState($this->initialState);
			} catch (\Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface $e) {
			}
		}

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://usercontent.apps.nextcloud.com');

		$templateResponse = new TemplateResponse('settings', 'settings/empty', ['pageTitle' => $this->l10n->t('Settings')]);
		$templateResponse->setContentSecurityPolicy($policy);

		Util::addStyle('settings', 'settings');
		Util::addScript('settings', 'vue-settings-apps-users-management');

		return $templateResponse;
	}

	/**
	 * Get all active entries for the app discover section
	 */
	#[NoCSRFRequired]
	public function getAppDiscoverJSON(): JSONResponse {
		$data = $this->discoverFetcher->get(true);
		return new JSONResponse(array_values($data));
	}

	/**
	 * Get a image for the app discover section - this is proxied for privacy and CSP reasons
	 *
	 * @param string $image
	 * @throws \Exception
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function getAppDiscoverMedia(string $fileName): Response {
		$etag = $this->discoverFetcher->getETag() ?? date('Y-m');
		$folder = null;
		try {
			$folder = $this->appData->getFolder('app-discover-cache');
			$this->cleanUpImageCache($folder, $etag);
		} catch (\Throwable $e) {
			$folder = $this->appData->newFolder('app-discover-cache');
		}

		// Get the current cache folder
		try {
			$folder = $folder->getFolder($etag);
		} catch (NotFoundException $e) {
			$folder = $folder->newFolder($etag);
		}

		$info = pathinfo($fileName);
		$hashName = md5($fileName);
		$allFiles = $folder->getDirectoryListing();
		// Try to find the file
		$file = array_filter($allFiles, function (ISimpleFile $file) use ($hashName) {
			return str_starts_with($file->getName(), $hashName);
		});
		// Get the first entry
		$file = reset($file);
		// If not found request from Web
		if ($file === false) {
			try {
				$client = $this->clientService->newClient();
				$fileResponse = $client->get($fileName);
				$contentType = $fileResponse->getHeader('Content-Type');
				$extension = $info['extension'] ?? '';
				$file = $folder->newFile($hashName . '.' . base64_encode($contentType) . '.' . $extension, $fileResponse->getBody());
			} catch (\Throwable $e) {
				$this->logger->warning('Could not load media file for app discover section', ['media_src' => $fileName, 'exception' => $e]);
				return new NotFoundResponse();
			}
		} else {
			// File was found so we can get the content type from the file name
			$contentType = base64_decode(explode('.', $file->getName())[1] ?? '');
		}

		$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $contentType]);
		// cache for 7 days
		$response->cacheFor(604800, false, true);
		return $response;
	}

	/**
	 * Remove orphaned folders from the image cache that do not match the current etag
	 * @param ISimpleFolder $folder The folder to clear
	 * @param string $etag The etag (directory name) to keep
	 */
	private function cleanUpImageCache(ISimpleFolder $folder, string $etag): void {
		// Cleanup old cache folders
		$allFiles = $folder->getDirectoryListing();
		foreach ($allFiles as $dir) {
			try {
				if ($dir->getName() !== $etag) {
					$dir->delete();
				}
			} catch (NotPermittedException $e) {
				// ignore folder for now
			}
		}
	}

	private function getAppsWithUpdates() {
		$appClass = new \OC_App();
		$apps = $appClass->listAllApps();
		foreach ($apps as $key => $app) {
			$newVersion = $this->installer->isUpdateAvailable($app['id']);
			if ($newVersion === false) {
				unset($apps[$key]);
			}
		}
		return $apps;
	}

	private function getBundles() {
		$result = [];
		$bundles = $this->bundleFetcher->getBundles();
		foreach ($bundles as $bundle) {
			$result[] = [
				'name' => $bundle->getName(),
				'id' => $bundle->getIdentifier(),
				'appIdentifiers' => $bundle->getAppIdentifiers()
			];
		}
		return $result;
	}

	/**
	 * Get all available categories
	 *
	 * @return JSONResponse
	 */
	public function listCategories(): JSONResponse {
		return new JSONResponse($this->getAllCategories());
	}

	private function getAllCategories() {
		$currentLanguage = substr($this->l10nFactory->findLanguage(), 0, 2);

		$categories = $this->categoryFetcher->get();
		return array_map(fn ($category) => [
			'id' => $category['id'],
			'displayName' => $category['translations'][$currentLanguage]['name'] ?? $category['translations']['en']['name'],
		], $categories);
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

	private function fetchApps() {
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
		$supportedApps = $appClass->getSupportedApps();
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
		return $this->allApps;
	}

	/**
	 * Get all available apps in a category
	 *
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function listApps(): JSONResponse {
		$this->fetchApps();
		$apps = $this->getAllApps();

		$dependencyAnalyzer = new DependencyAnalyzer(new Platform($this->config), $this->l10n);

		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		if (!is_array($ignoreMaxApps)) {
			$this->logger->warning('The value given for app_install_overwrite is not an array. Ignoring...');
			$ignoreMaxApps = [];
		}

		// Extend existing app details
		$apps = array_map(function (array $appData) use ($dependencyAnalyzer, $ignoreMaxApps) {
			if (isset($appData['appstoreData'])) {
				$appstoreData = $appData['appstoreData'];
				$appData['screenshot'] = $this->createProxyPreviewUrl($appstoreData['screenshots'][0]['url'] ?? '');
				$appData['category'] = $appstoreData['categories'];
				$appData['releases'] = $appstoreData['releases'];
			}

			$newVersion = $this->installer->isUpdateAvailable($appData['id']);
			if ($newVersion) {
				$appData['update'] = $newVersion;
			}

			// fix groups to be an array
			$groups = [];
			if (is_string($appData['groups'])) {
				$groups = json_decode($appData['groups']);
				// ensure 'groups' is an array
				if (!is_array($groups)) {
					$groups = [$groups];
				}
			}
			$appData['groups'] = $groups;
			$appData['canUnInstall'] = !$appData['active'] && $appData['removable'];

			// fix licence vs license
			if (isset($appData['license']) && !isset($appData['licence'])) {
				$appData['licence'] = $appData['license'];
			}

			$ignoreMax = in_array($appData['id'], $ignoreMaxApps);

			// analyse dependencies
			$missing = $dependencyAnalyzer->analyze($appData, $ignoreMax);
			$appData['canInstall'] = empty($missing);
			$appData['missingDependencies'] = $missing;

			$appData['missingMinOwnCloudVersion'] = !isset($appData['dependencies']['nextcloud']['@attributes']['min-version']);
			$appData['missingMaxOwnCloudVersion'] = !isset($appData['dependencies']['nextcloud']['@attributes']['max-version']);
			$appData['isCompatible'] = $dependencyAnalyzer->isMarkedCompatible($appData);

			return $appData;
		}, $apps);

		usort($apps, [$this, 'sortApps']);

		return new JSONResponse(['apps' => $apps, 'status' => 'success']);
	}

	/**
	 * Get all apps for a category from the app store
	 *
	 * @param string $requestedCategory
	 * @return array
	 * @throws \Exception
	 */
	private function getAppsForCategory($requestedCategory = ''): array {
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
			$nextCloudVersion = $versionParser->getVersion($app['releases'][0]['rawPlatformVersionSpec']);
			$nextCloudVersionDependencies = [];
			if ($nextCloudVersion->getMinimumVersion() !== '') {
				$nextCloudVersionDependencies['nextcloud']['@attributes']['min-version'] = $nextCloudVersion->getMinimumVersion();
			}
			if ($nextCloudVersion->getMaximumVersion() !== '') {
				$nextCloudVersionDependencies['nextcloud']['@attributes']['max-version'] = $nextCloudVersion->getMaximumVersion();
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
			$enabledValue = $this->config->getAppValue($app['id'], 'enabled', 'no');
			$groups = null;
			if ($enabledValue !== 'no' && $enabledValue !== 'yes') {
				$groups = $enabledValue;
			}

			$currentVersion = '';
			if ($this->appManager->isInstalled($app['id'])) {
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
				'default_enable' => '',
				'types' => [],
				'documentation' => [
					'admin' => $app['adminDocs'],
					'user' => $app['userDocs'],
					'developer' => $app['developerDocs']
				],
				'website' => $app['website'],
				'bugs' => $app['issueTracker'],
				'detailpage' => $app['website'],
				'dependencies' => array_merge(
					$nextCloudVersionDependencies,
					$phpDependencies
				),
				'level' => ($app['isFeatured'] === true) ? 200 : 100,
				'missingMaxOwnCloudVersion' => false,
				'missingMinOwnCloudVersion' => false,
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
	 * @param string $appId
	 * @param array $groups
	 * @return JSONResponse
	 */
	#[PasswordConfirmationRequired]
	public function enableApp(string $appId, array $groups = []): JSONResponse {
		return $this->enableApps([$appId], $groups);
	}

	/**
	 * Enable one or more apps
	 *
	 * apps will be enabled for specific groups only if $groups is defined
	 *
	 * @param array $appIds
	 * @param array $groups
	 * @return JSONResponse
	 */
	#[PasswordConfirmationRequired]
	public function enableApps(array $appIds, array $groups = []): JSONResponse {
		try {
			$updateRequired = false;

			foreach ($appIds as $appId) {
				$appId = $this->appManager->cleanAppId($appId);

				// Check if app is already downloaded
				/** @var Installer $installer */
				$installer = \OC::$server->get(Installer::class);
				$isDownloaded = $installer->isDownloaded($appId);

				if (!$isDownloaded) {
					$installer->downloadApp($appId);
				}

				$installer->installApp($appId);

				if (count($groups) > 0) {
					$this->appManager->enableAppForGroups($appId, $this->getGroupList($groups));
				} else {
					$this->appManager->enableApp($appId);
				}
				if (\OC_App::shouldUpgrade($appId)) {
					$updateRequired = true;
				}
			}
			return new JSONResponse(['data' => ['update_required' => $updateRequired]]);
		} catch (\Throwable $e) {
			$this->logger->error('could not enable apps', ['exception' => $e]);
			return new JSONResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	private function getGroupList(array $groups) {
		$groupManager = \OC::$server->getGroupManager();
		$groupsList = [];
		foreach ($groups as $group) {
			$groupItem = $groupManager->get($group);
			if ($groupItem instanceof IGroup) {
				$groupsList[] = $groupManager->get($group);
			}
		}
		return $groupsList;
	}

	/**
	 * @param string $appId
	 * @return JSONResponse
	 */
	#[PasswordConfirmationRequired]
	public function disableApp(string $appId): JSONResponse {
		return $this->disableApps([$appId]);
	}

	/**
	 * @param array $appIds
	 * @return JSONResponse
	 */
	#[PasswordConfirmationRequired]
	public function disableApps(array $appIds): JSONResponse {
		try {
			foreach ($appIds as $appId) {
				$appId = $this->appManager->cleanAppId($appId);
				$this->appManager->disableApp($appId);
			}
			return new JSONResponse([]);
		} catch (\Exception $e) {
			$this->logger->error('could not disable app', ['exception' => $e]);
			return new JSONResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @param string $appId
	 * @return JSONResponse
	 */
	#[PasswordConfirmationRequired]
	public function uninstallApp(string $appId): JSONResponse {
		$appId = $this->appManager->cleanAppId($appId);
		$result = $this->installer->removeApp($appId);
		if ($result !== false) {
			// If this app was force enabled, remove the force-enabled-state
			$this->appManager->removeOverwriteNextcloudRequirement($appId);
			$this->appManager->clearAppsCache();
			return new JSONResponse(['data' => ['appid' => $appId]]);
		}
		return new JSONResponse(['data' => ['message' => $this->l10n->t('Could not remove app.')]], Http::STATUS_INTERNAL_SERVER_ERROR);
	}

	/**
	 * @param string $appId
	 * @return JSONResponse
	 */
	public function updateApp(string $appId): JSONResponse {
		$appId = $this->appManager->cleanAppId($appId);

		$this->config->setSystemValue('maintenance', true);
		try {
			$result = $this->installer->updateAppstoreApp($appId);
			$this->config->setSystemValue('maintenance', false);
		} catch (\Exception $ex) {
			$this->config->setSystemValue('maintenance', false);
			return new JSONResponse(['data' => ['message' => $ex->getMessage()]], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		if ($result !== false) {
			return new JSONResponse(['data' => ['appid' => $appId]]);
		}
		return new JSONResponse(['data' => ['message' => $this->l10n->t('Could not update app.')]], Http::STATUS_INTERNAL_SERVER_ERROR);
	}

	private function sortApps($a, $b) {
		$a = (string)$a['name'];
		$b = (string)$b['name'];
		if ($a === $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}

	public function force(string $appId): JSONResponse {
		$appId = $this->appManager->cleanAppId($appId);
		$this->appManager->overwriteNextcloudRequirement($appId);
		return new JSONResponse();
	}
}
