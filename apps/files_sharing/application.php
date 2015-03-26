<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
				$c->query('UserManager'),
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
		$container->registerService('UserManager', function(SimpleContainer $c) use ($server){
			return $server->getUserManager();
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
