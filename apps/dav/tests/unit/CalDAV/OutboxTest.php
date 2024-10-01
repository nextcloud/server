<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Outbox;
use OCP\IConfig;
use Test\TestCase;

class OutboxTest extends TestCase {

	/** @var IConfig */
	private $config;

	/** @var Outbox */
	private $outbox;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->outbox = new Outbox($this->config, 'user-principal-123');
	}

	public function testGetACLFreeBusyEnabled(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'disableFreeBusy', 'no')
			->willReturn('no');

		$this->assertEquals([
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-read',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
		], $this->outbox->getACL());
	}

	public function testGetACLFreeBusyDisabled(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'disableFreeBusy', 'no')
			->willReturn('yes');

		$this->assertEquals([
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-read',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-invite',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-invite',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-reply',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-reply',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
		], $this->outbox->getACL());
	}
}
