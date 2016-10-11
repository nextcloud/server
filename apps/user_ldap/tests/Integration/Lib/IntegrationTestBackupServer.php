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

class IntegrationTestBackupServer extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		parent::initConnection();
		$originalHost = $this->connection->ldapHost;
		$originalPort = $this->connection->ldapPort;
		$this->connection->setConfiguration([
			'ldapHost' => 'qwertz.uiop',
			'ldapPort' => '32123',
			'ldap_backup_host' => $originalHost,
			'ldap_backup_port' => $originalPort,
		]);
	}

	/**
	 * tests that a backup connection is being used when the main  LDAP server
	 * is offline
	 *
	 * Beware: after starting docker, the LDAP host might not be ready yet, thus
	 * causing a false positive. Retry in that case… or increase the sleep time
	 * in run-test.sh
	 *
	 * @return bool
	 */
	protected function case1() {
		try {
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return false;
		}
		return true;
	}

	/**
	 * ensures that an exception is thrown if LDAP main server and LDAP backup
	 * server are not available
	 *
	 * @return bool
	 */
	protected function case2() {
		// reset possible LDAP connection
		$this->initConnection();
		try {
			$this->connection->setConfiguration([
				'ldap_backup_host' => 'qwertz.uiop',
				'ldap_backup_port' => '32123',
			]);
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return true;
		}
		return false;
	}

	/**
	 * ensures that an exception is thrown if main LDAP server is down and a
	 * backup server is not given
	 *
	 * @return bool
	 */
	protected function case3() {
		// reset possible LDAP connection
		$this->initConnection();
		try {
			$this->connection->setConfiguration([
				'ldap_backup_host' => '',
				'ldap_backup_port' => '',
			]);
			$this->connection->getConnectionResource();
		} catch (\OC\ServerNotAvailableException $e) {
			return true;
		}
		return false;
	}
}

$test = new IntegrationTestBackupServer($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
