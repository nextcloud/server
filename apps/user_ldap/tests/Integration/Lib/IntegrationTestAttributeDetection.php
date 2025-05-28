<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Integration\Lib;

use OCA\User_LDAP\Group_LDAP;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_LDAP;
use OCA\User_LDAP\UserPluginManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../Bootstrap.php';

class IntegrationTestAttributeDetection extends AbstractIntegrationTest {
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		require(__DIR__ . '/../setup-scripts/createExplicitGroups.php');

		parent::init();

		$this->connection->setConfiguration(['ldapGroupFilter' => 'objectClass=groupOfNames']);
		$this->connection->setConfiguration(['ldapGroupMemberAssocAttr' => 'member']);

		$userMapper = new UserMapping(Server::get(IDBConnection::class));
		$userMapper->clear();
		$this->access->setUserMapper($userMapper);

		$groupMapper = new GroupMapping(Server::get(IDBConnection::class));
		$groupMapper->clear();
		$this->access->setGroupMapper($groupMapper);

		$userBackend = new User_LDAP($this->access, Server::get(\OCP\Notification\IManager::class), Server::get(UserPluginManager::class), Server::get(LoggerInterface::class), Server::get(DeletedUsersIndex::class));
		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend($userBackend);

		$groupBackend = new Group_LDAP($this->access, Server::get(GroupPluginManager::class), Server::get(IConfig::class));
		$groupManger = Server::get(IGroupManager::class);
		$groupManger->clearBackends();
		$groupManger->addBackend($groupBackend);
	}

	protected function caseNativeUUIDAttributeUsers() {
		// trigger importing of users which also triggers UUID attribute detection
		Server::get(IUserManager::class)->search('', 5, 0);
		return $this->connection->ldapUuidUserAttribute === 'entryuuid';
	}

	protected function caseNativeUUIDAttributeGroups() {
		// essentially the same as 'caseNativeUUIDAttributeUsers', code paths
		// are similar, but we take no chances.

		// trigger importing of users which also triggers UUID attribute detection
		Server::get(IGroupManager::class)->search('', 5, 0);
		return $this->connection->ldapUuidGroupAttribute === 'entryuuid';
	}
}

/** @var string $host */
/** @var int $port */
/** @var string $adn */
/** @var string $apwd */
/** @var string $bdn */
$test = new IntegrationTestAttributeDetection($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
