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
use OCA\Files_Sharing\Controllers\ExternalSharesController;
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
		$server = $container->getServer();

		/**
		 * Controllers
		 */
		$container->registerService('ShareController', function(SimpleContainer $c) use ($server) {
			return new ShareController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserSession'),
				$server->getAppConfig(),
				$server->getConfig(),
				$c->query('URLGenerator'),
				$server->getUserManager(),
				$server->getLogger(),
				$server->getActivityManager()
			);
		});
		$container->registerService('ExternalSharesController', function(SimpleContainer $c) {
			return new ExternalSharesController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('IsIncomingShareEnabled'),
				$c->query('ExternalManager')
			);
		});

		/**
		 * Core class wrappers
		 */
		$container->registerService('UserSession', function(SimpleContainer $c) use ($server) {
			return $server->getUserSession();
		});
		$container->registerService('URLGenerator', function(SimpleContainer $c) use ($server){
			return $server->getUrlGenerator();
		});
		$container->registerService('IsIncomingShareEnabled', function(SimpleContainer $c) {
			return Helper::isIncomingServer2serverShareEnabled();
		});
		$container->registerService('ExternalManager', function(SimpleContainer $c) use ($server){
			$user = $server->getUserSession()->getUser();
			$uid = $user ? $user->getUID() : null;
			return new \OCA\Files_Sharing\External\Manager(
					$server->getDatabaseConnection(),
					\OC\Files\Filesystem::getMountManager(),
					\OC\Files\Filesystem::getLoader(),
					$server->getHTTPHelper(),
					$uid
			);
		});

		/**
		 * Middleware
		 */
		$container->registerService('SharingCheckMiddleware', function(SimpleContainer $c) use ($server){
			return new SharingCheckMiddleware(
				$c->query('AppName'),
				$server->getConfig(),
				$server->getAppManager()
			);
		});

		// Execute middlewares
		$container->registerMiddleware('SharingCheckMiddleware');
	}

}
