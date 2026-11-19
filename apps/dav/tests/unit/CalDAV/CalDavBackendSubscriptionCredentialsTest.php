<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Security\ICrypto;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class CalDavBackendSubscriptionCredentialsTest extends TestCase {
	public static function sourceWithCredentialsProvider(): array {
		return [
			'plain' => ['https://user:pass@example.com/cal.ics', 'https://example.com/cal.ics', 'user', 'pass'],
			'webcal' => ['webcal://user:pass@example.com/cal.ics', 'webcal://example.com/cal.ics', 'user', 'pass'],
			'port, query and fragment' => ['https://user:pass@example.com:8443/cal.ics?a=b@c#frag', 'https://example.com:8443/cal.ics?a=b@c#frag', 'user', 'pass'],
			'percent-encoded' => ['https://us%40er:p%23ss%3Aw%2Frd%25@example.com/cal.ics', 'https://example.com/cal.ics', 'us@er', 'p#ss:w/rd%'],
			'plus is literal' => ['https://user:pa+ss@example.com/cal.ics', 'https://example.com/cal.ics', 'user', 'pa+ss'],
			'utf-8' => ['https://user:p%C3%A4ss@example.com/cal.ics', 'https://example.com/cal.ics', 'user', 'päss'],
			'unencoded colon in password' => ['https://user:pa:ss@example.com/cal.ics', 'https://example.com/cal.ics', 'user', 'pa:ss'],
			'unencoded at sign in username' => ['https://user@domain.tld:pass@example.com/cal.ics', 'https://example.com/cal.ics', 'user@domain.tld', 'pass'],
			'username only' => ['https://user@example.com/cal.ics', 'https://example.com/cal.ics', 'user', ''],
			'password only' => ['https://:pass@example.com/cal.ics', 'https://example.com/cal.ics', '', 'pass'],
		];
	}

	#[DataProvider('sourceWithCredentialsProvider')]
	public function testExtractWithCredentials(string $source, string $expectedSource, string $expectedUsername, string $expectedPassword): void {
		[$strippedSource, $credentials] = CalDavBackend::extractSubscriptionCredentials($source);

		$this->assertSame($expectedSource, $strippedSource);
		$this->assertIsString($credentials);
		$this->assertSame(
			['username' => $expectedUsername, 'password' => $expectedPassword],
			json_decode(Server::get(ICrypto::class)->decrypt($credentials), true),
		);
	}

	public static function sourceWithoutCredentialsProvider(): array {
		return [
			'no user info' => ['https://example.com/cal.ics', 'https://example.com/cal.ics'],
			'at sign in path' => ['https://example.com/user@domain/cal.ics', 'https://example.com/user@domain/cal.ics'],
			'at sign in query' => ['https://example.com/cal.ics?user=a@b', 'https://example.com/cal.ics?user=a@b'],
			'empty user info' => ['https://@example.com/cal.ics', 'https://example.com/cal.ics'],
			'empty username and password' => ['https://:@example.com/cal.ics', 'https://example.com/cal.ics'],
			'no scheme' => ['user:pass@example.com/cal.ics', 'user:pass@example.com/cal.ics'],
		];
	}

	#[DataProvider('sourceWithoutCredentialsProvider')]
	public function testExtractWithoutCredentials(string $source, string $expectedSource): void {
		$this->assertSame([$expectedSource, null], CalDavBackend::extractSubscriptionCredentials($source));
	}
}
