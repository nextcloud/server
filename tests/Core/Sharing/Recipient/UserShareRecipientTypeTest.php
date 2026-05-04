<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Tests\Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\User\Database;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class UserShareRecipientTypeTest extends TestCase {
	private IUser $user1;

	private IUser $user2;

	private IUser $user3;

	private IUser $user4;

	private UserShareRecipientType $recipientType;

	private const DISPLAY_NAMES = [
		'user1' => 'User 1',
		'user2' => 'User 2',
		'user3' => 'User 3',
		'user4' => 'User 4',
	];

	private function createUser(IUserManager $userManager, string $uid, string $password): IUser {
		$user = $userManager->createUser($uid, $password);
		$this->assertNotFalse($user);
		$this->assertTrue($user->setDisplayName(self::DISPLAY_NAMES[$uid]));
		return $user;
	}

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$this->user1 = $this->createUser($userManager, 'user1', 'password');
		$this->user2 = $this->createUser($userManager, 'user2', 'password');
		$this->user3 = $this->createUser($userManager, 'user3', 'password');
		$this->user4 = $this->createUser($userManager, 'user4', 'password');

		self::loginAsUser($this->user1->getUID());

		$this->recipientType = new UserShareRecipientType();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();
		$this->user2->delete();
		$this->user3->delete();
		$this->user4->delete();

		parent::tearDown();
	}

	public function testGetDisplayName(): void {
		$this->overwriteService(IL10N::class, Server::get(IFactory::class)->get(''));

		$this->assertEquals('User', $this->recipientType->getDisplayName());
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient($this->user1, 'user2'));
		$this->assertFalse($this->recipientType->validateRecipient($this->user1, 'user1'));
		$this->assertFalse($this->recipientType->validateRecipient($this->user1, 'invalid'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals(['user1'], $this->recipientType->getRecipients($this->user1, null));
	}

	public function testGetRecipientDisplayName(): void {
		$this->assertEquals('User 1', $this->recipientType->getRecipientDisplayName($this->user1->getUID()));
	}

	public function testSearchRecipients(): void {
		$accessContext = new ShareAccessContext();

		/** @psalm-suppress ArgumentTypeCoercion */
		$generateRecipient = static fn (string $userId): ShareRecipient => new ShareRecipient(
			UserShareRecipientType::class,
			$userId,
		);

		// The UserPlugin already removes the current user (user1 here), leading to one result less than requested.
		// This is an issue of the Collaborators API and can't be easily fixed.
		// If the following tests fail, because different numbers of results are returned: congratulations, you fixed the problem!

		$this->assertEquals(array_map($generateRecipient(...), ['user2', 'user3']), $this->recipientType->searchRecipients($accessContext, 'user', 3, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['user2', 'user3', 'user4']), $this->recipientType->searchRecipients($accessContext, 'user', 4, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['user2', 'user3', 'user4']), $this->recipientType->searchRecipients($accessContext, 'user', 4, 1));
		$this->assertEquals(array_map($generateRecipient(...), ['user3', 'user4']), $this->recipientType->searchRecipients($accessContext, 'user', 4, 2));

		$this->assertEquals(array_map($generateRecipient(...), []), $this->recipientType->searchRecipients($accessContext, 'user', 1, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['user2']), $this->recipientType->searchRecipients($accessContext, 'user', 2, 0));
		$this->assertEquals(array_map($generateRecipient(...), ['user2', 'user3']), $this->recipientType->searchRecipients($accessContext, 'user', 2, 1));
		$this->assertEquals(array_map($generateRecipient(...), ['user3', 'user4']), $this->recipientType->searchRecipients($accessContext, 'user', 2, 2));
		$this->assertEquals(array_map($generateRecipient(...), ['user4']), $this->recipientType->searchRecipients($accessContext, 'user', 2, 3));

		$this->assertEquals(array_map($generateRecipient(...), ['user2']), $this->recipientType->searchRecipients($accessContext, 'user2', 1, 0));
	}
}
