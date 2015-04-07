<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 3/11/15, 11:03 AM
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
namespace OCA\Encryption\AppInfo;


use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\HookManager;
use OCA\Encryption\Hooks\UserHooks;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\App;
use OCP\AppFramework\IAppContainer;
use OCP\Encryption\IManager;
use OCP\IConfig;


class Application extends \OCP\AppFramework\App {
	/**
	 * @var IManager
	 */
	private $encryptionManager;
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @param $appName
	 * @param array $urlParams
	 */
	public function __construct($urlParams = array()) {
		parent::__construct('encryption', $urlParams);
		$this->encryptionManager = \OC::$server->getEncryptionManager();
		$this->config = \OC::$server->getConfig();
		$this->registerServices();
	}

	/**
	 *
	 */
	public function registerHooks() {
		if (!$this->config->getSystemValue('maintenance', false)) {

			$container = $this->getContainer();
			$server = $container->getServer();
			// Register our hooks and fire them.
			$hookManager = new HookManager();

			$hookManager->registerHook([
				new UserHooks($container->query('KeyManager'),
					$server->getLogger(),
					$container->query('UserSetup'),
					$server->getUserSession(),
					$container->query('Util'),
					new \OCA\Encryption\Session($server->getSession()),
					$container->query('Crypt'),
					$container->query('Recovery'))
			]);

			$hookManager->fireHooks();

		} else {
			// Logout user if we are in maintenance to force re-login
			$this->getContainer()->getServer()->getUserSession()->logout();
		}
	}

	/**
	 *
	 */
	public function registerEncryptionModule() {
		$container = $this->getContainer();
		$container->registerService('EncryptionModule', function (IAppContainer $c) {
			return new \OCA\Encryption\Crypto\Encryption(
				$c->query('Crypt'),
				$c->query('KeyManager'),
				$c->query('Util'));
		});
		$module = $container->query('EncryptionModule');
		$this->encryptionManager->registerEncryptionModule($module);
	}

	/**
	 *
	 */
	public function registerServices() {
		$container = $this->getContainer();

		$container->registerService('Crypt',
			function (IAppContainer $c) {
				$server = $c->getServer();
				return new Crypt($server->getLogger(),
					$server->getUserSession(),
					$server->getConfig());
			});

		$container->registerService('KeyManager',
			function (IAppContainer $c) {
				$server = $c->getServer();

				return new KeyManager($server->getEncryptionKeyStorage(\OCA\Encryption\Crypto\Encryption::ID),
					$c->query('Crypt'),
					$server->getConfig(),
					$server->getUserSession(),
					new \OCA\Encryption\Session($server->getSession()),
					$server->getLogger(),
					$c->query('Util')
				);
			});


		$container->registerService('Recovery',
			function (IAppContainer $c) {
				$server = $c->getServer();

				return new Recovery(
					$server->getUserSession(),
					$c->query('Crypt'),
					$server->getSecureRandom(),
					$c->query('KeyManager'),
					$server->getConfig(),
					$server->getEncryptionKeyStorage(\OCA\Encryption\Crypto\Encryption::ID),
					$server->getEncryptionFilesHelper(),
					new \OC\Files\View());
			});

			$container->registerService('RecoveryController', function (IAppContainer $c) {
			$server = $c->getServer();
			return new \OCA\Encryption\Controller\RecoveryController(
				$c->getAppName(),
				$server->getRequest(),
				$server->getConfig(),
				$server->getL10N($c->getAppName()),
				$c->query('Recovery'));
		});

		$container->registerService('UserSetup',
			function (IAppContainer $c) {
				$server = $c->getServer();
				return new Setup($server->getLogger(),
					$server->getUserSession(),
					$c->query('Crypt'),
					$c->query('KeyManager'));
			});

		$container->registerService('Util',
			function (IAppContainer $c) {
				$server = $c->getServer();

				return new Util(
					new View(),
					$c->query('Crypt'),
					$server->getLogger(),
					$server->getUserSession(),
					$server->getConfig());
			});

	}

	/**
	 *
	 */
	public function registerSettings() {
		// Register settings scripts
		App::registerAdmin('encryption', 'settings/settings-admin');
		App::registerPersonal('encryption', 'settings/settings-personal');
	}
}
