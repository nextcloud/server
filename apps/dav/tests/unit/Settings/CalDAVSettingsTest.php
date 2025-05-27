<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV\Settings;

use OCA\DAV\Settings\CalDAVSettings;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalDAVSettingsTest extends TestCase {
	private IConfig&MockObject $config;
	private IInitialState&MockObject $initialState;
	private IURLGenerator&MockObject $urlGenerator;
	private IAppManager&MockObject $appManager;
	private CalDAVSettings $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->settings = new CalDAVSettings($this->config, $this->initialState, $this->urlGenerator, $this->appManager);
	}

	public function testGetForm(): void {
		$this->config->method('getAppValue')
			->willReturnMap([
				['dav', 'sendInvitations', 'yes', 'yes'],
				['dav', 'generateBirthdayCalendar', 'yes', 'no'],
				['dav', 'sendEventReminders', 'yes', 'yes'],
				['dav', 'sendEventRemindersToSharedUsers', 'yes', 'yes'],
				['dav', 'sendEventRemindersPush', 'yes', 'yes'],
			]);
		$this->urlGenerator
			->expects($this->once())
			->method('linkToDocs')
			->with('user-sync-calendars')
			->willReturn('Some docs URL');

		$calls = [
			['userSyncCalendarsDocUrl', 'Some docs URL'],
			['sendInvitations', true],
			['generateBirthdayCalendar', false],
			['sendEventReminders', true],
			['sendEventRemindersToSharedUsers', true],
			['sendEventRemindersPush', true],
		];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$result = $this->settings->getForm();

		$this->assertInstanceOf(TemplateResponse::class, $result);
	}

	public function testGetSection(): void {
		$this->appManager->expects(self::once())
			->method('isBackendRequired')
			->with(IAppManager::BACKEND_CALDAV)
			->willReturn(true);
		$this->assertEquals('groupware', $this->settings->getSection());
	}

	public function testGetSectionWithoutCaldavBackend(): void {
		$this->appManager->expects(self::once())
			->method('isBackendRequired')
			->with(IAppManager::BACKEND_CALDAV)
			->willReturn(false);
		$this->assertEquals(null, $this->settings->getSection());
	}

	public function testGetPriority(): void {
		$this->assertEquals(10, $this->settings->getPriority());
	}
}
