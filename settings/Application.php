<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
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

namespace OC\Settings;

use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\AppFramework\Utility\TimeFactory;
use OC\Authentication\Token\IProvider;
use OC\Server;
use OC\Settings\Middleware\SubadminMiddleware;
use OCP\AppFramework\App;
use OCP\IContainer;
use OCP\Settings\IManager;
use OCP\Util;

/**
 * @package OC\Settings
 */
class Application extends App {


	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=[]){
		parent::__construct('settings', $urlParams);

		$container = $this->getContainer();

		// Register Middleware
		$container->registerAlias('SubadminMiddleware', SubadminMiddleware::class);
		$container->registerMiddleWare('SubadminMiddleware');

		/**
		 * Core class wrappers
		 */
		/** FIXME: Remove once OC_User is non-static and mockable */
		$container->registerService('isAdmin', function() {
			return \OC_User::isAdminUser(\OC_User::getUser());
		});
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$container->registerService('isSubAdmin', function(IContainer $c) {
			$userObject = \OC::$server->getUserSession()->getUser();
			$isSubAdmin = false;
			if($userObject !== null) {
				$isSubAdmin = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($userObject);
			}
			return $isSubAdmin;
		});
		$container->registerService('fromMailAddress', function() {
			return Util::getDefaultEmailAddress('no-reply');
		});
		$container->registerService('userCertificateManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getCertificateManager();
		}, false);
		$container->registerService('systemCertificateManager', function (IContainer $c) {
			return $c->query('ServerContainer')->getCertificateManager(null);
		}, false);
		$container->registerService(IProvider::class, function (IContainer $c) {
			return $c->query('ServerContainer')->query(IProvider::class);
		});
		$container->registerService(IManager::class, function (IContainer $c) {
			return $c->query('ServerContainer')->getSettingsManager();
		});
		$container->registerService(AppFetcher::class, function (IContainer $c) {
			/** @var Server $server */
			$server = $c->query('ServerContainer');
			return new AppFetcher(
				$server->getAppDataDir('appstore'),
				$server->getHTTPClientService(),
				$server->query(TimeFactory::class),
				$server->getConfig()
			);
		});
		$container->registerService(CategoryFetcher::class, function (IContainer $c) {
			/** @var Server $server */
			$server = $c->query('ServerContainer');
			return new CategoryFetcher(
				$server->getAppDataDir('appstore'),
				$server->getHTTPClientService(),
				$server->query(TimeFactory::class)
			);
		});
	}
}
