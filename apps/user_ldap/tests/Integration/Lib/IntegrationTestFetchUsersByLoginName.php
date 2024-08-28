<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Integration\Lib;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_LDAP;
use OCA\User_LDAP\UserPluginManager;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../Bootstrap.php';

class IntegrationTestFetchUsersByLoginName extends AbstractIntegrationTest {
	/** @var UserMapping */
	protected $mapping;

	/** @var User_LDAP */
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
		$this->backend = new User_LDAP($this->access, \OC::$server->getNotificationManager(), \OC::$server->get(UserPluginManager::class), \OC::$server->get(LoggerInterface::class), \OC::$server->get(DeletedUsersIndex::class));
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

/** @var string $host */
/** @var int $port */
/** @var string $adn */
/** @var string $apwd */
/** @var string $bdn */
$test = new IntegrationTestFetchUsersByLoginName($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
