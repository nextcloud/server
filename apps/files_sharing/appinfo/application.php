<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Appinfo;

use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\Propagation\PropagationManager;
use OCP\AppFramework\App;
use \OCP\IContainer;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_sharing', $urlParams);
		$container = $this->getContainer();

		$container->registerService('MountProvider', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');
			return new MountProvider(
				$server->getConfig(),
				$c->query('PropagationManager')
			);
		});

		$container->registerService('PropagationManager', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');
			return new PropagationManager(
				$server->getUserSession(),
				$server->getConfig()
			);
		});
	}

	public function registerMountProviders() {
		/** @var \OCP\IServerContainer $server */
		$server = $this->getContainer()->query('ServerContainer');
		$mountProviderCollection = $server->getMountProviderCollection();
		$mountProviderCollection->registerProvider($this->getContainer()->query('MountProvider'));
	}

	public function setupPropagation() {
		$propagationManager = $this->getContainer()->query('PropagationManager');
		\OCP\Util::connectHook('OC_Filesystem', 'setup', $propagationManager, 'globalSetup');
	}
}
