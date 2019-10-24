<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\Files_Versions\Versions\VersionManager;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCA\Files_Versions\Capabilities;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_versions', $urlParams);

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
}
