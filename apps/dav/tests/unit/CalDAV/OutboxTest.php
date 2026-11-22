<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Outbox;
use OCA\DAV\ConfigLexicon;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OutboxTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private Outbox $outbox;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->outbox = new Outbox($this->appConfig, 'user-principal-123');
	}

	public function testGetACLFreeBusyEnabled(): void {
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::DISABLE_FREE_BUSY)
			->willReturn(false);

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
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::DISABLE_FREE_BUSY)
			->willReturn(true);

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
