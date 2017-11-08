<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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


namespace OCA\FederatedFileSharing\AppInfo;


use OC\AppFramework\Utility\SimpleContainer;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\Controller\RequestHandlerController;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCP\AppFramework\App;
use OCP\GlobalScale\IConfig;

class Application extends App {

	/** @var FederatedShareProvider */
	protected $federatedShareProvider;

	public function __construct() {
		parent::__construct('federatedfilesharing');

		$container = $this->getContainer();
		$server = $container->getServer();

		$container->registerService('RequestHandlerController', function(SimpleContainer $c) use ($server) {
			$addressHandler = new AddressHandler(
				$server->getURLGenerator(),
				$server->getL10N('federatedfilesharing'),
				$server->getCloudIdManager()
			);
			$notification = new Notifications(
				$addressHandler,
				$server->getHTTPClientService(),
				$server->query(\OCP\OCS\IDiscoveryService::class),
				\OC::$server->getJobList()
			);
			return new RequestHandlerController(
				$c->query('AppName'),
				$server->getRequest(),
				$this->getFederatedShareProvider(),
				$server->getDatabaseConnection(),
				$server->getShareManager(),
				$notification,
				$addressHandler,
				$server->getUserManager(),
				$server->getCloudIdManager()
			);
		});
	}

	/**
	 * get instance of federated share provider
	 *
	 * @return FederatedShareProvider
	 */
	public function getFederatedShareProvider() {
		if ($this->federatedShareProvider === null) {
			$this->initFederatedShareProvider();
		}
		return $this->federatedShareProvider;
	}

	/**
	 * initialize federated share provider
	 */
	protected function initFederatedShareProvider() {
		$c = $this->getContainer();
		$addressHandler = new \OCA\FederatedFileSharing\AddressHandler(
			\OC::$server->getURLGenerator(),
			\OC::$server->getL10N('federatedfilesharing'),
			\OC::$server->getCloudIdManager()
		);
		$notifications = new \OCA\FederatedFileSharing\Notifications(
			$addressHandler,
			\OC::$server->getHTTPClientService(),
			\OC::$server->query(\OCP\OCS\IDiscoveryService::class),
			\OC::$server->getJobList()
		);
		$tokenHandler = new \OCA\FederatedFileSharing\TokenHandler(
			\OC::$server->getSecureRandom()
		);

		$this->federatedShareProvider = new \OCA\FederatedFileSharing\FederatedShareProvider(
			\OC::$server->getDatabaseConnection(),
			$addressHandler,
			$notifications,
			$tokenHandler,
			\OC::$server->getL10N('federatedfilesharing'),
			\OC::$server->getLogger(),
			\OC::$server->getLazyRootFolder(),
			\OC::$server->getConfig(),
			\OC::$server->getUserManager(),
			\OC::$server->getCloudIdManager(),
			$c->query(IConfig::class)
		);
	}

}
