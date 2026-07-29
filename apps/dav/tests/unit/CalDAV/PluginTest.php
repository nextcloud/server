<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Plugin;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\Server as SabreServer;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
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
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'linkProvider')]
	public function testGetCalendarHomeForPrincipal(string $input, string $expected): void {
		$this->assertSame($expected, $this->plugin->getCalendarHomeForPrincipal($input));
	}

	public function testGetCalendarHomeForUnknownPrincipal(): void {
		$this->assertNull($this->plugin->getCalendarHomeForPrincipal('FOO/BAR/BLUB'));
	}

	private const ICS_ENTOURAGE = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTART:20260715T100000Z\r\nDTEND:20260715T110000Z\r\nSUMMARY:Test\r\nUID:test-uid-123\r\nX-ENTOURAGE_UUID:test-uid-123\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

	private function makePlugin(bool $forgiving): Plugin {
		/** @var IConfig&MockObject $config */
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('dav.forgiving_ical_parser', false)
			->willReturn($forgiving);

		$plugin = new class($config) extends Plugin {
			public function exposeValidateICalendar(string &$data, string $path, bool &$modified, RequestInterface $request, ResponseInterface $response, bool $isNew): void {
				$this->validateICalendar($data, $path, $modified, $request, $response, $isNew);
			}
		};

		/** @var SabreServer&MockObject $server */
		$server = $this->createMock(SabreServer::class);
		$server->method('getProperties')->willReturn([]);
		$server->method('getHTTPPrefer')->willReturn(['handling' => 'lenient']);
		$server->method('emit')->willReturn(true);

		// Inject server without calling initialize() to avoid its side effects on xml/resourceTypeMapping
		$prop = new \ReflectionProperty(\Sabre\CalDAV\Plugin::class, 'server');
		$prop->setValue($plugin, $server);

		return $plugin;
	}

	public function testValidateICalendarRejectsNonStandardPropertyWhenFlagDisabled(): void {
		$plugin = $this->makePlugin(false);
		$data = self::ICS_ENTOURAGE;
		$modified = false;
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$this->expectException(UnsupportedMediaType::class);
		$plugin->exposeValidateICalendar($data, 'calendars/admin/personal/test.ics', $modified, $request, $response, true);
	}

	public function testValidateICalendarAcceptsNonStandardPropertyWhenFlagEnabled(): void {
		$plugin = $this->makePlugin(true);
		$data = self::ICS_ENTOURAGE;
		$modified = false;
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$response->expects($this->once())
			->method('setHeader')
			->with('X-Sabre-Ew-Gross', $this->stringContains('X-ENTOURAGE_UUID'));

		$plugin->exposeValidateICalendar($data, 'calendars/admin/personal/test.ics', $modified, $request, $response, true);
	}
}
