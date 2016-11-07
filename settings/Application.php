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
use OC\Files\View;
use OC\Server;
use OC\Settings\Controller\AppSettingsController;
use OC\Settings\Controller\AuthSettingsController;
use OC\Settings\Controller\CertificateController;
use OC\Settings\Controller\CheckSetupController;
use OC\Settings\Controller\EncryptionController;
use OC\Settings\Controller\GroupsController;
use OC\Settings\Controller\LogSettingsController;
use OC\Settings\Controller\MailSettingsController;
use OC\Settings\Controller\SecuritySettingsController;
use OC\Settings\Controller\UsersController;
use OC\Settings\Middleware\SubadminMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
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
		/**
		 * Controllers
		 */
		$container->registerService('MailSettingsController', function(IContainer $c) {
			return new MailSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N'),
				$c->query('Config'),
				$c->query('UserSession'),
				$c->query('Defaults'),
				$c->query('Mailer'),
				$c->query('DefaultMailAddress')
			);
		});
		$container->registerService('EncryptionController', function(IContainer $c) {
			return new EncryptionController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N'),
				$c->query('Config'),
				$c->query('DatabaseConnection'),
				$c->query('UserManager'),
				new View(),
				$c->query('Logger')
			);
		});
		$container->registerService('AppSettingsController', function(IContainer $c) {
			return new AppSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N'),
				$c->query('Config'),
				$c->query('ICacheFactory'),
				$c->query('INavigationManager'),
				$c->query('IAppManager'),
				$c->query('OcsClient')
			);
		});
		$container->registerService('AuthSettingsController', function(IContainer $c) {
			return new AuthSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ServerContainer')->query('OC\Authentication\Token\IProvider'),
				$c->query('UserManager'),
				$c->query('ServerContainer')->getSession(),
				$c->query('ServerContainer')->getSecureRandom(),
				$c->query('UserId')
			);
		});
		$container->registerService('SecuritySettingsController', function(IContainer $c) {
			return new SecuritySettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Config')
			);
		});
		$container->registerService('AccountManager', function(IAppContainer $c) {
			return new AccountManager($c->getServer()->getDatabaseConnection());
		});
		$container->registerService('CertificateController', function(IContainer $c) {
			return new CertificateController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('CertificateManager'),
				$c->query('SystemCertificateManager'),
				$c->query('L10N'),
				$c->query('IAppManager')
			);
		});
		$container->registerService('GroupsController', function(IContainer $c) {
			return new GroupsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('GroupManager'),
				$c->query('UserSession'),
				$c->query('IsAdmin'),
				$c->query('L10N')
			);
		});
		$container->registerService('UsersController', function(IContainer $c) {
			return new UsersController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserManager'),
				$c->query('GroupManager'),
				$c->query('UserSession'),
				$c->query('Config'),
				$c->query('IsAdmin'),
				$c->query('L10N'),
				$c->query('Logger'),
				$c->query('Defaults'),
				$c->query('Mailer'),
				$c->query('DefaultMailAddress'),
				$c->query('URLGenerator'),
				$c->query('OCP\\App\\IAppManager'),
				$c->query('OCP\\IAvatarManager'),
				$c->query('AccountManager')
			);
		});
		$container->registerService('LogSettingsController', function(IContainer $c) {
			return new LogSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Config'),
				$c->query('L10N')
			);
		});
		$container->registerService('CheckSetupController', function(IContainer $c) {
			return new CheckSetupController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Config'),
				$c->query('ClientService'),
				$c->query('URLGenerator'),
				$c->query('Util'),
				$c->query('L10N'),
				$c->query('Checker')
			);
		});


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
