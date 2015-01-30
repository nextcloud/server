<?php
/**
 * @author Lukas Reschke
 * @author Thomas Müller
 * @copyright 2014 Lukas Reschke lukas@owncloud.com, 2014 Thomas Müller deepdiver@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings\Controller;

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use \OCP\AppFramework\Controller;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IConfig;

/**
 * @package OC\Settings\Controller
 */
class AppSettingsController extends Controller {

	/** @var \OCP\IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var \OCP\ICache */
	private $cache;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param ICacheFactory $cache
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config,
								ICacheFactory $cache) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->cache = $cache->create($appName);
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
			['id' => 0, 'displayName' => (string)$this->l10n->t('Enabled')],
			['id' => 1, 'displayName' => (string)$this->l10n->t('Not enabled')],
		];

		if($this->config->getSystemValue('appstoreenabled', true)) {
			$categories[] = ['id' => 2, 'displayName' => (string)$this->l10n->t('Recommended')];
			// apps from external repo via OCS
			$ocs = \OC_OCSClient::getCategories();
			if ($ocs) {
				foreach($ocs as $k => $v) {
					$categories[] = array(
						'id' => $k,
						'displayName' => str_replace('ownCloud ', '', $v)
					);
				}
			}
		}

		$categories['status'] = 'success';
		$this->cache->set('listCategories', $categories, 3600);

		return $categories;
	}

	/**
	 * Get all available categories
	 * @param int $category
	 * @return array
	 */
	public function listApps($category = 0) {
		if(!is_null($this->cache->get('listApps-'.$category))) {
			$apps = $this->cache->get('listApps-'.$category);
		} else {
			switch ($category) {
				// installed apps
				case 0:
					$apps = \OC_App::listAllApps(true);
					$apps = array_filter($apps, function ($app) {
						return $app['active'];
					});
					usort($apps, function ($a, $b) {
						$a = (string)$a['name'];
						$b = (string)$b['name'];
						if ($a === $b) {
							return 0;
						}
						return ($a < $b) ? -1 : 1;
					});
					break;
				// not-installed apps
				case 1:
					$apps = \OC_App::listAllApps(true);
					$apps = array_filter($apps, function ($app) {
						return !$app['active'];
					});
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
					if ($category === 2) {
						$apps = \OC_App::getAppstoreApps('approved');
						if ($apps) {
							$apps = array_filter($apps, function ($app) {
								return isset($app['internalclass']) && $app['internalclass'] === 'recommendedapp';
							});
						}
					} else {
						$apps = \OC_App::getAppstoreApps('approved', $category);
					}
					if (!$apps) {
						$apps = array();
					}
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

			return $app;
		}, $apps);

		$this->cache->set('listApps-'.$category, $apps, 300);

		return ['apps' => $apps, 'status' => 'success'];
	}
}
