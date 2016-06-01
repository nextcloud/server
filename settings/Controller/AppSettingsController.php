<?php
/**
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OC\OCSClient;
use OCP\App\IAppManager;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\ICacheFactory;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IConfig;

/**
 * @package OC\Settings\Controller
 */
class AppSettingsController extends Controller {
	const CAT_ENABLED = 0;
	const CAT_DISABLED = 1;

	/** @var \OCP\IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var \OCP\ICache */
	private $cache;
	/** @var INavigationManager */
	private $navigationManager;
	/** @var IAppManager */
	private $appManager;
	/** @var OCSClient */
	private $ocsClient;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param ICacheFactory $cache
	 * @param INavigationManager $navigationManager
	 * @param IAppManager $appManager
	 * @param OCSClient $ocsClient
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config,
								ICacheFactory $cache,
								INavigationManager $navigationManager,
								IAppManager $appManager,
								OCSClient $ocsClient) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->cache = $cache->create($appName);
		$this->navigationManager = $navigationManager;
		$this->appManager = $appManager;
		$this->ocsClient = $ocsClient;
	}

	/**
	 * Enables or disables the display of experimental apps
	 * @param bool $state
	 * @return DataResponse
	 */
	public function changeExperimentalConfigState($state) {
		$this->config->setSystemValue('appstore.experimental.enabled', $state);
		$this->appManager->clearAppsCache();
		return new DataResponse();
	}

	/**
	 * @param string|int $category
	 * @return int
	 */
	protected function getCategory($category) {
		if (is_string($category)) {
			foreach ($this->listCategories() as $cat) {
				if (isset($cat['ident']) && $cat['ident'] === $category) {
					$category = (int) $cat['id'];
					break;
				}
			}

			// Didn't find the category, falling back to enabled
			if (is_string($category)) {
				$category = self::CAT_ENABLED;
			}
		}
		return (int) $category;
	}

	/**
	 * @NoCSRFRequired
	 * @param string $category
	 * @return TemplateResponse
	 */
	public function viewApps($category = '') {
		$categoryId = $this->getCategory($category);
		if ($categoryId === self::CAT_ENABLED) {
			// Do not use an arbitrary input string, because we put the category in html
			$category = 'enabled';
		}

		$params = [];
		$params['experimentalEnabled'] = $this->config->getSystemValue('appstore.experimental.enabled', false);
		$params['category'] = $category;
		$params['appstoreEnabled'] = $this->config->getSystemValue('appstoreenabled', true) === true;
		$this->navigationManager->setActiveEntry('core_apps');

		$templateResponse = new TemplateResponse($this->appName, 'apps', $params, 'user');
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://apps.owncloud.com');
		$templateResponse->setContentSecurityPolicy($policy);

		return $templateResponse;
	}

	/**
	 * Get all available categories
	 * @return array
	 */
	public function listCategories() {

		if(!is_null($this->cache->get('listCategories'))) {
			return $this->cache->get('listCategories');
		}
		$categories = [
			['id' => self::CAT_ENABLED, 'ident' => 'enabled', 'displayName' => (string)$this->l10n->t('Enabled')],
			['id' => self::CAT_DISABLED, 'ident' => 'disabled', 'displayName' => (string)$this->l10n->t('Not enabled')],
		];

		if($this->ocsClient->isAppStoreEnabled()) {
			// apps from external repo via OCS
			$ocs = $this->ocsClient->getCategories(\OCP\Util::getVersion());
			if ($ocs) {
				foreach($ocs as $k => $v) {
					$name = str_replace('ownCloud ', '', $v);
					$ident = str_replace(' ', '-', urlencode(strtolower($name)));
					$categories[] = [
						'id' => $k,
						'ident' => $ident,
						'displayName' => $name,
					];
				}
			}
		}

		$this->cache->set('listCategories', $categories, 3600);

		return $categories;
	}

	/**
	 * Get all available apps in a category
	 *
	 * @param string $category
	 * @param bool $includeUpdateInfo Should we check whether there is an update
	 *                                in the app store?
	 * @return array
	 */
	public function listApps($category = '', $includeUpdateInfo = true) {
		$category = $this->getCategory($category);
		$cacheName = 'listApps-' . $category . '-' . (int) $includeUpdateInfo;

		if(!is_null($this->cache->get($cacheName))) {
			$apps = $this->cache->get($cacheName);
		} else {
			switch ($category) {
				// installed apps
				case 0:
					$apps = $this->getInstalledApps($includeUpdateInfo);
					usort($apps, function ($a, $b) {
						$a = (string)$a['name'];
						$b = (string)$b['name'];
						if ($a === $b) {
							return 0;
						}
						return ($a < $b) ? -1 : 1;
					});
					$version = \OCP\Util::getVersion();
					foreach($apps as $key => $app) {
						if(!array_key_exists('level', $app) && array_key_exists('ocsid', $app)) {
							$remoteAppEntry = $this->ocsClient->getApplication($app['ocsid'], $version);

							if(is_array($remoteAppEntry) && array_key_exists('level', $remoteAppEntry)) {
								$apps[$key]['level'] = $remoteAppEntry['level'];
							}
						}
					}
					break;
				// not-installed apps
				case 1:
					$apps = \OC_App::listAllApps(true, $includeUpdateInfo, $this->ocsClient);
					$apps = array_filter($apps, function ($app) {
						return !$app['active'];
					});
					$version = \OCP\Util::getVersion();
					foreach($apps as $key => $app) {
						if(!array_key_exists('level', $app) && array_key_exists('ocsid', $app)) {
							$remoteAppEntry = $this->ocsClient->getApplication($app['ocsid'], $version);

							if(is_array($remoteAppEntry) && array_key_exists('level', $remoteAppEntry)) {
								$apps[$key]['level'] = $remoteAppEntry['level'];
							}
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
					break;
				default:
					$filter = $this->config->getSystemValue('appstore.experimental.enabled', false) ? 'all' : 'approved';

					$apps = \OC_App::getAppstoreApps($filter, $category, $this->ocsClient);
					if (!$apps) {
						$apps = array();
					} else {
						// don't list installed apps
						$installedApps = $this->getInstalledApps(false);
						$installedApps = array_map(function ($app) {
							if (isset($app['ocsid'])) {
								return $app['ocsid'];
							}
							return $app['id'];
						}, $installedApps);
						$apps = array_filter($apps, function ($app) use ($installedApps) {
							return !in_array($app['id'], $installedApps);
						});

						// show tooltip if app is downloaded from remote server
						$inactiveApps = $this->getInactiveApps();
						foreach ($apps as &$app) {
							$app['needsDownload'] = !in_array($app['id'], $inactiveApps);
						}
					}

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

			$app['missingMinOwnCloudVersion'] = !isset($app['dependencies']['owncloud']['@attributes']['min-version']);
			$app['missingMaxOwnCloudVersion'] = !isset($app['dependencies']['owncloud']['@attributes']['max-version']);

			return $app;
		}, $apps);

		$this->cache->set($cacheName, $apps, 300);

		return ['apps' => $apps, 'status' => 'success'];
	}

	/**
	 * @param bool $includeUpdateInfo Should we check whether there is an update
	 *                                in the app store?
	 * @return array
	 */
	private function getInstalledApps($includeUpdateInfo = true) {
		$apps = \OC_App::listAllApps(true, $includeUpdateInfo, $this->ocsClient);
		$apps = array_filter($apps, function ($app) {
			return $app['active'];
		});
		return $apps;
	}

	/**
	 * @return array
	 */
	private function getInactiveApps() {
		$inactiveApps = \OC_App::listAllApps(true, false, $this->ocsClient);
		$inactiveApps = array_filter($inactiveApps,
			function ($app) {
			return !$app['active'];
		});
		$inactiveApps = array_map(function($app) {
			if (isset($app['ocsid'])) {
				return $app['ocsid'];
			}
			return $app['id'];
		}, $inactiveApps);
		return $inactiveApps;
	}

}
