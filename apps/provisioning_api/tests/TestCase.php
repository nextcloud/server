<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Provisioning_API\Tests;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;

abstract class TestCase extends \Test\TestCase {

	/** @var IUser[] */
	protected array $users = [];
	protected IUserManager $userManager;
	protected IGroupManager $groupManager;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = Server::get(IUserManager::class);
		$this->groupManager = Server::get(IGroupManager::class);
		$this->groupManager->createGroup('admin');
	}

	/**
	 * Generates a temp user
	 * @param int $num number of users to generate
	 * @return IUser[]|IUser
	 */
	protected function generateUsers($num = 1) {
		$users = [];
		for ($i = 0; $i < $num; $i++) {
			$user = $this->userManager->createUser($this->getUniqueID(), 'password');
			$this->users[] = $user;
			$users[] = $user;
		}
		$result = count($users) === 1 ? reset($users) : $users;
		$this->assertNotEquals(false, $result);
		return $result;
	}

	protected function tearDown(): void {
		foreach ($this->users as $user) {
			$user->delete();
		}

		$this->groupManager->get('admin')->delete();
		parent::tearDown();
	}
}
