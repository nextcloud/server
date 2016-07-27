<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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

use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;

require_once __DIR__ . '/../Bootstrap.php';

class IntegrationTestAccessGroupsMatchFilter extends AbstractIntegrationTest {

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		require(__DIR__ . '/../setup-scripts/createExplicitGroups.php');
		require(__DIR__ . '/../setup-scripts/createExplicitGroupsDifferentOU.php');
		parent::init();
	}

	/**
	 * tests whether the group filter works with one specific group, while the
	 * input is the same.
	 *
	 * @return bool
	 */
	protected function case1() {
		$this->connection->setConfiguration(['ldapGroupFilter' => 'cn=RedGroup']);

		$dns = ['cn=RedGroup,ou=Groups,' . $this->base];
		$result = $this->access->groupsMatchFilter($dns);
		return ($dns === $result);
	}

	/**
	 * Tests whether a filter for limited groups is effective when more existing
	 * groups were passed for validation.
	 *
	 * @return bool
	 */
	protected function case2() {
		$this->connection->setConfiguration(['ldapGroupFilter' => '(|(cn=RedGroup)(cn=PurpleGroup))']);

		$dns = [
			'cn=RedGroup,ou=Groups,' . $this->base,
			'cn=BlueGroup,ou=Groups,' . $this->base,
			'cn=PurpleGroup,ou=Groups,' . $this->base
		];
		$result = $this->access->groupsMatchFilter($dns);

		$status =
			count($result) === 2
			&& in_array('cn=RedGroup,ou=Groups,' . $this->base, $result)
			&& in_array('cn=PurpleGroup,ou=Groups,' . $this->base, $result);

		return $status;
	}

	/**
	 * Tests whether a filter for limited groups is effective when more existing
	 * groups were passed for validation.
	 *
	 * @return bool
	 */
	protected function case3() {
		$this->connection->setConfiguration(['ldapGroupFilter' => '(objectclass=groupOfNames)']);

		$dns = [
			'cn=RedGroup,ou=Groups,' . $this->base,
			'cn=PurpleGroup,ou=Groups,' . $this->base,
			'cn=SquaredCircleGroup,ou=SpecialGroups,' . $this->base
		];
		$result = $this->access->groupsMatchFilter($dns);

		$status =
			count($result) === 2
			&& in_array('cn=RedGroup,ou=Groups,' . $this->base, $result)
			&& in_array('cn=PurpleGroup,ou=Groups,' . $this->base, $result);

		return $status;
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
			'ldapBaseGroups' => 'ou=Groups,' . $this->base,
			'ldapUserFilter' => 'objectclass=inetOrgPerson',
			'ldapUserDisplayName' => 'displayName',
			'ldapGroupDisplayName' => 'cn',
			'ldapLoginFilter' => 'uid=%uid',
		]);
	}
}

$test = new IntegrationTestAccessGroupsMatchFilter($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
