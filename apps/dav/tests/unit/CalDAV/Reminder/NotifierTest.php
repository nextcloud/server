<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Reminder\Notifier;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NotifierTest extends TestCase {
	protected IFactory&MockObject $factory;
	protected IURLGenerator&MockObject $urlGenerator;
	protected IL10N&MockObject $l10n;
	protected ITimeFactory&MockObject $timeFactory;
	protected Notifier $notifier;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				if (!is_array($args)) {
					$args = [$args];
				}
				return vsprintf($string, $args);
			});
		$this->l10n->expects($this->any())
			->method('l')
			->willReturnCallback(function ($string, $args) {
				/** \DateTime $args */
				return $args->format(\DateTime::ATOM);
			});
		$this->l10n->expects($this->any())
			->method('n')
			->willReturnCallback(function ($textSingular, $textPlural, $count, $args) {
				$text = $count === 1 ? $textSingular : $textPlural;
				$text = str_replace('%n', (string)$count, $text);
				return vsprintf($text, $args);
			});
		$this->factory = $this->createMock(IFactory::class);
		$this->factory->expects($this->any())
			->method('get')
			->willReturn($this->l10n);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory
			->method('getDateTime')
			->willReturn(\DateTime::createFromFormat(\DateTime::ATOM, '2005-08-15T14:00:00+02:00'));

		$this->notifier = new Notifier(
			$this->factory,
			$this->urlGenerator,
			$this->timeFactory
		);
	}

	public function testGetId():void {
		$this->assertEquals($this->notifier->getID(), 'dav');
	}

	public function testGetName():void {
		$this->assertEquals($this->notifier->getName(), 'Calendar');
	}


	public function testPrepareWrongApp(): void {
		$this->expectException(UnknownNotificationException::class);
		$this->expectExceptionMessage('Notification not from this app');

		/** @var INotification&MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('notifications');
		$notification->expects($this->never())
			->method('getSubject');

		$this->notifier->prepare($notification, 'en');
	}


	public function testPrepareWrongSubject(): void {
		$this->expectException(UnknownNotificationException::class);
		$this->expectExceptionMessage('Unknown subject');

		/** @var INotification&MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn('wrong subject');

		$this->notifier->prepare($notification, 'en');
	}

	private static function hasPhpDatetimeDiffBug(): bool {
		$d1 = \DateTime::createFromFormat(\DateTimeInterface::ATOM, '2023-11-22T11:52:00+01:00');
		$d2 = new \DateTime('2023-11-22T10:52:03', new \DateTimeZone('UTC'));

		// The difference is 3 seconds, not -1year+11months+…
		return $d1->diff($d2)->y < 0;
	}

	public static function dataPrepare(): array {
		return [
			[
				'calendar_reminder',
				[
					'title' => 'Title of this event',
					'start_atom' => '2005-08-15T15:52:01+02:00'
				],
				self::hasPhpDatetimeDiffBug() ? 'Title of this event' : 'Title of this event (in 1 hour, 52 minutes)',
				[
					'title' => 'Title of this event',
					'description' => null,
					'location' => 'NC Headquarters',
					'all_day' => false,
					'start_atom' => '2005-08-15T15:52:01+02:00',
					'start_is_floating' => false,
					'start_timezone' => 'Europe/Berlin',
					'end_atom' => '2005-08-15T17:52:01+02:00',
					'end_is_floating' => false,
					'end_timezone' => 'Europe/Berlin',
					'calendar_displayname' => 'Personal',
				],
				"Calendar: Personal\r\nDate: 2005-08-15T15:52:01+02:00, 2005-08-15T15:52:01+02:00 - 2005-08-15T17:52:01+02:00 (Europe/Berlin)\r\nWhere: NC Headquarters"
			],
			[
				'calendar_reminder',
				[
					'title' => 'Title of this event',
					'start_atom' => '2005-08-15T13:00:00+02:00',
				],
				self::hasPhpDatetimeDiffBug() ? 'Title of this event' : 'Title of this event (1 hour ago)',
				[
					'title' => 'Title of this event',
					'description' => null,
					'location' => 'NC Headquarters',
					'all_day' => false,
					'start_atom' => '2005-08-15T13:00:00+02:00',
					'start_is_floating' => false,
					'start_timezone' => 'Europe/Berlin',
					'end_atom' => '2005-08-15T15:00:00+02:00',
					'end_is_floating' => false,
					'end_timezone' => 'Europe/Berlin',
					'calendar_displayname' => 'Personal',
				],
				"Calendar: Personal\r\nDate: 2005-08-15T13:00:00+02:00, 2005-08-15T13:00:00+02:00 - 2005-08-15T15:00:00+02:00 (Europe/Berlin)\r\nWhere: NC Headquarters"
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataPrepare')]
	public function testPrepare(string $subjectType, array $subjectParams, string $subject, array $messageParams, string $message): void {
		/** @var INotification&MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn($subjectType);
		$notification->expects($this->once())
			->method('getSubjectParameters')
			->willReturn($subjectParams);
		$notification->expects($this->once())
			->method('getMessageParameters')
			->willReturn($messageParams);

		$notification->expects($this->once())
			->method('setParsedSubject')
			->with($subject)
			->willReturnSelf();

		$notification->expects($this->once())
			->method('setParsedMessage')
			->with($message)
			->willReturnSelf();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'places/calendar.svg')
			->willReturn('icon-url');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('icon-url')
			->willReturn('absolute-icon-url');
		$notification->expects($this->once())
			->method('setIcon')
			->with('absolute-icon-url')
			->willReturnSelf();

		$return = $this->notifier->prepare($notification, 'en');

		$this->assertEquals($notification, $return);
	}

	public function testPassedEvent(): void {
		/** @var INotification&MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn('calendar_reminder');
		$notification->expects($this->once())
			->method('getSubjectParameters')
			->willReturn([
				'title' => 'Title of this event',
				'start_atom' => '2005-08-15T08:00:00+02:00'
			]);

		$notification->expects($this->once())
			->method('getMessageParameters')
			->willReturn([
				'title' => 'Title of this event',
				'description' => null,
				'location' => 'NC Headquarters',
				'all_day' => false,
				'start_atom' => '2005-08-15T08:00:00+02:00',
				'start_is_floating' => false,
				'start_timezone' => 'Europe/Berlin',
				'end_atom' => '2005-08-15T13:00:00+02:00',
				'end_is_floating' => false,
				'end_timezone' => 'Europe/Berlin',
				'calendar_displayname' => 'Personal',
			]);

		$notification->expects($this->once())
			->method('setParsedSubject')
			->with(self::hasPhpDatetimeDiffBug() ? 'Title of this event' : 'Title of this event (6 hours ago)')
			->willReturnSelf();

		$this->expectException(AlreadyProcessedException::class);

		$return = $this->notifier->prepare($notification, 'en');

		$this->assertEquals($notification, $return);
	}
}
