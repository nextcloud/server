<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\Unit\DAV\Settings;

use OCA\DAV\Settings\CalDAVSettings;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalDAVSettingsTest extends TestCase {

	/** @var IConfig|MockObject */
	private $config;

	/** @var IInitialState|MockObject */
	private $initialState;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IAppManager|MockObject */
	private $appManager;

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
			->withConsecutive(
				['dav', 'sendInvitations', 'yes'],
				['dav', 'generateBirthdayCalendar', 'yes'],
				['dav', 'sendEventReminders', 'yes'],
				['dav', 'sendEventRemindersToSharedUsers', 'yes'],
				['dav', 'sendEventRemindersPush', 'yes'],
			)
			->will($this->onConsecutiveCalls('yes', 'no', 'yes', 'yes', 'yes'));
		$this->urlGenerator
			->expects($this->once())
			->method('linkToDocs')
			->with('user-sync-calendars')
			->willReturn('Some docs URL');
		$this->initialState->method('provideInitialState')
			->withConsecutive(
				['userSyncCalendarsDocUrl', 'Some docs URL'],
				['sendInvitations', true],
				['generateBirthdayCalendar', false],
				['sendEventReminders', true],
				['sendEventRemindersToSharedUsers', true],
				['sendEventRemindersPush', true],
			);
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
