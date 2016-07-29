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
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_LDAP;

require_once __DIR__ . '/../Bootstrap.php';

class IntegrationTestConnect extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/** @var  string */
	protected $host;

	/** @var  int */
	protected $port;

	public function __construct($host, $port, $bind, $pwd, $base) {
		// make sure host is a simple host name
		if(strpos($host, '://') !== false) {
			$host = substr_replace($host, '', 0, strpos($host, '://') + 3);
		}
		if(strpos($host, ':') !== false) {
			$host = substr_replace($host, '', strpos($host, ':'));
		}
		$this->host = $host;
		$this->port = $port;
		parent::__construct($host, $port, $bind, $pwd, $base);
	}

	/**
	 * test that a faulty host will does not connect successfully
	 *
	 * @return bool
	 */
	protected function case1() {
		// reset possible LDAP connection
		$this->initConnection();
		$this->connection->setConfiguration([
			'ldapHost' => 'qwertz.uiop',
		]);
		try {
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return true;
		}
		return false;
	}

	/**
	 * tests that a connect succeeds when only a hostname is provided
	 *
	 * @return bool
	 */
	protected function case2() {
		// reset possible LDAP connection
		$this->initConnection();
		$this->connection->setConfiguration([
				'ldapHost' => $this->host,
		]);
		try {
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return false;
		}
		return true;
	}

	/**
	 * tests that a connect succeeds when an LDAP URL is provided
	 *
	 * @return bool
	 */
	protected function case3() {
		// reset possible LDAP connection
		$this->initConnection();
		$this->connection->setConfiguration([
				'ldapHost' => 'ldap://' . $this->host,
		]);
		try {
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return false;
		}
		return true;
	}

	/**
	 * tests that a connect succeeds when an LDAP URL with port is provided
	 *
	 * @return bool
	 */
	protected function case4() {
		// reset possible LDAP connection
		$this->initConnection();
		$this->connection->setConfiguration([
				'ldapHost' => 'ldap://' . $this->host  . ':' . $this->port,
		]);
		try {
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return false;
		}
		return true;
	}

	/**
	 * tests that a connect succeeds when a hostname with port is provided
	 *
	 * @return bool
	 */
	protected function case5() {
		// reset possible LDAP connection
		$this->initConnection();
		$this->connection->setConfiguration([
				'ldapHost' => $this->host  . ':' . $this->port,
		]);
		try {
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return false;
		}
		return true;
	}

	/**
	 * repeat case1, only to make sure that not a connection was reused by
	 * accident.
	 *
	 * @return bool
	 */
	protected function case6() {
		return $this->case1();
	}
}

$test = new IntegrationTestConnect($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
