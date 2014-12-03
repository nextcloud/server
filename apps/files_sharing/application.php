<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing;

use OC\AppFramework\Utility\SimpleContainer;
use OCA\Files_Sharing\Controllers\ShareController;
use OCA\Files_Sharing\Middleware\SharingCheckMiddleware;
use \OCP\AppFramework\App;

/**
 * @package OCA\Files_Sharing
 */
class Application extends App {


	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=array()){
		parent::__construct('files_sharing', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('ShareController', function(SimpleContainer $c) {
			return new ShareController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserSession'),
				$c->query('ServerContainer')->getAppConfig(),
				$c->query('ServerContainer')->getConfig(),
				$c->query('URLGenerator'),
				$c->query('ServerContainer')->getUserManager(),
				$c->query('ServerContainer')->getLogger()
			);
		});

		/**
		 * Core class wrappers
		 */
		$container->registerService('UserSession', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getUserSession();
		});
		$container->registerService('URLGenerator', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getUrlGenerator();
		});

		/**
		 * Middleware
		 */
		$container->registerService('SharingCheckMiddleware', function(SimpleContainer $c){
			return new SharingCheckMiddleware(
				$c->query('AppName'),
				$c->query('ServerContainer')->getAppConfig(),
				$c->getCoreApi()
			);
		});

		// Execute middlewares
		$container->registerMiddleware('SharingCheckMiddleware');
	}

}
