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

class Application extends App {
	public function __construct(array $urlParams=array()) {
		parent::__construct('files', $urlParams);
		$container = $this->getContainer();


		/**
		 * Controllers
		 */
		$container->registerService('APIController', function (SimpleContainer $c) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request')
			);
		});
	}
}
