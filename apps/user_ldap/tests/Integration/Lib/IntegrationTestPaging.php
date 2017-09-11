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

class IntegrationTestPaging extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/** @var int */
	protected $pagingSize = 2;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		parent::init();

		$this->backend = new User_LDAP($this->access, \OC::$server->getConfig(), \OC::$server->getNotificationManager());
	}

	public function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
			'ldapPagingSize' => $this->pagingSize
		]);
	}

	/**
	 * tests that paging works properly against a simple example (reading all
	 * of few users in smallest steps)
	 *
	 * @return bool
	 */
	protected function case1() {
		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];
		$users = [];

		$result = $this->access->searchUsers($filter, $attributes);
		foreach($result as $user) {
			$users[] = $user['cn'];
		}

		if(count($users) === 4) {
			return true;
		}

		return false;
	}

	protected function case2() {
		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];
		$users = [];

		$result = $this->access->searchUsers($filter, $attributes, null, $this->pagingSize);
		foreach($result as $user) {
			$users[] = $user['cn'];
		}

		if(count($users) === 4 - $this->pagingSize) {
			return true;
		}

		return false;
	}
}

/** @var string $host */
/** @var int $port */
/** @var string $adn */
/** @var string $apwd */
/** @var string $bdn */
$test = new IntegrationTestPaging($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
