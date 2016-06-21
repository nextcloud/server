<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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

namespace OCA\Files_Sharing\AppInfo;

use OCA\FederatedFileSharing\DiscoveryManager;
use OCA\Files_Sharing\MountProvider;
use OCP\AppFramework\App;
use OC\AppFramework\Utility\SimpleContainer;
use OCA\Files_Sharing\Controllers\ExternalSharesController;
use OCA\Files_Sharing\Controllers\ShareController;
use OCA\Files_Sharing\Middleware\SharingCheckMiddleware;
use \OCP\IContainer;
use OCA\Files_Sharing\Capabilities;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_sharing', $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

		/**
		 * Controllers
		 */
		$container->registerService('ShareController', function (SimpleContainer $c) use ($server) {
			$federatedSharingApp = new \OCA\FederatedFileSharing\AppInfo\Application('federatedfilesharing');
			return new ShareController(
				$c->query('AppName'),
				$c->query('Request'),
				$server->getConfig(),
				$server->getURLGenerator(),
				$server->getUserManager(),
				$server->getLogger(),
				$server->getActivityManager(),
				$server->getShareManager(),
				$server->getSession(),
				$server->getPreviewManager(),
				$server->getRootFolder(),
				$federatedSharingApp->getFederatedShareProvider()
			);
		});
		$container->registerService('ExternalSharesController', function (SimpleContainer $c) {
			return new ExternalSharesController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ExternalManager'),
				$c->query('HttpClientService')
			);
		});

		/**
		 * Core class wrappers
		 */
		$container->registerService('HttpClientService', function (SimpleContainer $c) use ($server) {
			return $server->getHTTPClientService();
		});
		$container->registerService('ExternalManager', function (SimpleContainer $c) use ($server) {
			$user = $server->getUserSession()->getUser();
			$uid = $user ? $user->getUID() : null;
			$discoveryManager = new DiscoveryManager(
				\OC::$server->getMemCacheFactory(),
				\OC::$server->getHTTPClientService()
			);
			return new \OCA\Files_Sharing\External\Manager(
				$server->getDatabaseConnection(),
				\OC\Files\Filesystem::getMountManager(),
				\OC\Files\Filesystem::getLoader(),
				$server->getHTTPHelper(),
				$server->getNotificationManager(),
				$discoveryManager,
				$uid
			);
		});

		/**
		 * Middleware
		 */
		$container->registerService('SharingCheckMiddleware', function (SimpleContainer $c) use ($server) {
			return new SharingCheckMiddleware(
				$c->query('AppName'),
				$server->getConfig(),
				$server->getAppManager(),
				$c['ControllerMethodReflector']
			);
		});

		// Execute middlewares
		$container->registerMiddleware('SharingCheckMiddleware');

		$container->registerService('MountProvider', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');
			return new MountProvider(
				$server->getConfig(),
				$server->getShareManager(),
				$server->getLogger()
			);
		});

		$container->registerService('ExternalMountProvider', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');
			return new \OCA\Files_Sharing\External\MountProvider(
				$server->getDatabaseConnection(),
				function() use ($c) {
					return $c->query('ExternalManager');
				}
			);
		});

		/*
		 * Register capabilities
		 */
		$container->registerCapability('OCA\Files_Sharing\Capabilities');
	}

	public function registerMountProviders() {
		/** @var \OCP\IServerContainer $server */
		$server = $this->getContainer()->query('ServerContainer');
		$mountProviderCollection = $server->getMountProviderCollection();
		$mountProviderCollection->registerProvider($this->getContainer()->query('MountProvider'));
		$mountProviderCollection->registerProvider($this->getContainer()->query('ExternalMountProvider'));
	}
}
