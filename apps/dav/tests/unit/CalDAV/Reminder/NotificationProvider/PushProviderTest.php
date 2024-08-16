<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

class PushProviderTest extends AbstractNotificationProviderTest {
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $manager;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(IManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->provider = new PushProvider(
			$this->config,
			$this->manager,
			$this->logger,
			$this->l10nFactory,
			$this->urlGenerator,
			$this->timeFactory
		);
	}

	public function testNotificationType():void {
		$this->assertEquals(PushProvider::NOTIFICATION_TYPE, 'DISPLAY');
	}

	public function testNotSend(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'sendEventRemindersPush', 'yes')
			->willReturn('no');

		$this->manager->expects($this->never())
			->method('createNotification');
		$this->manager->expects($this->never())
			->method('notify');

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')
			->willReturn('uid1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')
			->willReturn('uid2');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')
			->willReturn('uid3');

		$users = [$user1, $user2, $user3];

		$this->provider->send($this->vcalendar->VEVENT, $this->calendarDisplayName, [], $users);
	}

	public function testSend(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'sendEventRemindersPush', 'yes')
			->willReturn('yes');

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')
			->willReturn('uid1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')
			->willReturn('uid2');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')
			->willReturn('uid3');

		$users = [$user1, $user2, $user3];

		$dateTime = new \DateTime('@946684800');
		$this->timeFactory->method('getDateTime')
			->with()
			->willReturn($dateTime);

		$notification1 = $this->createNotificationMock('uid1', $dateTime);
		$notification2 = $this->createNotificationMock('uid2', $dateTime);
		$notification3 = $this->createNotificationMock('uid3', $dateTime);

		$this->manager->expects($this->exactly(3))
			->method('createNotification')
			->with()
			->willReturnOnConsecutiveCalls(
				$notification1,
				$notification2,
				$notification3
			);

		$this->manager->expects($this->exactly(3))
			->method('notify')
			->withConsecutive(
				[$notification1],
				[$notification2],
				[$notification3],
			);

		$this->provider->send($this->vcalendar->VEVENT, $this->calendarDisplayName, [], $users);
	}

	/**
	 * @param string $uid
	 * @param \DateTime $dt
	 */
	private function createNotificationMock(string $uid, \DateTime $dt):INotification {
		$notification = $this->createMock(INotification::class);
		$notification
			->expects($this->once())
			->method('setApp')
			->with('dav')
			->willReturn($notification);

		$notification->expects($this->once())
			->method('setUser')
			->with($uid)
			->willReturn($notification);

		$notification->expects($this->once())
			->method('setDateTime')
			->with($dt)
			->willReturn($notification);

		$notification->expects($this->once())
			->method('setObject')
			->with('dav', hash('sha256', 'uid1234', false))
			->willReturn($notification);

		$notification->expects($this->once())
			->method('setSubject')
			->with('calendar_reminder', [
				'title' => 'Fellowship meeting',
				'start_atom' => '2017-01-01T00:00:00+00:00',
			])
			->willReturn($notification);

		$notification
			->expects($this->once())
			->method('setMessage')
			->with('calendar_reminder', [
				'title' => 'Fellowship meeting',
				'start_atom' => '2017-01-01T00:00:00+00:00',
				'description' => null,
				'location' => null,
				'all_day' => false,
				'start_is_floating' => false,
				'start_timezone' => 'UTC',
				'end_atom' => '2017-01-01T00:00:00+00:00',
				'end_is_floating' => false,
				'end_timezone' => 'UTC',
				'calendar_displayname' => 'Personal',
			])
			->willReturn($notification);

		return $notification;
	}
}
