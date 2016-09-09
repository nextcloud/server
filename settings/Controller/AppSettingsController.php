<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OC\OCSClient;
use OCP\App\IAppManager;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\INavigationManager;
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
	 * @param INavigationManager $navigationManager
	 * @param IAppManager $appManager
	 * @param OCSClient $ocsClient
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config,
								INavigationManager $navigationManager,
								IAppManager $appManager,
								OCSClient $ocsClient) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->navigationManager = $navigationManager;
		$this->appManager = $appManager;
		$this->ocsClient = $ocsClient;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param string $category
	 * @return TemplateResponse
	 */
	public function viewApps($category = '') {
		$params = [];
		$params['category'] = $category;
		$params['appstoreEnabled'] = $this->config->getSystemValue('appstoreenabled', true) === true;
		$this->navigationManager->setActiveEntry('core_apps');

		$templateResponse = new TemplateResponse($this->appName, 'apps', $params, 'user');
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('*');
		$templateResponse->setContentSecurityPolicy($policy);

		return $templateResponse;
	}

	/**
	 * Get all available categories
	 *
	 * @return array
	 */
	public function listCategories() {
		$categories = [
			[
				'id' => 'enabled',
				'displayName' => (string)$this->l10n->t('Enabled'),
			],
			[
				'id' => 'disabled',
				'displayName' => (string)$this->l10n->t('Not enabled'),
			],
		];

		if($this->ocsClient->isAppStoreEnabled()) {
			$categories = array_merge($categories, $this->ocsClient->getCategories());
		}

		return $categories;
	}

	/**
	 * Get all available apps
	 *
	 * @return array
	 */
	public function listApps() {
		// FIXME: Inject version
		$version = '9.1.0';//implode('.', \OCP\Util::getVersion());
		return $this->ocsClient->getApplications($version);
	}

}
