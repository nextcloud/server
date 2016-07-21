<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
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

use OCA\Files\Controller\ApiController;
use OCP\AppFramework\App;
use \OCA\Files\Service\TagService;
use \OCP\IContainer;
use OCA\Files\Controller\ViewController;

class Application extends App {
	public function __construct(array $urlParams=array()) {
		parent::__construct('files', $urlParams);
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
				$server->getConfig()
			);
		});

		$container->registerService('ViewController', function (IContainer $c) use ($server) {
			return new ViewController(
				$c->query('AppName'),
				$c->query('Request'),
				$server->getURLGenerator(),
				$server->getNavigationManager(),
				$c->query('L10N'),
				$server->getConfig(),
				$server->getEventDispatcher(),
				$server->getUserSession(),
				$server->getAppManager(),
				$server->getRootFolder()
			);
		});

		/**
		 * Core
		 */
		$container->registerService('L10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N($c->query('AppName'));
		});

		/**
		 * Services
		 */
		$container->registerService('Tagger', function(IContainer $c)  {
			return $c->query('ServerContainer')->getTagManager()->load('files');
		});
		$container->registerService('TagService', function(IContainer $c)  {
			$homeFolder = $c->query('ServerContainer')->getUserFolder();
			return new TagService(
				$c->query('ServerContainer')->getUserSession(),
				$c->query('Tagger'),
				$homeFolder
			);
		});

		/*
		 * Register capabilities
		 */
		$container->registerCapability('OCA\Files\Capabilities');
	}
}
