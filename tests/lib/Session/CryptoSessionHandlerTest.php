<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Tests\Session;

use OC\Session\CryptoSessionHandler;
use Test\TestCase;

/**
 * @covers \OC\Session\CryptoSessionHandler
 */
class CryptoSessionHandlerTest extends TestCase {

	public function testParseIdWithPassphrase(): void {
		[$sessionId, $passphrase] = CryptoSessionHandler::parseId('abc|def');

		self::assertEquals('abc', $sessionId);
		self::assertEquals('def', $passphrase);
	}

	public function testParseIdWithoutPassphrase(): void {
		[$sessionId, $passphrase] = CryptoSessionHandler::parseId('abc');

		self::assertEquals('abc', $sessionId);
		self::assertNull($passphrase);
	}
}
