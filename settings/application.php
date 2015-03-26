<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OC\Settings\Controller\AppSettingsController;
use OC\Settings\Controller\GroupsController;
use OC\Settings\Controller\LogSettingsController;
use OC\Settings\Controller\MailSettingsController;
use OC\Settings\Controller\SecuritySettingsController;
use OC\Settings\Controller\UsersController;
use OC\Settings\Factory\SubAdminFactory;
use OC\Settings\Middleware\SubadminMiddleware;
use \OCP\AppFramework\App;
use OCP\IContainer;
use \OCP\Util;

/**
 * @package OC\Settings
 */
class Application extends App {


	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=array()){
		parent::__construct('settings', $urlParams);

		$container = $this->getContainer();

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
		$container->registerService('AppSettingsController', function(IContainer $c) {
			return new AppSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N'),
				$c->query('Config'),
				$c->query('ICacheFactory')
			);
		});
		$container->registerService('SecuritySettingsController', function(IContainer $c) {
			return new SecuritySettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Config')
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
				$c->query('SubAdminFactory')
			);
		});
		$container->registerService('LogSettingsController', function(IContainer $c) {
			return new LogSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Config'),
				$c->query('L10N'),
				$c->query('TimeFactory')
			);
		});

		/**
		 * Middleware
		 */
		$container->registerService('SubadminMiddleware', function(IContainer $c){
			return new SubadminMiddleware(
				$c->query('ControllerMethodReflector'),
				$c->query('IsSubAdmin')
			);
		});
		// Execute middlewares
		$container->registerMiddleware('SubadminMiddleware');

		/**
		 * Core class wrappers
		 */
		$container->registerService('Config', function(IContainer $c) {
			return $c->query('ServerContainer')->getConfig();
		});
		$container->registerService('ICacheFactory', function(IContainer $c) {
			return $c->query('ServerContainer')->getMemCacheFactory();
		});
		$container->registerService('L10N', function(IContainer $c) {
			return $c->query('ServerContainer')->getL10N('settings');
		});
		$container->registerService('GroupManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getGroupManager();
		});
		$container->registerService('UserManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getUserManager();
		});
		$container->registerService('UserSession', function(IContainer $c) {
			return $c->query('ServerContainer')->getUserSession();
		});
		/** FIXME: Remove once OC_User is non-static and mockable */
		$container->registerService('IsAdmin', function(IContainer $c) {
			return \OC_User::isAdminUser(\OC_User::getUser());
		});
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$container->registerService('IsSubAdmin', function(IContainer $c) {
			return \OC_Subadmin::isSubAdmin(\OC_User::getUser());
		});
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$container->registerService('SubAdminFactory', function(IContainer $c) {
			return new SubAdminFactory();
		});
		$container->registerService('Mailer', function(IContainer $c) {
			return $c->query('ServerContainer')->getMailer();
		});
		$container->registerService('Defaults', function(IContainer $c) {
			return new \OC_Defaults;
		});
		$container->registerService('DefaultMailAddress', function(IContainer $c) {
			return Util::getDefaultEmailAddress('no-reply');
		});
		$container->registerService('Logger', function(IContainer $c) {
			return $c->query('ServerContainer')->getLogger();
		});
		$container->registerService('URLGenerator', function(IContainer $c) {
			return $c->query('ServerContainer')->getURLGenerator();
		});
	}
}
