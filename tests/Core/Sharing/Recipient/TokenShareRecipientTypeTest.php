<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Tests\Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OC\Share\Constants;
use OC\User\Database;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class TokenShareRecipientTypeTest extends TestCase {
	private IUser $owner;

	private TokenShareRecipientType $recipientType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$owner = $userManager->createUser('owner', 'password');
		$this->assertNotFalse($owner);
		$this->owner = $owner;

		$this->recipientType = new TokenShareRecipientType();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->owner->delete();

		parent::tearDown();
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient($this->owner, str_repeat('a', Constants::MIN_TOKEN_LENGTH)));
		$this->assertFalse($this->recipientType->validateRecipient($this->owner, str_repeat('a', Constants::MIN_TOKEN_LENGTH - 1)));

		$this->assertTrue($this->recipientType->validateRecipient($this->owner, str_repeat('a', Constants::MAX_TOKEN_LENGTH)));
		$this->assertFalse($this->recipientType->validateRecipient($this->owner, str_repeat('a', Constants::MAX_TOKEN_LENGTH + 1)));

		$this->assertTrue($this->recipientType->validateRecipient($this->owner, 'a-1b-2'));
	}

	public function testGetRecipientValues(): void {
		$this->assertEquals([], $this->recipientType->getRecipients(null, null));
		$this->assertEquals([], $this->recipientType->getRecipients(null, 1));
		$this->assertEquals([''], $this->recipientType->getRecipients(null, ''));
		$this->assertEquals(['abc'], $this->recipientType->getRecipients(null, 'abc'));
	}
}
