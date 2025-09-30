<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OC\User\Database;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class UserShareRecipientTypeTest extends TestCase {
	private IUser $user1;

	private IUser $user2;

	private IUser $user3;

	private UserShareRecipientType $recipientType;

	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$this->user1 = $userManager->createUser('user1', 'password');
		$this->user2 = $userManager->createUser('user2', 'password');
		$this->user3 = $userManager->createUser('user3', 'password');

		$this->recipientType = new UserShareRecipientType();
	}

	protected function tearDown(): void {
		$this->user1->delete();
		$this->user2->delete();
		$this->user3->delete();

		parent::tearDown();
	}

	public function testSearchRecipients(): void {
		$this->assertEquals(['user1', 'user2', 'user3'], $this->recipientType->searchRecipients('user', 3, 0));
		$this->assertEquals(['user1'], $this->recipientType->searchRecipients('user', 1, 0));
		$this->assertEquals(['user2', 'user3'], $this->recipientType->searchRecipients('user', 3, 1));
		$this->assertEquals(['user2'], $this->recipientType->searchRecipients('user', 1, 1));
	}

	public function testValidateRecipient(): void {
		$creator = $this->createMock(IUser::class);
		$creator->method('getUID')->willReturn('creator');

		$this->assertTrue($this->recipientType->validateRecipient($creator, 'user1'));
		$this->assertFalse($this->recipientType->validateRecipient($creator, 'invalid'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals(['user1'], $this->recipientType->getRecipientValues($this->user1));
	}
}
