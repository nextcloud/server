<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files_Sharing\AppInfo;

use OCA\Files_Sharing\Middleware\OCSShareAPIMiddleware;
use OCA\Files_Sharing\Middleware\ShareInfoMiddleware;
use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\Notification\Notifier;
use OCP\AppFramework\App;
use OC\AppFramework\Utility\SimpleContainer;
use OCA\Files_Sharing\Controller\ExternalSharesController;
use OCA\Files_Sharing\Controller\ShareController;
use OCA\Files_Sharing\Middleware\SharingCheckMiddleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Defaults;
use OCP\Federation\ICloudIdManager;
use \OCP\IContainer;
use OCP\IServerContainer;
use OCA\Files_Sharing\Capabilities;
use OCA\Files_Sharing\External\Manager;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_sharing', $urlParams);

		$container = $this->getContainer();
		/** @var IServerContainer $server */
		$server = $container->getServer();

		/**
		 * Controllers
		 */
		$container->registerService('ShareController', function (SimpleContainer $c) use ($server) {
			$federatedSharingApp = new \OCA\FederatedFileSharing\AppInfo\Application();
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
				$federatedSharingApp->getFederatedShareProvider(),
				$server->getEventDispatcher(),
				$server->getL10N($c->query('AppName')),
				$server->query(Defaults::class)
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
		$container->registerService(ICloudIdManager::class, function (SimpleContainer $c) use ($server) {
			return $server->getCloudIdManager();
		});
		$container->registerService('ExternalManager', function (SimpleContainer $c) use ($server) {
			$user = $server->getUserSession()->getUser();
			$uid = $user ? $user->getUID() : null;
			return new \OCA\Files_Sharing\External\Manager(
				$server->getDatabaseConnection(),
				\OC\Files\Filesystem::getMountManager(),
				\OC\Files\Filesystem::getLoader(),
				$server->getHTTPClientService(),
				$server->getNotificationManager(),
				$server->query(\OCP\OCS\IDiscoveryService::class),
				$server->getCloudFederationProviderManager(),
				$server->getCloudFederationFactory(),
				$server->getGroupManager(),
				$server->getUserManager(),
				$uid
			);
		});
		$container->registerAlias(Manager::class, 'ExternalManager');

		/**
		 * Middleware
		 */
		$container->registerService('SharingCheckMiddleware', function (SimpleContainer $c) use ($server) {
			return new SharingCheckMiddleware(
				$c->query('AppName'),
				$server->getConfig(),
				$server->getAppManager(),
				$server->query(IControllerMethodReflector::class),
				$server->getShareManager(),
				$server->getRequest()
			);
		});

		$container->registerService(ShareInfoMiddleware::class, function () use ($server) {
			return new ShareInfoMiddleware(
				$server->getShareManager()
			);
		});

		// Execute middlewares
		$container->registerMiddleWare('SharingCheckMiddleware');
		$container->registerMiddleWare(OCSShareAPIMiddleware::class);
		$container->registerMiddleWare(ShareInfoMiddleware::class);

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
				},
				$server->getCloudIdManager()
			);
		});

		/*
		 * Register capabilities
		 */
		$container->registerCapability(Capabilities::class);

		/** @var \OCP\Notification\IManager $notifications */
		$notifications = $container->query(\OCP\Notification\IManager::class);
		$notifications->registerNotifierService(Notifier::class);
	}

	public function registerMountProviders() {
		/** @var \OCP\IServerContainer $server */
		$server = $this->getContainer()->query('ServerContainer');
		$mountProviderCollection = $server->getMountProviderCollection();
		$mountProviderCollection->registerProvider($this->getContainer()->query('MountProvider'));
		$mountProviderCollection->registerProvider($this->getContainer()->query('ExternalMountProvider'));
	}
}
