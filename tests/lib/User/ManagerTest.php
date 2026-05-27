<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Test\TestCase;

#[Group('DB')]
class ManagerTest extends TestCase {
	public function testCountUsersOnlyDisabled(): void {
		$manager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$countBefore = $manager->countDisabledUsers();

		//Add test users
		$user1 = $manager->createUser('testdisabledcount1', 'testdisabledcount1');

		$user2 = $manager->createUser('testdisabledcount2', 'testdisabledcount2');
		$user2->setEnabled(false);

		$user3 = $manager->createUser('testdisabledcount3', 'testdisabledcount3');

		$user4 = $manager->createUser('testdisabledcount4', 'testdisabledcount4');
		$user4->setEnabled(false);

		$this->assertEquals($countBefore + 2, $manager->countDisabledUsers());

		//cleanup
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
	}

	public function testCountUsersOnlySeen(): void {
		$manager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$countBefore = $manager->countSeenUsers();

		//Add test users
		$user1 = $manager->createUser('testseencount1', 'testseencount1');
		$user1->updateLastLoginTimestamp();

		$user2 = $manager->createUser('testseencount2', 'testseencount2');
		$user2->updateLastLoginTimestamp();

		$user3 = $manager->createUser('testseencount3', 'testseencount3');

		$user4 = $manager->createUser('testseencount4', 'testseencount4');
		$user4->updateLastLoginTimestamp();

		$this->assertEquals($countBefore + 3, $manager->countSeenUsers());

		//cleanup
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
	}

	public function testCallForSeenUsers(): void {
		$manager = Server::get(IUserManager::class);
		// count other users in the db before adding our own
		$count = 0;
		$function = function (IUser $user) use (&$count): void {
			$count++;
		};
		$manager->callForAllUsers($function, '', true);
		$countBefore = $count;

		//Add test users
		$user1 = $manager->createUser('testseen1', 'testseen10');
		$user1->updateLastLoginTimestamp();

		$user2 = $manager->createUser('testseen2', 'testseen20');
		$user2->updateLastLoginTimestamp();

		$user3 = $manager->createUser('testseen3', 'testseen30');

		$user4 = $manager->createUser('testseen4', 'testseen40');
		$user4->updateLastLoginTimestamp();

		$count = 0;
		$manager->callForAllUsers($function, '', true);

		$this->assertEquals($countBefore + 3, $count);

		//cleanup
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(enabled: false)]
	public function testRecentlyActive(): void {
		$config = Server::get(IConfig::class);
		$manager = Server::get(IUserManager::class);

		// Create some users
		$now = (string)time();
		$user1 = $manager->createUser('test_active_1', 'test_active_1');
		$config->setUserValue('test_active_1', 'login', 'lastLogin', $now);
		$user1->setDisplayName('test active 1');
		$user1->setSystemEMailAddress('roger@active.com');

		$user2 = $manager->createUser('TEST_ACTIVE_2_FRED', 'TEST_ACTIVE_2');
		$config->setUserValue('TEST_ACTIVE_2_FRED', 'login', 'lastLogin', $now);
		$user2->setDisplayName('TEST ACTIVE 2 UPPER');
		$user2->setSystemEMailAddress('Fred@Active.Com');

		$user3 = $manager->createUser('test_active_3', 'test_active_3');
		$config->setUserValue('test_active_3', 'login', 'lastLogin', $now + 1);
		$user3->setDisplayName('test active 3');

		$user4 = $manager->createUser('test_active_4', 'test_active_4');
		$config->setUserValue('test_active_4', 'login', 'lastLogin', $now);
		$user4->setDisplayName('Test Active 4');

		$user5 = $manager->createUser('test_inactive_1', 'test_inactive_1');
		$user5->setDisplayName('Test Inactive 1');
		$user2->setSystemEMailAddress('jeanne@Active.Com');

		// Search recently active
		//  - No search, case-insensitive order
		$users = $manager->getLastLoggedInUsers(4);
		$this->assertEquals(['test_active_3', 'test_active_1', 'TEST_ACTIVE_2_FRED', 'test_active_4'], $users);
		//  - Search, case-insensitive order
		$users = $manager->getLastLoggedInUsers(search: 'act');
		$this->assertEquals(['test_active_3', 'test_active_1', 'TEST_ACTIVE_2_FRED', 'test_active_4'], $users);
		//  - No search with offset
		$users = $manager->getLastLoggedInUsers(2, 2);
		$this->assertEquals(['TEST_ACTIVE_2_FRED', 'test_active_4'], $users);
		//  - Case insensitive search (email)
		$users = $manager->getLastLoggedInUsers(search: 'active.com');
		$this->assertEquals(['test_active_1', 'TEST_ACTIVE_2_FRED'], $users);
		//  - Case insensitive search (display name)
		$users = $manager->getLastLoggedInUsers(search: 'upper');
		$this->assertEquals(['TEST_ACTIVE_2_FRED'], $users);
		//  - Case insensitive search (uid)
		$users = $manager->getLastLoggedInUsers(search: 'fred');
		$this->assertEquals(['TEST_ACTIVE_2_FRED'], $users);

		// Delete users and config keys
		$user1->delete();
		$user2->delete();
		$user3->delete();
		$user4->delete();
		$user5->delete();
	}
}
