<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
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

namespace OCA\user_ldap\tests\integration\lib;

use OCA\user_ldap\lib\user\Manager as LDAPUserManager;
use OCA\user_ldap\tests\integration\AbstractIntegrationTest;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\user_ldap\USER_LDAP;

require_once __DIR__  . '/../../../../../lib/base.php';

class IntegrationTestUserHome extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var USER_LDAP */
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
		$this->backend = new \OCA\user_ldap\USER_LDAP($this->access, \OC::$server->getConfig());
	}

	/**
	 * tests fetchUserByLoginName where it is expected that the login name does
	 * not match any LDAP user
	 *
	 * @return bool
	 */
	protected function case1() {
		$result = $this->access->fetchUsersByLoginName('nothere');
		return $result === [];
	}

	/**
	 * tests fetchUserByLoginName where it is expected that the login name does
	 * match one LDAP user
	 *
	 * @return bool
	 */
	protected function case2() {
		$result = $this->access->fetchUsersByLoginName('alice');
		return count($result) === 1;
	}

}

require_once(__DIR__ . '/../setup-scripts/config.php');
$test = new IntegrationTestUserHome($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
