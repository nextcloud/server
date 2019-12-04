<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files\AppInfo;

use OCA\Files\Activity\Helper;
use OCA\Files\Capabilities;
use OCA\Files\Collaboration\Resources\Listener;
use OCA\Files\Collaboration\Resources\ResourceProvider;
use OCA\Files\Controller\ApiController;
use OCA\Files\Controller\ViewController;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Listener\LegacyLoadAdditionalScriptsAdapter;
use OCA\Files\Notification\Notifier;
use OCA\Files\Service\TagService;
use OCP\AppFramework\App;
use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IContainer;

class Application extends App {

	public const APP_ID = 'files';

	public function __construct(array $urlParams=array()) {
		parent::__construct(self::APP_ID, $urlParams);
		$container = $this->getContainer();
		$server = $container->getServer();

		/**
		 * Controllers
		 */
		$container->registerService('APIController', function (IContainer $c) use ($server) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$server->getUserSession(),
				$c->query('TagService'),
				$server->getPreviewManager(),
				$server->getShareManager(),
				$server->getConfig(),
				$server->getUserFolder()
			);
		});

		/**
		 * Services
		 */
		$container->registerService('TagService', function(IContainer $c) use ($server) {
			$homeFolder = $c->query('ServerContainer')->getUserFolder();
			return new TagService(
				$c->query('ServerContainer')->getUserSession(),
				$c->query('ServerContainer')->getActivityManager(),
				$c->query('ServerContainer')->getTagManager()->load(self::APP_ID),
				$homeFolder,
				$server->getEventDispatcher()
			);
		});

		/*
		 * Register capabilities
		 */
		$container->registerCapability(Capabilities::class);

		/**
		 * Register Collaboration ResourceProvider
		 */
		/** @var IManager $resourceManager */
		$resourceManager = $container->query(IManager::class);
		$resourceManager->registerResourceProvider(ResourceProvider::class);
		Listener::register($server->getEventDispatcher());

		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $container->query(IEventDispatcher::class);
		$dispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, LegacyLoadAdditionalScriptsAdapter::class);

		/** @var \OCP\Notification\IManager $notifications */
		$notifications = $container->query(\OCP\Notification\IManager::class);
		$notifications->registerNotifierService(Notifier::class);
	}
}
