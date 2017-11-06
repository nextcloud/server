<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
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

namespace OCA\User_LDAP\Tests\Integration\Lib;

use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\LogWrapper;
use OCA\User_LDAP\User\Manager as LDAPUserManager;
use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_LDAP;
use OCP\Image;

require_once __DIR__ . '/../Bootstrap.php';

class IntegrationTestUserHome extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		parent::init();

		$this->mapping = new UserMapping(\OC::$server->getDatabaseConnection());
		$this->mapping->clear();
		$this->access->setUserMapper($this->mapping);
		$this->backend = new User_LDAP($this->access, \OC::$server->getConfig(), \OC::$server->getNotificationManager(), \OC::$server->getUserSession(), \OC::$server->query('LDAPUserPluginManager'));
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
			'homeFolderNamingRule' => 'homeDirectory',
		]);
	}

	/**
	 * initializes an LDAP user manager instance
	 * @return LDAPUserManager
	 */
	protected function initUserManager() {
		$this->userManager = new LDAPUserManager(
			\OC::$server->getConfig(),
			new FilesystemHelper(),
			new LogWrapper(),
			\OC::$server->getAvatarManager(),
			new Image(),
			\OC::$server->getDatabaseConnection(),
			\OC::$server->getUserManager(),
			\OC::$server->getNotificationManager()
		);
	}

	/**
	 * homeDirectory on LDAP is empty. Return values of getHome should be
	 * identical to user name, following Nextcloud default.
	 *
	 * @return bool
	 */
	protected function case1() {
		\OC::$server->getConfig()->setAppValue('user_ldap', 'enforce_home_folder_naming_rule', false);
		$userManager = \OC::$server->getUserManager();
		$userManager->clearBackends();
		$userManager->registerBackend($this->backend);
		$users = $userManager->search('', 5, 0);

		foreach($users as $user) {
			$home = $user->getHome();
			$uid = $user->getUID();
			$posFound = strpos($home, '/' . $uid);
			$posExpected = strlen($home) - (strlen($uid) + 1);
			if($posFound === false || $posFound !== $posExpected) {
				print('"' . $user->getUID() . '" was not found in "' . $home . '" or does not end with it.' . PHP_EOL);
				return false;
			}
		}

		return true;
	}

	/**
	 * homeDirectory on LDAP is empty. Having the attributes set is enforced.
	 *
	 * @return bool
	 */
	protected function case2() {
		\OC::$server->getConfig()->setAppValue('user_ldap', 'enforce_home_folder_naming_rule', true);
		$userManager = \OC::$server->getUserManager();
		// clearing backends is critical, otherwise the userManager will have
		// the user objects cached and the value from case1 returned
		$userManager->clearBackends();
		$userManager->registerBackend($this->backend);
		$users = $userManager->search('', 5, 0);

		try {
			foreach ($users as $user) {
				$user->getHome();
				print('User home was retrieved without throwing an Exception!' . PHP_EOL);
				return false;
			}
		} catch (\Exception $e) {
			if(strpos($e->getMessage(), 'Home dir attribute') === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * homeDirectory on LDAP is set to "attr:" which is effectively empty.
	 * Return values of getHome should be Nextcloud default.
	 *
	 * @return bool
	 */
	protected function case3() {
		\OC::$server->getConfig()->setAppValue('user_ldap', 'enforce_home_folder_naming_rule', true);
		$this->connection->setConfiguration([
			'homeFolderNamingRule' => 'attr:',
		]);
		$userManager = \OC::$server->getUserManager();
		$userManager->clearBackends();
		$userManager->registerBackend($this->backend);
		$users = $userManager->search('', 5, 0);

		try {
			foreach ($users as $user) {
				$home = $user->getHome();
				$uid = $user->getUID();
				$posFound = strpos($home, '/' . $uid);
				$posExpected = strlen($home) - (strlen($uid) + 1);
				if ($posFound === false || $posFound !== $posExpected) {
					print('"' . $user->getUID() . '" was not found in "' . $home . '" or does not end with it.' . PHP_EOL);
					return false;
				}
			}
		} catch (\Exception $e) {
			print("Unexpected Exception: " . $e->getMessage() . PHP_EOL);
			return false;
		}

		return true;
	}
}

/** @var string $host */
/** @var int $port */
/** @var string $adn */
/** @var string $apwd */
/** @var string $bdn */
$test = new IntegrationTestUserHome($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
