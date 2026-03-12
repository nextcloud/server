<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Plugin;
use Test\TestCase;

class PluginTest extends TestCase {
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = new Plugin();
	}

	public static function linkProvider(): array {
		return [
			[
				'principals/users/MyUserName',
				'calendars/MyUserName',
			],
			[
				'principals/calendar-resources/Resource-ABC',
				'system-calendars/calendar-resources/Resource-ABC',
			],
			[
				'principals/calendar-rooms/Room-ABC',
				'system-calendars/calendar-rooms/Room-ABC',
			],
			// calendar-proxy-write and calendar-proxy-read group principals must
			// resolve to the owner's calendar home so that delegates can discover
			// the delegated calendar home via PROPFIND on the proxy group principal.
			[
				'principals/users/alice/calendar-proxy-write',
				'calendars/alice',
			],
			[
				'principals/users/alice/calendar-proxy-read',
				'calendars/alice',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'linkProvider')]
	public function testGetCalendarHomeForPrincipal(string $input, string $expected): void {
		$this->assertSame($expected, $this->plugin->getCalendarHomeForPrincipal($input));
	}

	public function testGetCalendarHomeForUnknownPrincipal(): void {
		$this->assertNull($this->plugin->getCalendarHomeForPrincipal('FOO/BAR/BLUB'));
	}
}
