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

namespace OCA\user_ldap\tests\integration;

use OCA\user_ldap\lib\Access;
use OCA\user_ldap\lib\Connection;
use OCA\user_ldap\lib\LDAP;
use OCA\user_ldap\lib\user\Manager;

abstract class AbstractIntegrationTest {
	/** @var  LDAP */
	protected $ldap;

	/** @var  Connection */
	protected $connection;

	/** @var Access */
	protected $access;

	/** @var Manager */
	protected $userManager;

	/** @var  string */
	protected $base;

	/** @var string[] */
	protected $server;

	public function __construct($host, $port, $bind, $pwd, $base) {
		$this->base = $base;
		$this->server = [
			'host' => $host,
			'port' => $port,
			'dn'   => $bind,
			'pwd'  => $pwd
		];
	}

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		$this->initLDAPWrapper();
		$this->initConnection();
		$this->initUserManager();
		$this->initAccess();

	}

	/**
	 * initializes the test LDAP wrapper
	 */
	protected function initLDAPWrapper() {
		$this->ldap = new LDAP();
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		$this->connection = new Connection($this->ldap, '', null);
		$this->connection->setConfiguration([
			'ldapHost' => $this->server['host'],
			'ldapPort' => $this->server['port'],
			'ldapBase' => $this->base,
			'ldapAgentName' => $this->server['dn'],
			'ldapAgentPassword' => $this->server['pwd'],
			'ldapUserFilter' => 'objectclass=inetOrgPerson',
			'ldapUserDisplayName' => 'cn',
			'ldapGroupDisplayName' => 'cn',
			'ldapLoginFilter' => '(|(uid=%uid)(samaccountname=%uid))',
			'ldapCacheTTL' => 0,
			'ldapConfigurationActive' => 1,
		]);
	}

	/**
	 * initializes an LDAP user manager instance
	 * @return Manager
	 */
	protected function initUserManager() {
		$this->userManager = new FakeManager();
	}

	/**
	 * initializes the Access test instance
	 */
	protected function initAccess() {
		$this->access = new Access($this->connection, $this->ldap, $this->userManager);
	}

	/**
	 * runs the test cases while outputting progress and result information
	 *
	 * If a test failed, the script is exited with return code 1.
	 */
	public function run() {
		$methods = get_class_methods($this);
		$atLeastOneCaseRan = false;
		foreach($methods as $method) {
			if(strpos($method, 'case') === 0) {
				print("running $method " . PHP_EOL);
				if(!$this->$method()) {
					print(PHP_EOL . '>>> !!! Test ' . $method . ' FAILED !!! <<<' . PHP_EOL . PHP_EOL);
					exit(1);
				}
				$atLeastOneCaseRan = true;
			}
		}
		if($atLeastOneCaseRan) {
			print('Tests succeeded' . PHP_EOL);
		} else {
			print('No Test was available.' . PHP_EOL);
			exit(1);
		}
	}
}
