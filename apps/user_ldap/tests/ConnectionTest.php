<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\User_LDAP\Tests;
use OCA\User_LDAP\Connection;

/**
 * Class Test_Connection
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class ConnectionTest extends \Test\TestCase {
	/** @var \OCA\User_LDAP\ILDAPWrapper  */
	protected $ldap;

	/** @var  Connection */
	protected $connection;

	public function setUp() {
		parent::setUp();

		$this->ldap       = $this->getMock('\OCA\User_LDAP\ILDAPWrapper');
		// we use a mock here to replace the cache mechanism, due to missing DI in LDAP backend.
		$this->connection = $this->getMockBuilder('OCA\User_LDAP\Connection')
								 ->setMethods(['getFromCache', 'writeToCache'])
								 ->setConstructorArgs([$this->ldap, '', null])
								 ->getMock();

		$this->ldap->expects($this->any())
			->method('areLDAPFunctionsAvailable')
			->will($this->returnValue(true));
	}

	public function testOriginalAgentUnchangedOnClone() {
		//background: upon login a bind is done with the user credentials
		//which is valid for the whole LDAP resource. It needs to be reset
		//to the agent's credentials
		$lw  = $this->getMock('\OCA\User_LDAP\ILDAPWrapper');

		$connection = new Connection($lw, '', null);
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

	public function testUseBackupServer() {
		$mainHost = 'ldap://nixda.ldap';
		$backupHost = 'ldap://fallback.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapBackupHost' => $backupHost,
			'ldapBackupPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'SuchASecret'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$this->ldap->expects($this->any())
			->method('setOption')
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(3))
			->method('connect')
			->will($this->returnValue('ldapResource'));

		// Not called often enough? Then, the fallback to the backup server is broken.
		$this->connection->expects($this->exactly(4))
			->method('getFromCache')
			->with('overrideMainServer')
			->will($this->onConsecutiveCalls(false, false, true, true));

		$this->connection->expects($this->once())
			->method('writeToCache')
			->with('overrideMainServer', true);

		$isThrown = false;
		$this->ldap->expects($this->exactly(3))
			->method('bind')
			->will($this->returnCallback(function () use (&$isThrown) {
				if(!$isThrown) {
					$isThrown = true;
					throw new \OC\ServerNotAvailableException();
				}
				return true;
			}));

		$this->connection->init();
		$this->connection->resetConnectionResource();
		// with the second init() we test whether caching works
		$this->connection->init();
	}

}
