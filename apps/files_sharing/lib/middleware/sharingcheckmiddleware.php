<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Middleware;

use OCP\App\IAppManager;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;

/**
 * Checks whether the "sharing check" is enabled
 *
 * @package OCA\Files_Sharing\Middleware
 */
class SharingCheckMiddleware extends Middleware {

	/** @var string */
	protected $appName;
	/** @var IConfig */
	protected $config;
	/** @var IAppManager */
	protected $appManager;

	/***
	 * @param string $appName
	 * @param IConfig $config
	 * @param IAppManager $appManager
	 */
	public function __construct($appName,
								IConfig $config,
								IAppManager $appManager) {
		$this->appName = $appName;
		$this->config = $config;
		$this->appManager = $appManager;
	}

	/**
	 * Check if sharing is enabled before the controllers is executed
	 */
	public function beforeController($controller, $methodName) {
		if(!$this->isSharingEnabled()) {
			throw new \Exception('Sharing is disabled.');
		}
	}

	/**
	 * Return 404 page in case of an exception
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return TemplateResponse
	 */
	public function afterException($controller, $methodName, \Exception $exception){
		return new TemplateResponse('core', '404', array(), 'guest');
	}

	/**
	 * Check whether sharing is enabled
	 * @return bool
	 */
	private function isSharingEnabled() {
		// FIXME: This check is done here since the route is globally defined and not inside the files_sharing app
		// Check whether the sharing application is enabled
		if(!$this->appManager->isEnabledForUser($this->appName)) {
			return false;
		}

		// Check whether public sharing is enabled
		if($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			return false;
		}

		return true;
	}

}
