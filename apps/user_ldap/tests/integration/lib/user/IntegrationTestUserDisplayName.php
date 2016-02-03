<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
use OCA\user_ldap\lib\user\User;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\user_ldap\tests\integration\AbstractIntegrationTest;

require_once __DIR__  . '/../../../../../../lib/base.php';

class IntegrationTestUserDisplayName extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../../setup-scripts/createExplicitUsers.php');
		parent::init();
		$this->mapping = new UserMapping(\OC::$server->getDatabaseConnection());
		$this->mapping->clear();
		$this->access->setUserMapper($this->mapping);
		$userBackend  = new OCA\user_ldap\USER_LDAP($this->access, \OC::$server->getConfig());
		\OC_User::useBackend($userBackend);
	}

	/**
	 * adds a map entry for the user, so we know the username
	 *
	 * @param $dn
	 * @param $username
	 */
	private function prepareUser($dn, $username) {
		// assigns our self-picked oc username to the dn
		$this->mapping->map($dn, $username, 'fakeUUID-' . $username);
	}

	/**
	 * tests whether a display name consisting of two parts is created correctly
	 *
	 * @return bool
	 */
	protected function case1() {
		$username = 'alice1337';
		$dn = 'uid=alice,ou=Users,' . $this->base;
		$this->prepareUser($dn, $username);
		$displayName = \OC::$server->getUserManager()->get($username)->getDisplayName();

		return strpos($displayName, '(Alice@example.com)') !== false;
	}

	/**
	 * tests whether a display name consisting of one part is created correctly
	 *
	 * @return bool
	 */
	protected function case2() {
		$this->connection->setConfiguration([
			'ldapUserDisplayName2' => '',
		]);
		$username = 'boris23421';
		$dn = 'uid=boris,ou=Users,' . $this->base;
		$this->prepareUser($dn, $username);
		$displayName = \OC::$server->getUserManager()->get($username)->getDisplayName();

		return strpos($displayName, '(Boris@example.com)') === false;
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
			'ldapUserDisplayName' => 'displayName',
			'ldapUserDisplayName2' => 'mail',
		]);
	}
}

require_once(__DIR__ . '/../../setup-scripts/config.php');
$test = new IntegrationTestUserDisplayName($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
