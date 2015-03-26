<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OCA\user_ldap\tests;

class Test_Connection extends \Test\TestCase {

	public function testOriginalAgentUnchangedOnClone() {
		//background: upon login a bind is done with the user credentials
		//which is valid for the whole LDAP resource. It needs to be reset
		//to the agent's credentials
		$lw  = $this->getMock('\OCA\user_ldap\lib\ILDAPWrapper');

		$connection = new \OCA\user_ldap\lib\Connection($lw, '', null);
		$agent = array(
			'ldapAgentName' => 'agent',
			'ldapAgentPassword' => '123456',
		);
		$connection->setConfiguration($agent);

		$testConnection = clone $connection;
		$user = array(
			'ldapAgentName' => 'user',
			'ldapAgentPassword' => 'password',
		);
		$testConnection->setConfiguration($user);

		$agentName = $connection->ldapAgentName;
		$agentPawd = $connection->ldapAgentPassword;

		$this->assertSame($agentName, $agent['ldapAgentName']);
		$this->assertSame($agentPawd, $agent['ldapAgentPassword']);
	}

}