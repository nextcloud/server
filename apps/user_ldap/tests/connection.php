<?php
/**
* ownCloud
*
* @author Arthur Schiwon
* @copyright 2013 Arthur Schiwon blizzz@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
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