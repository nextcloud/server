<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files\Appinfo;

use OC\AppFramework\Utility\SimpleContainer;
use OCA\Files\Controller\ApiController;
use OCP\AppFramework\App;
use \OCA\Files\Service\TagService;
use \OCP\IContainer;

class Application extends App {
	public function __construct(array $urlParams=array()) {
		parent::__construct('files', $urlParams);
		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('APIController', function (IContainer $c) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('TagService')
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
	}
}
