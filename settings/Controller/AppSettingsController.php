<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Felix A. Epp <work@felixepp.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\Settings\Controller;

use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\App\AppStore\Version\VersionParser;
use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OC\Installer;
use OCP\App\IAppManager;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IConfig;
use OCP\L10N\IFactory;

/**
 * @package OC\Settings\Controller
 */
class AppSettingsController extends Controller {
	const CAT_ENABLED = 0;
	const CAT_DISABLED = 1;
	const CAT_ALL_INSTALLED = 2;
	const CAT_APP_BUNDLES = 3;
	const CAT_UPDATES = 4;

	/** @var \OCP\IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var INavigationManager */
	private $navigationManager;
	/** @var IAppManager */
	private $appManager;
	/** @var CategoryFetcher */
	private $categoryFetcher;
	/** @var AppFetcher */
	private $appFetcher;
	/** @var IFactory */
	private $l10nFactory;
	/** @var BundleFetcher */
	private $bundleFetcher;
	/** @var Installer */
	private $installer;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param INavigationManager $navigationManager
	 * @param IAppManager $appManager
	 * @param CategoryFetcher $categoryFetcher
	 * @param AppFetcher $appFetcher
	 * @param IFactory $l10nFactory
	 * @param BundleFetcher $bundleFetcher
	 * @param Installer $installer
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config,
								INavigationManager $navigationManager,
								IAppManager $appManager,
								CategoryFetcher $categoryFetcher,
								AppFetcher $appFetcher,
								IFactory $l10nFactory,
								BundleFetcher $bundleFetcher,
								Installer $installer) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->navigationManager = $navigationManager;
		$this->appManager = $appManager;
		$this->categoryFetcher = $categoryFetcher;
		$this->appFetcher = $appFetcher;
		$this->l10nFactory = $l10nFactory;
		$this->bundleFetcher = $bundleFetcher;
		$this->installer = $installer;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param string $category
	 * @return TemplateResponse
	 */
	public function viewApps($category = '') {
		if ($category === '') {
			$category = 'installed';
		}

		$params = [];
		$params['category'] = $category;
		$params['appstoreEnabled'] = $this->config->getSystemValue('appstoreenabled', true) === true;
		$this->navigationManager->setActiveEntry('core_apps');

		$templateResponse = new TemplateResponse($this->appName, 'apps', $params, 'user');
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://usercontent.apps.nextcloud.com');
		$templateResponse->setContentSecurityPolicy($policy);

		return $templateResponse;
	}

	private function getAllCategories() {
		$currentLanguage = substr($this->l10nFactory->findLanguage(), 0, 2);

		$updateCount = count($this->getAppsWithUpdates());
		$formattedCategories = [
			['id' => self::CAT_ALL_INSTALLED, 'ident' => 'installed', 'displayName' => (string)$this->l10n->t('Your apps')],
			['id' => self::CAT_UPDATES, 'ident' => 'updates', 'displayName' => (string)$this->l10n->t('Updates'), 'counter' => $updateCount],
			['id' => self::CAT_ENABLED, 'ident' => 'enabled', 'displayName' => (string)$this->l10n->t('Enabled apps')],
			['id' => self::CAT_DISABLED, 'ident' => 'disabled', 'displayName' => (string)$this->l10n->t('Disabled apps')],
			['id' => self::CAT_APP_BUNDLES, 'ident' => 'app-bundles', 'displayName' => (string)$this->l10n->t('App bundles')],
		];
		$categories = $this->categoryFetcher->get();
		foreach($categories as $category) {
			$formattedCategories[] = [
				'id' => $category['id'],
				'ident' => $category['id'],
				'displayName' => isset($category['translations'][$currentLanguage]['name']) ? $category['translations'][$currentLanguage]['name'] : $category['translations']['en']['name'],
			];
		}

		return $formattedCategories;
	}

	/**
	 * Get all available categories
	 *
	 * @return JSONResponse
	 */
	public function listCategories() {
		return new JSONResponse($this->getAllCategories());
	}

	/**
	 * Get all apps for a category
	 *
	 * @param string $requestedCategory
	 * @return array
	 */
	private function getAppsForCategory($requestedCategory) {
		$versionParser = new VersionParser();
		$formattedApps = [];
		$apps = $this->appFetcher->get();
		foreach($apps as $app) {
			if (isset($app['isFeatured'])) {
				$app['featured'] = $app['isFeatured'];
			}

			// Skip all apps not in the requested category
			$isInCategory = false;
			foreach($app['categories'] as $category) {
				if($category === $requestedCategory) {
					$isInCategory = true;
				}
			}
			if(!$isInCategory) {
				continue;
			}

			$nextCloudVersion = $versionParser->getVersion($app['releases'][0]['rawPlatformVersionSpec']);
			$nextCloudVersionDependencies = [];
			if($nextCloudVersion->getMinimumVersion() !== '') {
				$nextCloudVersionDependencies['nextcloud']['@attributes']['min-version'] = $nextCloudVersion->getMinimumVersion();
			}
			if($nextCloudVersion->getMaximumVersion() !== '') {
				$nextCloudVersionDependencies['nextcloud']['@attributes']['max-version'] = $nextCloudVersion->getMaximumVersion();
			}
			$phpVersion = $versionParser->getVersion($app['releases'][0]['rawPhpVersionSpec']);
			$existsLocally = (\OC_App::getAppPath($app['id']) !== false) ? true : false;
			$phpDependencies = [];
			if($phpVersion->getMinimumVersion() !== '') {
				$phpDependencies['php']['@attributes']['min-version'] = $phpVersion->getMinimumVersion();
			}
			if($phpVersion->getMaximumVersion() !== '') {
				$phpDependencies['php']['@attributes']['max-version'] = $phpVersion->getMaximumVersion();
			}
			if(isset($app['releases'][0]['minIntSize'])) {
				$phpDependencies['php']['@attributes']['min-int-size'] = $app['releases'][0]['minIntSize'];
			}
			$authors = '';
			foreach($app['authors'] as $key => $author) {
				$authors .= $author['name'];
				if($key !== count($app['authors']) - 1) {
					$authors .= ', ';
				}
			}

			$currentLanguage = substr(\OC::$server->getL10NFactory()->findLanguage(), 0, 2);
			$enabledValue = $this->config->getAppValue($app['id'], 'enabled', 'no');
			$groups = null;
			if($enabledValue !== 'no' && $enabledValue !== 'yes') {
				$groups = $enabledValue;
			}

			$currentVersion = '';
			if($this->appManager->isInstalled($app['id'])) {
				$currentVersion = \OC_App::getAppVersion($app['id']);
			} else {
				$currentLanguage = $app['releases'][0]['version'];
			}

			$formattedApps[] = [
				'id' => $app['id'],
				'name' => isset($app['translations'][$currentLanguage]['name']) ? $app['translations'][$currentLanguage]['name'] : $app['translations']['en']['name'],
				'description' => isset($app['translations'][$currentLanguage]['description']) ? $app['translations'][$currentLanguage]['description'] : $app['translations']['en']['description'],
				'license' => $app['releases'][0]['licenses'],
				'author' => $authors,
				'shipped' => false,
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
				'level' => ($app['featured'] === true) ? 200 : 100,
				'missingMaxOwnCloudVersion' => false,
				'missingMinOwnCloudVersion' => false,
				'canInstall' => true,
				'preview' => isset($app['screenshots'][0]['url']) ? 'https://usercontent.apps.nextcloud.com/'.base64_encode($app['screenshots'][0]['url']) : '',
				'score' => $app['ratingOverall'],
				'ratingNumOverall' => $app['ratingNumOverall'],
				'ratingNumThresholdReached' => $app['ratingNumOverall'] > 5 ? true : false,
				'removable' => $existsLocally,
				'active' => $this->appManager->isEnabledForUser($app['id']),
				'needsDownload' => !$existsLocally,
				'groups' => $groups,
				'fromAppStore' => true,
			];


			$newVersion = $this->installer->isUpdateAvailable($app['id']);
			if($newVersion && $this->appManager->isInstalled($app['id'])) {
				$formattedApps[count($formattedApps)-1]['update'] = $newVersion;
			}
		}

		return $formattedApps;
	}

	private function getAppsWithUpdates() {
		$appClass = new \OC_App();
		$apps = $appClass->listAllApps();
		foreach($apps as $key => $app) {
			$newVersion = $this->installer->isUpdateAvailable($app['id']);
			if($newVersion !== false) {
				$apps[$key]['update'] = $newVersion;
			} else {
				unset($apps[$key]);
			}
		}
		usort($apps, function ($a, $b) {
			$a = (string)$a['name'];
			$b = (string)$b['name'];
			if ($a === $b) {
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		});
		return $apps;
	}

	/**
	 * Get all available apps in a category
	 *
	 * @param string $category
	 * @return JSONResponse
	 */
	public function listApps($category = '') {
		$appClass = new \OC_App();

		switch ($category) {
			// installed apps
			case 'installed':
				$apps = $appClass->listAllApps();

				foreach($apps as $key => $app) {
					$newVersion = $this->installer->isUpdateAvailable($app['id']);
					$apps[$key]['update'] = $newVersion;
				}

				usort($apps, function ($a, $b) {
					$a = (string)$a['name'];
					$b = (string)$b['name'];
					if ($a === $b) {
						return 0;
					}
					return ($a < $b) ? -1 : 1;
				});
				break;
			// updates
			case 'updates':
				$apps = $this->getAppsWithUpdates();
				break;
			// enabled apps
			case 'enabled':
				$apps = $appClass->listAllApps();
				$apps = array_filter($apps, function ($app) {
					return $app['active'];
				});

				foreach($apps as $key => $app) {
					$newVersion = $this->installer->isUpdateAvailable($app['id']);
					$apps[$key]['update'] = $newVersion;
				}

				usort($apps, function ($a, $b) {
					$a = (string)$a['name'];
					$b = (string)$b['name'];
					if ($a === $b) {
						return 0;
					}
					return ($a < $b) ? -1 : 1;
				});
				break;
			// disabled  apps
			case 'disabled':
				$apps = $appClass->listAllApps();
				$apps = array_filter($apps, function ($app) {
					return !$app['active'];
				});

				$apps = array_map(function ($app) {
					$newVersion = $this->installer->isUpdateAvailable($app['id']);
					if ($newVersion !== false) {
						$app['update'] = $newVersion;
					}
					return $app;
				}, $apps);

				usort($apps, function ($a, $b) {
					$a = (string)$a['name'];
					$b = (string)$b['name'];
					if ($a === $b) {
						return 0;
					}
					return ($a < $b) ? -1 : 1;
				});
				break;
			case 'app-bundles':
				$bundles = $this->bundleFetcher->getBundles();
				$apps = [];
				foreach($bundles as $bundle) {
					$newCategory = true;
					$allApps = $appClass->listAllApps();
					$categories = $this->getAllCategories();
					foreach($categories as $singleCategory) {
						$newApps = $this->getAppsForCategory($singleCategory['id']);
						foreach($allApps as $app) {
							foreach($newApps as $key => $newApp) {
								if($app['id'] === $newApp['id']) {
									unset($newApps[$key]);
								}
							}
						}
						$allApps = array_merge($allApps, $newApps);
					}

					foreach($bundle->getAppIdentifiers() as $identifier) {
						foreach($allApps as $app) {
							if($app['id'] === $identifier) {
								if($newCategory) {
									$app['newCategory'] = true;
									$app['categoryName'] = $bundle->getName();
								}
								$app['bundleId'] = $bundle->getIdentifier();
								$newCategory = false;
								$apps[] = $app;
								continue;
							}
						}
					}
				}
				break;
			default:
				$apps = $this->getAppsForCategory($category);

				// sort by score
				usort($apps, function ($a, $b) {
					$a = (int)$a['score'];
					$b = (int)$b['score'];
					if ($a === $b) {
						return 0;
					}
					return ($a > $b) ? -1 : 1;
				});
				break;
		}

		// fix groups to be an array
		$dependencyAnalyzer = new DependencyAnalyzer(new Platform($this->config), $this->l10n);
		$apps = array_map(function($app) use ($dependencyAnalyzer) {

			// fix groups
			$groups = array();
			if (is_string($app['groups'])) {
				$groups = json_decode($app['groups']);
			}
			$app['groups'] = $groups;
			$app['canUnInstall'] = !$app['active'] && $app['removable'];

			// fix licence vs license
			if (isset($app['license']) && !isset($app['licence'])) {
				$app['licence'] = $app['license'];
			}

			// analyse dependencies
			$missing = $dependencyAnalyzer->analyze($app);
			$app['canInstall'] = empty($missing);
			$app['missingDependencies'] = $missing;

			$app['missingMinOwnCloudVersion'] = !isset($app['dependencies']['nextcloud']['@attributes']['min-version']);
			$app['missingMaxOwnCloudVersion'] = !isset($app['dependencies']['nextcloud']['@attributes']['max-version']);

			return $app;
		}, $apps);

		return new JSONResponse(['apps' => $apps, 'status' => 'success']);
	}
}
