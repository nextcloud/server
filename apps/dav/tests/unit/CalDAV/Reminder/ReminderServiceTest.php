<?php
/**
 * @copyright Copyright (c) 2019, Thomas Citharel
 *
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder;

use OCA\DAV\CalDAV\Reminder\AbstractNotificationProvider;
use OCA\DAV\CalDAV\Reminder\Backend;
use OCA\DAV\CalDAV\Reminder\NotificationProviderManager;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Test\TestCase;

class ReminderServiceTest extends TestCase {

    /** @var Backend|\PHPUnit\Framework\MockObject\MockObject */
    private $backend;

    /** @var NotificationProviderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationProviderManager;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject*/
	private $groupManager;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;

	public const CALENDAR_DATA = <<<EOD
BEGIN:VCALENDAR
PRODID:-//Nextcloud calendar v1.6.4
BEGIN:VEVENT
CREATED:20160602T133732
DTSTAMP:20160602T133732
LAST-MODIFIED:20160602T133732
UID:wej2z68l9h
SUMMARY:Test Event
LOCATION:Somewhere ...
DESCRIPTION:maybe ....
DTSTART;TZID=Europe/Berlin;VALUE=DATE:20160609
DTEND;TZID=Europe/Berlin;VALUE=DATE:20160610
BEGIN:VALARM
ACTION:EMAIL
TRIGGER:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR
EOD;

    public function setUp() {
		parent::setUp();

        $this->backend = $this->createMock(Backend::class);
        $this->notificationProviderManager = $this->createMock(NotificationProviderManager::class);
        $this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
    }

    public function dataTestProcessReminders(): array
    {
        return [
            [
                [], null
            ],
            [
                [
                    [
                        'calendardata' => self::CALENDAR_DATA,
						'displayname' => 'Personal',
                        'type' => 'EMAIL',
                        'uid' => 1,
                        'id' => 1,
                    ],
                ],
                $this->createMock(EmailProvider::class),
            ],
            [
                [
                    [
                        'calendardata' => self::CALENDAR_DATA,
						'displayname' => 'Personal',
                        'type' => 'DISPLAY',
                        'uid' => 1,
                        'id' => 1,
                    ],
                ],
                $this->createMock(PushProvider::class),
            ]
        ];
    }

	/**
	 * @dataProvider dataTestProcessReminders
	 * @param array $reminders
	 * @param AbstractNotificationProvider|null $notificationProvider
	 * @throws \OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException
	 * @throws \OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException
	 * @throws \OC\User\NoUserException
	 */
    public function testProcessReminders(array $reminders, ?AbstractNotificationProvider $notificationProvider): void
    {
        $user = $this->createMock(IUser::class);

        $this->backend->expects($this->once())->method('getRemindersToProcess')->willReturn($reminders);
        if (count($reminders) > 0) {
            $this->userManager->expects($this->exactly(count($reminders)))->method('get')->willReturn($user);
            $this->backend->expects($this->exactly(count($reminders)))->method('removeReminder');
            $this->notificationProviderManager->expects($this->exactly(count($reminders)))->method('getProvider')->willReturn($notificationProvider);
        }

        $reminderService = new ReminderService($this->backend, $this->notificationProviderManager, $this->userManager, $this->groupManager, $this->userSession);
        $reminderService->processReminders();
    }

	/**
	 * @expectedException OC\User\NoUserException
	 */
    public function testProcessReminderWithBadUser(): void
	{
		$this->backend->expects($this->once())->method('getRemindersToProcess')->willReturn([
			[
				'calendardata' => self::CALENDAR_DATA,
				'type' => 'DISPLAY',
				'uid' => 1,
				'id' => 1,
			]
		]);
		$this->userManager->expects($this->once())->method('get')->with(1)->willReturn(null);
		$reminderService = new ReminderService($this->backend, $this->notificationProviderManager, $this->userManager, $this->groupManager, $this->userSession);
		$reminderService->processReminders();
	}

	public function providesTouchCalendarObject(): array
	{
		return [
			[
				'\OCA\DAV\CalDAV\CalDavBackend::deleteCalendarObject',
				[
					'principaluri' => 'principals/users/personal'
				],
				[],
				[
					'calendarid' => 1,
					'uri' => 'something.ics',
				],
				0
			],
			[
				'\OCA\DAV\CalDAV\CalDavBackend::createCalendarObject',
				[
					'principaluri' => 'principals/users/personal'
				],
				[],
				[
					'calendarid' => 1,
					'uri' => 'something.ics',
					'calendardata' => self::CALENDAR_DATA
				],
				0
			],
			[
				'\OCA\DAV\CalDAV\CalDavBackend::updateCalendarObject',
				[
					'principaluri' => 'principals/users/someone',
					'uri' => 'personal'
				],
				[
					[
						'{http://owncloud.org/ns}principal' => 'principals/users/someone'
					]
				],
				[
					'calendarid' => 1,
					'uri' => 'something.ics',
					'calendardata' => self::CALENDAR_DATA
				],
				0
			],
			[
				'\OCA\DAV\CalDAV\CalDavBackend::createCalendarObject',
				[
					'principaluri' => 'principals/users/someone',
					'uri' => 'personal'
				],
				[
					[
						'{http://owncloud.org/ns}principal' => 'principals/groups/somegroup'
					]
				],
				[
					'calendarid' => 1,
					'uri' => 'something.ics',
					'calendardata' => self::CALENDAR_DATA
				],
				1
			]
		];
	}

	/**
	 * @dataProvider providesTouchCalendarObject
	 * @param string $action
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 * @param int $numberOfGroups
	 * @throws \OC\User\NoUserException
	 * @throws \Sabre\VObject\InvalidDataException
	 */
	public function testOnTouchCalendarObject(string $action, array $calendarData, array $shares, array $objectData, int $numberOfGroups): void
	{
		$this->backend->expects($this->once())->method('cleanRemindersForEvent')->with($objectData['calendarid'], $objectData['uri']);

		if ($action !== '\OCA\DAV\CalDAV\CalDavBackend::deleteCalendarObject') {
			$user = $this->createMock(IUser::class);
			$user->expects($this->once())->method('getUID')->willReturn('user');

			$this->userSession->expects($this->once())->method('getUser')->willReturn($user);
			if ($numberOfGroups === 0) {
				$this->backend->expects($this->exactly(count($shares) + 1))->method('insertReminder');
			} else {
				$group = $this->createMock(IGroup::class);
				$groupUser = $this->createMock(IUser::class);
				$groupUser->expects($this->once())->method('getUID')->willReturn('groupuser');
				$group->expects($this->once())->method('getUsers')->willReturn([$groupUser]);
				$this->groupManager->expects($this->exactly($numberOfGroups))->method('get')->willReturn($group);
			}
		}
		$reminderService = new ReminderService($this->backend, $this->notificationProviderManager, $this->userManager, $this->groupManager, $this->userSession);
		$reminderService->onTouchCalendarObject($action, $calendarData, $shares, $objectData);
	}

	/**
	 * @expectedException OC\User\NoUserException
	 */
	public function testOnTouchCalendarObjectWithNoSession(): void
	{
		$this->backend->expects($this->once())->method('cleanRemindersForEvent');
		$this->userSession->expects($this->once())->method('getUser')->willReturn(null);

		$reminderService = new ReminderService($this->backend, $this->notificationProviderManager, $this->userManager, $this->groupManager, $this->userSession);
		$reminderService->onTouchCalendarObject('', ['principaluri' => 'foo'], [], ['calendarid' => 1, 'uri' => 'bar']);
	}

	public function testOnTouchCalendarObjectWithNoCalendarURI(): void
	{
		$reminderService = new ReminderService($this->backend, $this->notificationProviderManager, $this->userManager, $this->groupManager, $this->userSession);
		$this->assertNull($reminderService->onTouchCalendarObject('', [], [], []));
	}
}
