<?php

namespace OCA\Files_Sharing\App;

use \OCP\AppFramework\App;
use \OCA\Files_Sharing\Controller\AdminSettingsController;

class Sharing extends App {

	public function __construct(array $urlParams=array()){
		parent::__construct('files_sharing', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('AdminSettingsController', function($c) {
			return new AdminSettingsController(
				$c->query('AppName'),
				$c->query('Request')
			);
		});
	}
}
