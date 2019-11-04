<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

namespace OCA\Files_Versions\AppInfo;

use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\Files_Versions\Capabilities;
use OCA\Files_Versions\Hooks;
use OCA\Files_Versions\Listener\LoadAdditionalScripts;
use OCA\Files_Versions\Listener\LoadSidebarScript;
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\Files_Versions\Versions\VersionManager;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSidebar;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\IEventDispatcher;

class Application extends App {

	const appID = 'files_versions';

	public function __construct(array $urlParams = array()) {
		parent::__construct(self::appID, $urlParams);

		$container = $this->getContainer();

		/*
		 * Register capabilities
		 */
		$container->registerCapability(Capabilities::class);

		/*
		 * Register $principalBackend for the DAV collection
		 */
		$container->registerService('principalBackend', function (IAppContainer $c) {
			$server = $c->getServer();
			return new Principal(
				$server->getUserManager(),
				$server->getGroupManager(),
				$server->getShareManager(),
				$server->getUserSession(),
				$server->getAppManager(),
				$server->query(ProxyMapper::class)
			);
		});

		$container->registerService(IVersionManager::class, function(IAppContainer $c) {
			return new VersionManager();
		});

		$this->registerVersionBackends();

		$this->registerHooks();

		$this->registerEvents();
	}

	public function registerVersionBackends() {
		$server = $this->getContainer()->getServer();
		$appManager = $server->getAppManager();
		foreach($appManager->getInstalledApps() as $app) {
			$appInfo = $appManager->getAppInfo($app);
			if (isset($appInfo['versions'])) {
				$backends = $appInfo['versions'];
				foreach($backends as $backend) {
					if (isset($backend['@value'])) {
						$this->loadBackend($backend);
					} else {
						foreach ($backend as $singleBackend) {
							$this->loadBackend($singleBackend);
						}
					}
				}
			}
		}
	}

	private function loadBackend(array $backend) {
		$server = $this->getContainer()->getServer();
		$logger = $server->getLogger();
		/** @var IVersionManager $versionManager */
		$versionManager = $this->getContainer()->getServer()->query(IVersionManager::class);
		$class = $backend['@value'];
		$for = $backend['@attributes']['for'];
		try {
			$backendObject = $server->query($class);
			$versionManager->registerBackend($for, $backendObject);
		} catch (\Exception $e) {
			$logger->logException($e);
		}
	}

	private function registerHooks() {
		Hooks::connectHooks();
	}

	/**
	 * Register events
	 */
	public function registerEvents() {
		$container = $this->getContainer();
		$server = $container->getServer();
		$eventDispatcher = $server->query(IEventDispatcher::class);


		$eventDispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScripts::class);
		$eventDispatcher->addServiceListener(LoadSidebar::class, LoadSidebarScript::class);
	}
}
