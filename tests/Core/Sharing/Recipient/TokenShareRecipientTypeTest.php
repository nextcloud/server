<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Sharing\Recipient;

use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class TokenShareRecipientTypeTest extends TestCase {
	private TokenShareRecipientType $recipientType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->recipientType = Server::get(TokenShareRecipientType::class);
	}

	public function testValidateRecipient(): void {
		$this->assertTrue($this->recipientType->validateRecipient(str_repeat('a', 32)));
		$this->assertFalse($this->recipientType->validateRecipient(str_repeat('a', 32 - 1)));
		$this->assertTrue($this->recipientType->validateRecipient(str_repeat('a', 255)));
		$this->assertFalse($this->recipientType->validateRecipient(str_repeat('a', 255 + 1)));
	}
}
