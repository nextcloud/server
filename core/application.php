<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace OC\Core;

use OC\AppFramework\Utility\SimpleContainer;
use \OCP\AppFramework\App;
use OC\Core\LostPassword\Controller\LostController;
use OC\Core\User\UserController;
use OC\Core\Avatar\AvatarController;
use \OCP\Util;

/**
 * Class Application
 *
 * @package OC\Core
 */
class Application extends App {

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=array()){
		parent::__construct('core', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('LostController', function(SimpleContainer $c) {
			return new LostController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('URLGenerator'),
				$c->query('UserManager'),
				$c->query('Defaults'),
				$c->query('L10N'),
				$c->query('Config'),
				$c->query('SecureRandom'),
				$c->query('DefaultEmailAddress'),
				$c->query('IsEncryptionEnabled'),
				$c->query('Mailer')
			);
		});
		$container->registerService('UserController', function(SimpleContainer $c) {
			return new UserController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserManager'),
				$c->query('Defaults')
			);
		});
		$container->registerService('AvatarController', function(SimpleContainer $c) {
			return new AvatarController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('AvatarManager'),
				$c->query('Cache'),
				$c->query('L10N'),
				$c->query('UserManager'),
				$c->query('UserSession')
			);
		});

		/**
		 * Core class wrappers
		 */
		$container->registerService('IsEncryptionEnabled', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getEncryptionManager()->isEnabled();
		});
		$container->registerService('URLGenerator', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getURLGenerator();
		});
		$container->registerService('UserManager', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getUserManager();
		});
		$container->registerService('Config', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getConfig();
		});
		$container->registerService('L10N', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getL10N('core');
		});
		$container->registerService('SecureRandom', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getSecureRandom();
		});
		$container->registerService('AvatarManager', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getAvatarManager();
		});
		$container->registerService('UserSession', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getUserSession();
		});
		$container->registerService('Cache', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getCache();
		});


		$container->registerService('Defaults', function() {
			return new \OC_Defaults;
		});
		$container->registerService('Mailer', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getMailer();
		});
		$container->registerService('DefaultEmailAddress', function() {
			return Util::getDefaultEmailAddress('lostpassword-noreply');
		});
	}

}
