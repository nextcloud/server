<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OC\Group\Database;
use OCA\Sharing\RecipientTypes\GroupShareRecipientType;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class GroupShareRecipientTypeTest extends TestCase {
	private IUser $user1;

	private IGroup $group1;

	private IGroup $group2;

	private IGroup $group3;

	private GroupShareRecipientType $recipientType;

	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$this->user1 = $userManager->createUser('user1', 'password');

		$groupManager = Server::get(IGroupManager::class);
		$groupManager->clearBackends();
		$groupManager->addBackend(new Database());

		$this->group1 = $groupManager->createGroup('group1');
		$this->group2 = $groupManager->createGroup('group2');
		$this->group3 = $groupManager->createGroup('group3');

		$this->group1->addUser($this->user1);

		$this->recipientType = new GroupShareRecipientType();
	}

	protected function tearDown(): void {
		$this->user1->delete();
		$this->group1->delete();
		$this->group2->delete();
		$this->group3->delete();

		parent::tearDown();
	}

	public function testSearchRecipients(): void {
		$this->assertEquals(['group1', 'group2', 'group3'], $this->recipientType->searchRecipients('group', 3, 0));
		$this->assertEquals(['group1'], $this->recipientType->searchRecipients('group', 1, 0));
		$this->assertEquals(['group2', 'group3'], $this->recipientType->searchRecipients('group', 3, 1));
		$this->assertEquals(['group2'], $this->recipientType->searchRecipients('group', 1, 1));
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient($this->user1, 'group1'));
		$this->assertFalse($this->recipientType->validateRecipient($this->user1, 'invalid'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals(['group1'], $this->recipientType->getRecipientValues($this->user1));
	}
}
