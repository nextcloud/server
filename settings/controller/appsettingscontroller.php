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

use \OCP\AppFramework\Controller;
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

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
	}

	/**
	 * Get all available categories
	 * @return array
	 */
	public function listCategories() {

		$categories = array(
			array('id' => 0, 'displayName' => (string)$this->l10n->t('Enabled') ),
			array('id' => 1, 'displayName' => (string)$this->l10n->t('Not enabled') ),
		);

		if($this->config->getSystemValue('appstoreenabled', true)) {
			$categories[] = array('id' => 2, 'displayName' => (string)$this->l10n->t('Recommended') );
			// apps from external repo via OCS
			$ocs = \OC_OCSClient::getCategories();
			foreach($ocs as $k => $v) {
				$categories[] = array(
					'id' => $k,
					'displayName' => str_replace('ownCloud ', '', $v)
				);
			}
		}

		$categories['status'] = 'success';

		return $categories;
	}

	/**
	 * Get all available categories
	 * @param int $category
	 * @return array
	 */
	public function listApps($category = 0) {
		$apps = array();

		switch($category) {
			// installed apps
			case 0:
				$apps = \OC_App::listAllApps(true);
				$apps = array_filter($apps, function($app) {
					return $app['active'];
				});
				break;
			// not-installed apps
			case 1:
				$apps = \OC_App::listAllApps(true);
				$apps = array_filter($apps, function($app) {
					return !$app['active'];
				});
				break;
			default:
				if ($category === 2) {
					$apps = \OC_App::getAppstoreApps('approved');
					$apps = array_filter($apps, function($app) {
						return isset($app['internalclass']) && $app['internalclass'] === 'recommendedapp';
					});
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

		// fix groups to be an array
		$apps = array_map(function($app){
			$groups = array();
			if (is_string($app['groups'])) {
				$groups = json_decode($app['groups']);
			}
			$app['groups'] = $groups;
			$app['canUnInstall'] = !$app['active'] && $app['removable'];
			return $app;
		}, $apps);

		return array('apps' => $apps, 'status' => 'success');
	}

}
