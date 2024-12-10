<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OCA\DAV\CalDAV\EventComparisonService;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IAttachment;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Mail\Provider\IManager as IMailManager;
use OCP\Mail\Provider\IMessage as IMailMessageNew;
use OCP\Mail\Provider\IMessageSend as IMailMessageSend;
use OCP\Mail\Provider\IService as IMailService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;
use Test\TestCase;
use function array_merge;

interface IMailServiceMock extends IMailService, IMailMessageSend {
	// workaround for creating mock class with multiple interfaces
	// TODO: remove after phpUnit 10 is supported.
}

class IMipPluginTest extends TestCase {

	/** @var IMessage|MockObject */
	private $mailMessage;

	/** @var IMailer|MockObject */
	private $mailer;

	/** @var IEMailTemplate|MockObject */
	private $emailTemplate;

	/** @var IAttachment|MockObject */
	private $emailAttachment;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var IAppConfig|MockObject */
	private $config;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var IUser|MockObject */
	private $user;

	/** @var IMipPlugin */
	private $plugin;

	/** @var IMipService|MockObject */
	private $service;

	/** @var Defaults|MockObject */
	private $defaults;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var EventComparisonService|MockObject */
	private $eventComparisonService;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var IMailService|IMailMessageSend|MockObject */
	private $mailService;

	/** @var IMailMessageNew|MockObject */
	private $mailMessageNew;

	protected function setUp(): void {
		$this->mailMessage = $this->createMock(IMessage::class);
		$this->mailMessage->method('setFrom')->willReturn($this->mailMessage);
		$this->mailMessage->method('setReplyTo')->willReturn($this->mailMessage);
		$this->mailMessage->method('setTo')->willReturn($this->mailMessage);

		$this->mailer = $this->createMock(IMailer::class);
		$this->mailer->method('createMessage')->willReturn($this->mailMessage);

		$this->emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->mailer->method('createEMailTemplate')->willReturn($this->emailTemplate);

		$this->emailAttachment = $this->createMock(IAttachment::class);
		$this->mailer->method('createAttachment')->willReturn($this->emailAttachment);

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1496912528); // 2017-01-01

		$this->config = $this->createMock(IAppConfig::class);

		$this->user = $this->createMock(IUser::class);

		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')
			->willReturn($this->user);

		$this->defaults = $this->createMock(Defaults::class);
		$this->defaults->method('getName')
			->willReturn('Instance Name 123');

		$this->service = $this->createMock(IMipService::class);

		$this->eventComparisonService = $this->createMock(EventComparisonService::class);

		$this->mailManager = $this->createMock(IMailManager::class);

		$this->mailService = $this->createMock(IMailServiceMock::class);

		$this->mailMessageNew = $this->createMock(IMailMessageNew::class);

		$this->plugin = new IMipPlugin(
			$this->config,
			$this->mailer,
			$this->logger,
			$this->timeFactory,
			$this->defaults,
			$this->userSession,
			$this->service,
			$this->eventComparisonService,
			$this->mailManager,
		);
	}

	public function testDeliveryNoSignificantChange(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$message->message->VEVENT->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$message->message->VEVENT->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE']);
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		$message->significantChange = false;
		$this->plugin->schedule($message);
		$this->assertEquals('1.0', $message->getScheduleStatus());
	}

	public function testParsingSingle(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'one', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting without (!) Boromir',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		// save the old copy in the plugin
		$oldVCalendar = new VCalendar();
		$oldVEvent = new VEvent($oldVCalendar, 'one', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		]);
		$oldVEvent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'boromir@tra.it.or', ['RSVP' => 'TRUE']);
		$oldVCalendar->add($oldVEvent);
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting without (!) Boromir',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$atnd = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$atnd = $attendee;
			}
		}
		$this->plugin->setVCalendar($oldVCalendar);
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->willReturn(['new' => [$newVevent], 'old' => [$oldVEvent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($atnd);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($atnd)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($newVevent, $oldVEvent)
			->willReturn($data);
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Fellowship meeting without (!) Boromir', true);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $newVevent, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->service->expects(self::once())
			->method('createInvitationToken')
			->with($message, $newVevent, 1496912700)
			->willReturn('token');
		$this->service->expects(self::once())
			->method('addResponseButtons')
			->with($this->emailTemplate, 'token');
		$this->service->expects(self::once())
			->method('addMoreOptionsButton')
			->with($this->emailTemplate, 'token');
		$this->mailer->expects(self::once())
			->method('send')
			->willReturn([]);
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testAttendeeIsResource(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'one', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting without (!) Boromir',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'the-shire@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'The Shire', 'CUTYPE' => 'ROOM']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'the-shire@hobb.it';
		// save the old copy in the plugin
		$oldVCalendar = new VCalendar();
		$oldVEvent = new VEvent($oldVCalendar, 'one', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		]);
		$oldVEvent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'the-shire@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'The Shire', 'CUTYPE' => 'ROOM']);
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'boromir@tra.it.or', ['RSVP' => 'TRUE']);
		$oldVCalendar->add($oldVEvent);
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting without (!) Boromir',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$room = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$room = $attendee;
			}
		}
		$this->plugin->setVCalendar($oldVCalendar);
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('the-shire@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->willReturn(['new' => [$newVevent], 'old' => [$oldVEvent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($room);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($room)
			->willReturn(true);
		$this->service->expects(self::never())
			->method('buildBodyData');
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::never())
			->method('getFrom');
		$this->service->expects(self::never())
			->method('addSubjectAndHeading');
		$this->service->expects(self::never())
			->method('addBulletList');
		$this->service->expects(self::never())
			->method('getAttendeeRsvpOrReqForParticipant');
		$this->config->expects(self::never())
			->method('getValueString');
		$this->service->expects(self::never())
			->method('createInvitationToken');
		$this->service->expects(self::never())
			->method('addResponseButtons');
		$this->service->expects(self::never())
			->method('addMoreOptionsButton');
		$this->mailer->expects(self::never())
			->method('send');
		$this->plugin->schedule($message);
		$this->assertEquals('1.0', $message->getScheduleStatus());
	}

	public function testParsingRecurrence(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'one', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z'
		]);
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'Frodo']);
		$newvEvent2 = new VEvent($newVCalendar, 'two', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Elevenses',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RECURRENCE-ID' => new \DateTime('2016-01-01 00:00:00')
		]);
		$newvEvent2->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newvEvent2->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		// save the old copy in the plugin
		$oldVCalendar = new VCalendar();
		$oldVEvent = new VEvent($oldVCalendar, 'one', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z'
		]);
		$oldVEvent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Elevenses',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$atnd = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$atnd = $attendee;
			}
		}
		$this->plugin->setVCalendar($oldVCalendar);
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->willReturn(['old' => [] ,'new' => [$newVevent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($atnd);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($atnd)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($newVevent, null)
			->willReturn($data);
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Elevenses', false);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $newVevent, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->service->expects(self::once())
			->method('createInvitationToken')
			->with($message, $newVevent, 1496912700)
			->willReturn('token');
		$this->service->expects(self::once())
			->method('addResponseButtons')
			->with($this->emailTemplate, 'token');
		$this->service->expects(self::once())
			->method('addMoreOptionsButton')
			->with($this->emailTemplate, 'token');
		$this->mailer->expects(self::once())
			->method('send')
			->willReturn([]);
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testEmailValidationFailed(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$message->message->VEVENT->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$message->message->VEVENT->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE']);
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';

		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(false);

		$this->plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
	}

	public function testFailedDelivery(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVcalendar = new VCalendar();
		$newVevent = new VEvent($newVcalendar, 'one', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting without (!) Boromir',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'Frodo']);
		$message->message = $newVcalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		// save the old copy in the plugin
		$oldVcalendar = new VCalendar();
		$oldVevent = new VEvent($oldVcalendar, 'one', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		]);
		$oldVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$oldVevent->add('ATTENDEE', 'mailto:' . 'boromir@tra.it.or', ['RSVP' => 'TRUE']);
		$oldVcalendar->add($oldVevent);
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting without (!) Boromir',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$atnd = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$atnd = $attendee;
			}
		}
		$this->plugin->setVCalendar($oldVcalendar);
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->willReturn(['old' => [] ,'new' => [$newVevent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($atnd);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($atnd)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($newVevent, null)
			->willReturn($data);
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Fellowship meeting without (!) Boromir', false);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $newVevent, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->service->expects(self::once())
			->method('createInvitationToken')
			->with($message, $newVevent, 1496912700)
			->willReturn('token');
		$this->service->expects(self::once())
			->method('addResponseButtons')
			->with($this->emailTemplate, 'token');
		$this->service->expects(self::once())
			->method('addMoreOptionsButton')
			->with($this->emailTemplate, 'token');
		$this->mailer->expects(self::once())
			->method('send')
			->willReturn([]);
		$this->mailer
			->method('send')
			->willThrowException(new \Exception());
		$this->logger->expects(self::once())
			->method('error');
		$this->plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
	}

	public function testMailProviderSend(): void {
		// construct iTip message with event and attendees
		$message = new Message();
		$message->method = 'REQUEST';
		$calendar = new VCalendar();
		$event = new VEvent($calendar, 'one', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting without (!) Boromir',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$event->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$event->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'Frodo']);
		$message->message = $calendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		// construct
		foreach ($event->select('ATTENDEE') as $entry) {
			if (strcasecmp($entry->getValue(), $message->recipient) === 0) {
				$attendee = $entry;
			}
		}
		// construct body data return
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting without (!) Boromir',
			'attendee_name' => 'frodo@hobb.it'
		];
		// construct system config mock returns
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		// construct user mock returns
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		// construct user session mock returns
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		// construct service mock returns
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($attendee);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($attendee)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($event, null)
			->willReturn($data);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Fellowship meeting without (!) Boromir', false);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $event, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->service->expects(self::once())
			->method('createInvitationToken')
			->with($message, $event, 1496912700)
			->willReturn('token');
		$this->service->expects(self::once())
			->method('addResponseButtons')
			->with($this->emailTemplate, 'token');
		$this->service->expects(self::once())
			->method('addMoreOptionsButton')
			->with($this->emailTemplate, 'token');
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->willReturn(['old' => [] ,'new' => [$event]]);
		// construct mail mock returns
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		// construct mail provider mock returns
		$this->mailService
			->method('initiateMessage')
			->willReturn($this->mailMessageNew);
		$this->mailService
			->method('sendMessage')
			->with($this->mailMessageNew);
		$this->mailManager
			->method('findServiceByAddress')
			->with('user1', 'gandalf@wiz.ard')
			->willReturn($this->mailService);

		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testMailProviderDisabled(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'one', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting without (!) Boromir',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		// save the old copy in the plugin
		$oldVCalendar = new VCalendar();
		$oldVEvent = new VEvent($oldVCalendar, 'one', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		]);
		$oldVEvent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$oldVEvent->add('ATTENDEE', 'mailto:' . 'boromir@tra.it.or', ['RSVP' => 'TRUE']);
		$oldVCalendar->add($oldVEvent);
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting without (!) Boromir',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$atnd = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$atnd = $attendee;
			}
		}
		$this->plugin->setVCalendar($oldVCalendar);
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->willReturn(['new' => [$newVevent], 'old' => [$oldVEvent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($atnd);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($atnd)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($newVevent, $oldVEvent)
			->willReturn($data);
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Fellowship meeting without (!) Boromir', true);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $newVevent, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->config->expects(self::once())
			->method('getValueBool')
			->with('core', 'mail_providers_enabled', true)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('createInvitationToken')
			->with($message, $newVevent, 1496912700)
			->willReturn('token');
		$this->service->expects(self::once())
			->method('addResponseButtons')
			->with($this->emailTemplate, 'token');
		$this->service->expects(self::once())
			->method('addMoreOptionsButton')
			->with($this->emailTemplate, 'token');
		$this->mailer->expects(self::once())
			->method('send')
			->willReturn([]);
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testNoOldEvent(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'VEVENT', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$atnd = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$atnd = $attendee;
			}
		}
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->with($newVCalendar, null)
			->willReturn(['old' => [] ,'new' => [$newVevent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($atnd);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($atnd)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($newVevent, null)
			->willReturn($data);
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Fellowship meeting', false);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $newVevent, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->service->expects(self::once())
			->method('createInvitationToken')
			->with($message, $newVevent, 1496912700)
			->willReturn('token');
		$this->service->expects(self::once())
			->method('addResponseButtons')
			->with($this->emailTemplate, 'token');
		$this->service->expects(self::once())
			->method('addMoreOptionsButton')
			->with($this->emailTemplate, 'token');
		$this->mailer->expects(self::once())
			->method('send')
			->willReturn([]);
		$this->mailer
			->method('send')
			->willReturn([]);
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testNoButtons(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'VEVENT', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting',
			'attendee_name' => 'frodo@hobb.it'
		];
		$attendees = $newVevent->select('ATTENDEE');
		$atnd = '';
		foreach ($attendees as $attendee) {
			if (strcasecmp($attendee->getValue(), $message->recipient) === 0) {
				$atnd = $attendee;
			}
		}
		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn(1496912700);
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(true);
		$this->eventComparisonService->expects(self::once())
			->method('findModified')
			->with($newVCalendar, null)
			->willReturn(['old' => [] ,'new' => [$newVevent]]);
		$this->service->expects(self::once())
			->method('getCurrentAttendee')
			->with($message)
			->willReturn($atnd);
		$this->service->expects(self::once())
			->method('isRoomOrResource')
			->with($atnd)
			->willReturn(false);
		$this->service->expects(self::once())
			->method('buildBodyData')
			->with($newVevent, null)
			->willReturn($data);
		$this->user->expects(self::any())
			->method('getUID')
			->willReturn('user1');
		$this->user->expects(self::any())
			->method('getDisplayName')
			->willReturn('Mr. Wizard');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($this->user);
		$this->service->expects(self::once())
			->method('getFrom');
		$this->service->expects(self::once())
			->method('addSubjectAndHeading')
			->with($this->emailTemplate, 'request', 'Mr. Wizard', 'Fellowship meeting', false);
		$this->service->expects(self::once())
			->method('addBulletList')
			->with($this->emailTemplate, $newVevent, $data);
		$this->service->expects(self::once())
			->method('getAttendeeRsvpOrReqForParticipant')
			->willReturn(true);
		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('no');
		$this->service->expects(self::never())
			->method('createInvitationToken');
		$this->service->expects(self::never())
			->method('addResponseButtons');
		$this->service->expects(self::never())
			->method('addMoreOptionsButton');
		$this->mailer->expects(self::once())
			->method('send')
			->willReturn([]);
		$this->mailer
			->method('send')
			->willReturn([]);
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}
}
