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

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\user_ldap\tests\integration\AbstractIntegrationTest;

require_once __DIR__  . '/../../../../../lib/base.php';

class IntegrationTestBatchApplyUserAttributes extends AbstractIntegrationTest {
	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		require(__DIR__ . '/../setup-scripts/createUsersWithoutDisplayName.php');
		parent::init();

		$this->mapping = new UserMapping(\OC::$server->getDatabaseConnection());
		$this->mapping->clear();
		$this->access->setUserMapper($this->mapping);
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
				'ldapUserDisplayName' => 'displayname',
		]);
	}

	/**
	 * indirectly tests whether batchApplyUserAttributes does it job properly,
	 * when a user without display name is included in the result set from LDAP.
	 *
	 * @return bool
	 */
	protected function case1() {
		$result = $this->access->fetchListOfUsers('objectclass=person', 'dn');
		// on the original issue, PHP would emit a fatal error
		// – cannot catch it here, but will render the test as unsuccessful
		return is_array($result) && !empty($result);
	}

}

require_once(__DIR__ . '/../setup-scripts/config.php');
$test = new IntegrationTestBatchApplyUserAttributes($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
