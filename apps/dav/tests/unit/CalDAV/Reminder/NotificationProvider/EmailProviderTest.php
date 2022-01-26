<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Thomas Citharel
 * @copyright Copyright (c) 2019, Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use DateTime;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;

class EmailProviderTest extends AbstractNotificationProviderTest {
	public const USER_EMAIL = 'frodo@hobb.it';

	/** @var LoggerInterface|MockObject */
	protected $logger;

	/** @var L10NFactory|MockObject */
	protected $l10nFactory;

	/** @var IL10N|MockObject */
	protected $l10n;

	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;

	/** @var IConfig|MockObject */
	protected $config;

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
			->willReturnOnConsecutiveCalls($template1, $template2);

		$this->mailer->expects($this->exactly(4))
			->method('validateMailAddress')
			->withConsecutive(
				['uid1@example.com'],
				['uid2@example.com'],
				['uid3@example.com'],
				['invalid']
			)
			->willReturnOnConsecutiveCalls(true, true, true, false);

		$this->mailer->expects($this->exactly(3))
			->method('createMessage')
			->with()
			->willReturnOnConsecutiveCalls($message11, $message21, $message22);
		$this->mailer->expects($this->exactly(3))
			->method('send')
			->withConsecutive(
				[$message11],
				[$message21],
				[$message22]
			)
			->willReturn([]);

		$this->setupURLGeneratorMock(2);

		$vcalendar = $this->getNoAttendeeVCalendar();
		$this->provider->send($vcalendar->VEVENT, $this->calendarDisplayName, $users);
	}

	public function testSendWithAttendees(): void {
		[$user1, $user2, $user3, , $user5] = $users = $this->getUsers();

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

		$this->mailer->expects($this->exactly(2))
			->method('createEMailTemplate')
			->with('dav.calendarReminder')
			->willReturnOnConsecutiveCalls($template1, $template2);

		$this->mailer->expects($this->exactly(7))
			->method('validateMailAddress')
			->withConsecutive(
				['foo1@example.org'],
				['uid2@example.com'],
				['uid3@example.com'],
				['invalid'],
				['foo3@example.org'],
				['foo4@example.org'],
				['uid1@example.com']
			)
			->willReturnOnConsecutiveCalls(true, true, true, false, true, true, true);

		$this->mailer->expects($this->exactly(6))
			->method('createMessage')
			->with()
			->willReturnOnConsecutiveCalls(
				$message11,
				$message12,
				$message13,
				$message21,
				$message22,
				$message23
			);


		$this->mailer->expects($this->exactly(6))
			->method('send')
			->withConsecutive(
				[$message11],
				[$message12],
				[$message13],
				[$message21],
				[$message22],
				[$message23]
			)
			->willReturn([]);

		$this->setupURLGeneratorMock(2);

		$vcalendar = $this->getAttendeeVCalendar();
		$this->provider->send($vcalendar->VEVENT, $this->calendarDisplayName, $users);
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
	 * @return IMessage
	 */
	private function getMessageMock(string $toMail, IEMailTemplate $templateMock):IMessage {
		$message = $this->createMock(IMessage::class);

		$message->expects($this->once())
			->method('setFrom')
			->with([Util::getDefaultEmailAddress('reminders-noreply')])
			->willReturn($message);

		if (null) {
			$message->expects($this->once())
				->method('setReplyTo')
				->with(null)
				->willReturn($message);
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
			'DTSTART' => new DateTime('2017-01-01 00:00:00+00:00'), // 1483228800,
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
			'DTSTART' => new DateTime('2017-01-01 00:00:00+00:00'), // 1483228800,
			'UID' => 'uid1234',
			'LOCATION' => 'Location 123',
			'DESCRIPTION' => 'DESCRIPTION 456',
		]);

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

	private function setupURLGeneratorMock(int $times = 1):void {
		$imagePathArgs = [];
		$imagePathReturns = [];

		$absoluteURLArgs = [];
		$absoluteURLReturns = [];

		for ($i = 0; $i < $times; $i++) {
			$imagePathArgs = array_merge($imagePathArgs, [
				['core', 'actions/info.png'],
				['core', 'places/calendar.png'],
				['core', 'actions/address.png'],
				['core', 'actions/more.png']
			]);
			$imagePathReturns = array_merge($imagePathReturns, ['imagePath1', 'imagePath2', 'imagePath3', 'imagePath4']);

			$absoluteURLArgs = array_merge($absoluteURLArgs, [
				['imagePath1'],
				['imagePath2'],
				['imagePath3'],
				['imagePath4']
			]);

			$absoluteURLReturns = array_merge($absoluteURLReturns, ['AbsURL1', 'AbsURL2', 'AbsURL3', 'AbsURL4']);
		}

		$this->urlGenerator
			->expects($this->exactly(4 * $times))
			->method('imagePath')
			->withConsecutive(...$imagePathArgs)
			->willReturnOnConsecutiveCalls(...$imagePathReturns);

		$this->urlGenerator
			->expects($this->exactly(4 * $times))
			->method('getAbsoluteURL')
			->withConsecutive(...$absoluteURLArgs)
			->willReturnOnConsecutiveCalls(...$absoluteURLReturns);
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
