<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OC\User\Database;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class TokenShareRecipientTypeTest extends TestCase {
	private IUser $user1;

	private TokenShareRecipientType $recipientType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$user1 = $userManager->createUser('user1', 'password');
		$this->assertNotFalse($user1);
		$this->user1 = $user1;

		$this->recipientType = new TokenShareRecipientType();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();

		parent::tearDown();
	}

	/** @psalm-suppress DeprecatedMethod The configs are only partly migrated to IAppConfig, so using deprecated IConfig is easier for now. */
	public function testValidateRecipient(): void {
		$config = Server::get(IConfig::class);

		$config->deleteAppValue(Application::APP_ID, 'shareapi_allow_links');

		$this->assertTrue($this->recipientType->validateRecipient($this->user1, str_repeat('a', 32)));
		$this->assertFalse($this->recipientType->validateRecipient($this->user1, str_repeat('a', 32 - 1)));
		$this->assertTrue($this->recipientType->validateRecipient($this->user1, str_repeat('a', 255)));
		$this->assertFalse($this->recipientType->validateRecipient($this->user1, str_repeat('a', 255 + 1)));

		$config->setAppValue(Application::APP_ID, 'shareapi_allow_links', 'no');

		$this->assertFalse($this->recipientType->validateRecipient($this->user1, str_repeat('a', 32)));

		$config->deleteAppValue(Application::APP_ID, 'shareapi_allow_links');
	}
}
