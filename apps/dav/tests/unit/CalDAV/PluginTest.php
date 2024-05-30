<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Plugin;
use Test\TestCase;

class PluginTest extends TestCase {
	/** @var Plugin */
	private $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = new Plugin();
	}

	public function linkProvider() {
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
		];
	}

	/**
	 * @dataProvider linkProvider
	 *
	 * @param $input
	 * @param $expected
	 */
	public function testGetCalendarHomeForPrincipal($input, $expected): void {
		$this->assertSame($expected, $this->plugin->getCalendarHomeForPrincipal($input));
	}

	public function testGetCalendarHomeForUnknownPrincipal(): void {
		$this->assertNull($this->plugin->getCalendarHomeForPrincipal('FOO/BAR/BLUB'));
	}
}
