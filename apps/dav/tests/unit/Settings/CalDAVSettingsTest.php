<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\Unit\DAV\Settings;

use OCA\DAV\Settings\CalDAVSettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\AppFramework\Services\IInitialState;
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

	/** @var CalDAVSettings */
	private CalDAVSettings $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->settings = new CalDAVSettings($this->config, $this->initialState, $this->urlGenerator);
	}

	public function testGetForm() {
		$this->config->method('getAppValue')
		   ->withConsecutive(
			   ['dav', 'sendInvitations', 'yes'],
			   ['dav', 'generateBirthdayCalendar', 'yes'],
			   ['dav', 'sendEventReminders', 'yes'],
			   ['dav', 'sendEventRemindersToSharedGroupMembers', 'yes'],
			   ['dav', 'sendEventRemindersPush', 'no'],
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
				['sendEventRemindersToSharedGroupMembers', true],
				['sendEventRemindersPush', true],
			);
		$result = $this->settings->getForm();

		$this->assertInstanceOf(TemplateResponse::class, $result);
	}

	public function testGetSection() {
		$this->assertEquals('groupware', $this->settings->getSection());
	}

	public function testGetPriority() {
		$this->assertEquals(10, $this->settings->getPriority());
	}
}
