<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCP\IL10N;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;

class EmailProviderTest extends AbstractNotificationProviderTest {
	public const USER_EMAIL = 'frodo@hobb.it';

	/** @var IMailer|MockObject */
	private $mailer;

	protected function setUp(): void {
		parent::setUp();

		$this->mailer = $this->createMock(IMailer::class);

		$this->provider = new EmailProvider(
			$this->config,
			$this->mailer,
			$this->logger,
			$this->l10nFactory,
			$this->urlGenerator
		);
	}

	public function testSendWithoutAttendees():void {
		[$user1, $user2, $user3, , $user5] = $users = $this->getUsers();
		$principalEmailAddresses = [$user1->getEmailAddress()];

		$enL10N = $this->createMock(IL10N::class);
		$enL10N->method('t')
			->willReturnArgument(0);
		$enL10N->method('l')
			->willReturnArgument(0);

		$deL10N = $this->createMock(IL10N::class);
		$deL10N->method('t')
			->willReturnArgument(0);
		$deL10N->method('l')
			->willReturnArgument(0);

		$this->l10nFactory
			->method('getUserLanguage')
			->willReturnMap([
				[$user1, 'en'],
				[$user2, 'de'],
				[$user3, 'de'],
				[$user5, 'de'],
			]);

		$this->l10nFactory
			->method('findGenericLanguage')
			->willReturn('en');

		$this->l10nFactory
			->method('languageExists')
			->willReturnMap([
				['dav', 'en', true],
				['dav', 'de', true],
			]);

		$this->l10nFactory
			->method('get')
			->willReturnMap([
				['dav', 'en', null, $enL10N],
				['dav', 'de', null, $deL10N],
			]);

		$template1 = $this->getTemplateMock();
		$message11 = $this->getMessageMock('uid1@example.com', $template1);
		$template2 = $this->getTemplateMock();
		$message21 = $this->getMessageMock('uid2@example.com', $template2);
		$message22 = $this->getMessageMock('uid3@example.com', $template2);

		$this->mailer->expects($this->exactly(2))
			->method('createEMailTemplate')
			->with('dav.calendarReminder')
			->willReturnOnConsecutiveCalls(
				$template1,
				$template2
			);

		$this->mailer->expects($this->exactly(4))
			->method('validateMailAddress')
			->withConsecutive(
				['uid1@example.com'],
				['uid2@example.com'],
				['uid3@example.com'],
				['invalid'],
			)
			->willReturnOnConsecutiveCalls(
				true,
				true,
				true,
				false,
			);

		$this->mailer->expects($this->exactly(3))
			->method('createMessage')
			->with()
			->willReturnOnConsecutiveCalls(
				$message11,
				$message21,
				$message22
			);

		$this->mailer->expects($this->exactly(3))
			->method('send')
			->withConsecutive(
				[$message11],
				[$message21],
				[$message22],
			)
			->willReturn([]);

		$this->setupURLGeneratorMock(2);

		$vcalendar = $this->getNoAttendeeVCalendar();
		$this->provider->send($vcalendar->VEVENT, $this->calendarDisplayName, $principalEmailAddresses, $users);
	}

	public function testSendWithAttendeesWhenOwnerIsOrganizer(): void {
		[$user1, $user2, $user3, , $user5] = $users = $this->getUsers();
		$principalEmailAddresses = [$user1->getEmailAddress()];

		$enL10N = $this->createMock(IL10N::class);
		$enL10N->method('t')
			->willReturnArgument(0);
		$enL10N->method('l')
			->willReturnArgument(0);

		$deL10N = $this->createMock(IL10N::class);
		$deL10N->method('t')
			->willReturnArgument(0);
		$deL10N->method('l')
			->willReturnArgument(0);

		$this->l10nFactory
			->method('getUserLanguage')
			->willReturnMap([
				[$user1, 'en'],
				[$user2, 'de'],
				[$user3, 'de'],
				[$user5, 'de'],
			]);

		$this->l10nFactory
			->method('findGenericLanguage')
			->willReturn('en');

		$this->l10nFactory
			->method('languageExists')
			->willReturnMap([
				['dav', 'en', true],
				['dav', 'de', true],
			]);

		$this->l10nFactory
			->method('get')
			->willReturnMap([
				['dav', 'en', null, $enL10N],
				['dav', 'de', null, $deL10N],
			]);

		$template1 = $this->getTemplateMock();
		$message11 = $this->getMessageMock('foo1@example.org', $template1);
		$message12 = $this->getMessageMock('uid2@example.com', $template1);
		$message13 = $this->getMessageMock('uid3@example.com', $template1);
		$template2 = $this->getTemplateMock();
		$message21 = $this->getMessageMock('foo3@example.org', $template2);
		$message22 = $this->getMessageMock('foo4@example.org', $template2);
		$message23 = $this->getMessageMock('uid1@example.com', $template2);

		$this->mailer->expects(self::exactly(2))
			->method('createEMailTemplate')
			->with('dav.calendarReminder')
			->willReturnOnConsecutiveCalls(
				$template1,
				$template2,
			);
		$this->mailer->expects($this->atLeastOnce())
			->method('validateMailAddress')
			->willReturnMap([
				['foo1@example.org', true],
				['foo3@example.org', true],
				['foo4@example.org', true],
				['uid1@example.com', true],
				['uid2@example.com', true],
				['uid3@example.com', true],
				['invalid', false],
			]);
		$this->mailer->expects($this->exactly(6))
			->method('createMessage')
			->with()
			->willReturnOnConsecutiveCalls(
				$message11,
				$message12,
				$message13,
				$message21,
				$message22,
				$message23,
			);
		$this->mailer->expects($this->exactly(6))
			->method('send')
			->withConsecutive(
				[$message11],
				[$message12],
				[$message13],
				[$message21],
				[$message22],
				[$message23],
			)->willReturn([]);
		$this->setupURLGeneratorMock(2);

		$vcalendar = $this->getAttendeeVCalendar();
		$this->provider->send($vcalendar->VEVENT, $this->calendarDisplayName, $principalEmailAddresses, $users);
	}

	public function testSendWithAttendeesWhenOwnerIsAttendee(): void {
		[$user1, $user2, $user3] = $this->getUsers();
		$users = [$user2, $user3];
		$principalEmailAddresses = [$user2->getEmailAddress()];

		$deL10N = $this->createMock(IL10N::class);
		$deL10N->method('t')
			->willReturnArgument(0);
		$deL10N->method('l')
			->willReturnArgument(0);

		$this->l10nFactory
			->method('getUserLanguage')
			->willReturnMap([
				[$user2, 'de'],
				[$user3, 'de'],
			]);

		$this->l10nFactory
			->method('findGenericLanguage')
			->willReturn('en');

		$this->l10nFactory
			->method('languageExists')
			->willReturnMap([
				['dav', 'de', true],
			]);

		$this->l10nFactory
			->method('get')
			->willReturnMap([
				['dav', 'de', null, $deL10N],
			]);

		$template1 = $this->getTemplateMock();
		$message12 = $this->getMessageMock('uid2@example.com', $template1);
		$message13 = $this->getMessageMock('uid3@example.com', $template1);

		$this->mailer->expects(self::once())
			->method('createEMailTemplate')
			->with('dav.calendarReminder')
			->willReturnOnConsecutiveCalls(
				$template1,
			);
		$this->mailer->expects($this->atLeastOnce())
			->method('validateMailAddress')
			->willReturnMap([
				['foo1@example.org', true],
				['foo3@example.org', true],
				['foo4@example.org', true],
				['uid1@example.com', true],
				['uid2@example.com', true],
				['uid3@example.com', true],
				['invalid', false],
			]);
		$this->mailer->expects($this->exactly(2))
			->method('createMessage')
			->with()
			->willReturnOnConsecutiveCalls(
				$message12,
				$message13,
			);
		$this->mailer->expects($this->exactly(2))
			->method('send')
			->withConsecutive(
				[$message12],
				[$message13],
			)->willReturn([]);
		$this->setupURLGeneratorMock(1);

		$vcalendar = $this->getAttendeeVCalendar();
		$this->provider->send($vcalendar->VEVENT, $this->calendarDisplayName, $principalEmailAddresses, $users);
	}

	/**
	 * @return IEMailTemplate
	 */
	private function getTemplateMock():IEMailTemplate {
		$template = $this->createMock(IEMailTemplate::class);

		$template->expects($this->once())
			->method('addHeader')
			->with()
			->willReturn($template);

		$template->expects($this->once())
			->method('setSubject')
			->with()
			->willReturn($template);

		$template->expects($this->once())
			->method('addHeading')
			->with()
			->willReturn($template);

		$template->expects($this->exactly(4))
			->method('addBodyListItem')
			->with()
			->willReturn($template);

		$template->expects($this->once())
			->method('addFooter')
			->with()
			->willReturn($template);

		return $template;
	}

	/**
	 * @param string $toMail
	 * @param IEMailTemplate $templateMock
	 * @param array|null $replyTo
	 * @return IMessage
	 */
	private function getMessageMock(string $toMail, IEMailTemplate $templateMock, ?array $replyTo = null):IMessage {
		$message = $this->createMock(IMessage::class);
		$i = 0;

		$message->expects($this->once())
			->method('setFrom')
			->with([Util::getDefaultEmailAddress('reminders-noreply')])
			->willReturn($message);

		if ($replyTo) {
			$message->expects($this->once())
				->method('setReplyTo')
				->with($replyTo)
				->willReturn($message);
		} else {
			$message->expects($this->never())
				->method('setReplyTo');
		}

		$message->expects($this->once())
			->method('setTo')
			->with([$toMail])
			->willReturn($message);

		$message->expects($this->once())
			->method('useTemplate')
			->with($templateMock)
			->willReturn($message);

		return $message;
	}

	private function getNoAttendeeVCalendar():VCalendar {
		$vcalendar = new VCalendar();
		$vcalendar->add('VEVENT', [
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2017-01-01 00:00:00+00:00'), // 1483228800,
			'UID' => 'uid1234',
			'LOCATION' => 'Location 123',
			'DESCRIPTION' => 'DESCRIPTION 456',
		]);

		return $vcalendar;
	}

	private function getAttendeeVCalendar():VCalendar {
		$vcalendar = new VCalendar();
		$vcalendar->add('VEVENT', [
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2017-01-01 00:00:00+00:00'), // 1483228800,
			'UID' => 'uid1234',
			'LOCATION' => 'Location 123',
			'DESCRIPTION' => 'DESCRIPTION 456',
		]);

		$vcalendar->VEVENT->add(
			'ORGANIZER',
			'mailto:uid1@example.com',
			[
				'LANG' => 'en'
			]
		);

		$vcalendar->VEVENT->add(
			'ATTENDEE',
			'mailto:foo1@example.org',
			[
				'LANG' => 'de',
				'PARTSTAT' => 'NEEDS-ACTION',
			]
		);

		$vcalendar->VEVENT->add(
			'ATTENDEE',
			'mailto:foo2@example.org',
			[
				'LANG' => 'de',
				'PARTSTAT' => 'DECLINED',
			]
		);

		$vcalendar->VEVENT->add(
			'ATTENDEE',
			'mailto:foo3@example.org',
			[
				'LANG' => 'en',
				'PARTSTAT' => 'CONFIRMED',
			]
		);

		$vcalendar->VEVENT->add(
			'ATTENDEE',
			'mailto:foo4@example.org'
		);

		$vcalendar->VEVENT->add(
			'ATTENDEE',
			'tomail:foo5@example.org'
		);

		return $vcalendar;
	}

	private function setupURLGeneratorMock(int $times = 1): void {
		$this->urlGenerator
			->expects($this->exactly($times * 4))
			->method('imagePath')
			->willReturnMap([
				['core', 'actions/info.png', 'imagePath1'],
				['core', 'places/calendar.png', 'imagePath2'],
				['core', 'actions/address.png', 'imagePath3'],
				['core', 'actions/more.png', 'imagePath4'],
			]);
		$this->urlGenerator
			->expects($this->exactly($times * 4))
			->method('getAbsoluteURL')
			->willReturnMap([
				['imagePath1', 'AbsURL1'],
				['imagePath2', 'AbsURL2'],
				['imagePath3', 'AbsURL3'],
				['imagePath4', 'AbsURL4'],
			]);
	}

	private function getUsers(): array {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')
			->willReturn('uid1');
		$user1->method('getEMailAddress')
			->willReturn('uid1@example.com');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')
			->willReturn('uid2');
		$user2->method('getEMailAddress')
			->willReturn('uid2@example.com');
		$user3 = $this->createMock(IUser::class);
		$user3->method('getUID')
			->willReturn('uid3');
		$user3->method('getEMailAddress')
			->willReturn('uid3@example.com');
		$user4 = $this->createMock(IUser::class);
		$user4->method('getUID')
			->willReturn('uid4');
		$user4->method('getEMailAddress')
			->willReturn(null);
		$user5 = $this->createMock(IUser::class);
		$user5->method('getUID')
			->willReturn('uid5');
		$user5->method('getEMailAddress')
			->willReturn('invalid');

		return [$user1, $user2, $user3, $user4, $user5];
	}
}
