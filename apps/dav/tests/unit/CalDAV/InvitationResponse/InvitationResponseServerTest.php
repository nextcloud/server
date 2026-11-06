<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\InvitationResponse;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\ConfigLexicon;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Reader;
use Test\TestCase;
use Test\Traits\UserTrait;

#[Group(name: 'DB')]
class InvitationResponseServerTest extends TestCase {
	use UserTrait;

	private const ORGANIZER = 'mailto:organizer@example.com';
	private const INTERNAL_ATTENDEE = 'mailto:attendee@example.com';
	private const EXTERNAL_ATTENDEE = 'mailto:external@example.org';
	private const UID = 'b5b4a3e0-2a1e-4b8f-9e57-6c3f0d7d1a2b';

	/** @var array<int, IUser> calendar id => owner */
	private array $calendars = [];
	private IMipPlugin&MockObject $iMipPlugin;

	protected function setUp(): void {
		parent::setUp();

		// Partial mock, the real initialize() subscribes schedule() to Sabre's schedule event
		$this->iMipPlugin = $this->getMockBuilder(IMipPlugin::class)
			->disableOriginalConstructor()
			->onlyMethods(['schedule'])
			->getMock();
		$this->overwriteService(IMipPlugin::class, $this->iMipPlugin);
	}

	protected function tearDown(): void {
		foreach ($this->calendars as $calendarId => $user) {
			Server::get(CalDavBackend::class)->deleteCalendar($calendarId, true);
			Server::get(IUserConfig::class)->deleteAllUserConfig($user->getUID());
		}
		Server::get(IAppConfig::class)->deleteKey(Application::APP_ID, ConfigLexicon::SEND_INVITATIONS);
		parent::tearDown();
	}

	public static function attendeeProvider(): array {
		return [
			'internal attendee' => [self::INTERNAL_ATTENDEE, true],
			'external attendee' => [self::EXTERNAL_ATTENDEE, false],
		];
	}

	public function testIMipPluginIsOnlyRegisteredWhenHandlingITipMessages(): void {
		$server = new InvitationResponseServer();

		$this->assertNull($server->getServer()->getPlugin('imip'));

		$server->handleITipMessage($this->buildReply(self::INTERNAL_ATTENDEE, self::INTERNAL_ATTENDEE, 'ACCEPTED'));

		$this->assertSame($this->iMipPlugin, $server->getServer()->getPlugin('imip'));
	}

	#[DataProvider('attendeeProvider')]
	public function testReplySendsEmailToOrganizer(string $attendee, bool $hasAccount): void {
		$message = $this->createEventAndReply($attendee, $hasAccount, 'NEEDS-ACTION');

		$this->iMipPlugin->expects($this->once())
			->method('schedule')
			->with($this->callback(fn (Message $reply): bool => $this->assertReplyToOrganizer($reply, $attendee)));

		(new InvitationResponseServer())->handleITipMessage($message);

		$this->assertSame('1.2', $message->getScheduleStatus());
	}

	#[DataProvider('attendeeProvider')]
	public function testNoEmailWhenInvitationsAreDisabled(string $attendee, bool $hasAccount): void {
		Server::get(IAppConfig::class)->setValueBool(Application::APP_ID, ConfigLexicon::SEND_INVITATIONS, false);
		$message = $this->createEventAndReply($attendee, $hasAccount, 'NEEDS-ACTION');

		$this->iMipPlugin->expects($this->never())->method('schedule');

		$server = new InvitationResponseServer();
		$server->handleITipMessage($message);

		$this->assertNull($server->getServer()->getPlugin('imip'));
		$this->assertSame('1.2', $message->getScheduleStatus());
	}

	public function testExternalAttendeeReplyWithoutChangeSendsNoEmail(): void {
		$message = $this->createEventAndReply(self::EXTERNAL_ATTENDEE, false, 'ACCEPTED');

		$this->iMipPlugin->expects($this->never())->method('schedule');

		(new InvitationResponseServer())->handleITipMessage($message);
	}

	private function assertReplyToOrganizer(Message $reply, string $attendee): bool {
		$this->assertSame('REPLY', $reply->method);
		$this->assertSame($attendee, $reply->sender);
		$this->assertSame(self::ORGANIZER, $reply->recipient);
		$this->assertSame('Team meeting', (string)$reply->message->VEVENT->SUMMARY);
		$this->assertSame('20991120T100000Z', (string)$reply->message->VEVENT->DTSTART);
		$this->assertSame('ACCEPTED', (string)$reply->message->VEVENT->ATTENDEE['PARTSTAT']);
		return true;
	}

	/**
	 * The reply goes to the attendee's own copy if they have an account, otherwise to the organizer's
	 */
	private function createEventAndReply(string $attendee, bool $hasAccount, string $currentPartStat): Message {
		$this->createUserWithEvent('invitation-response-organizer', self::ORGANIZER, $attendee, $currentPartStat);
		if ($hasAccount) {
			$this->createUserWithEvent('invitation-response-attendee', $attendee, $attendee, $currentPartStat);
		}

		$recipient = $hasAccount ? $attendee : self::ORGANIZER;
		return $this->buildReply($attendee, $recipient, 'ACCEPTED');
	}

	private function createUserWithEvent(string $userId, string $email, string $attendee, string $partStat): void {
		$user = $this->createUser($userId, 'password');
		$user->setSystemEMailAddress(substr($email, 7));

		$backend = Server::get(CalDavBackend::class);
		$calendarId = $backend->createCalendar('principals/users/' . $userId, 'personal', []);
		$this->calendars[$calendarId] = $user;
		$organizer = self::ORGANIZER;
		$uid = self::UID;
		$backend->createCalendarObject($calendarId, 'event.ics', <<<EOF
			BEGIN:VCALENDAR
			VERSION:2.0
			PRODID:-//Test//EN
			BEGIN:VEVENT
			UID:$uid
			DTSTAMP:20261104T100000Z
			DTSTART:20991120T100000Z
			DTEND:20991120T110000Z
			SUMMARY:Team meeting
			SEQUENCE:0
			ORGANIZER:$organizer
			ATTENDEE;RSVP=TRUE;PARTSTAT=$partStat:$attendee
			END:VEVENT
			END:VCALENDAR
			EOF);
	}

	private function buildReply(string $attendee, string $recipient, string $partStat): Message {
		$message = new Message();
		$message->uid = self::UID;
		$message->component = 'VEVENT';
		$message->method = 'REPLY';
		$message->sequence = 0;
		$message->sender = $attendee;
		$message->recipient = $recipient;
		$organizer = self::ORGANIZER;
		$uid = self::UID;
		$message->message = Reader::read(<<<EOF
			BEGIN:VCALENDAR
			VERSION:2.0
			METHOD:REPLY
			BEGIN:VEVENT
			ATTENDEE;PARTSTAT=$partStat:$attendee
			ORGANIZER:$organizer
			UID:$uid
			SEQUENCE:0
			DTSTAMP:20261104T120000Z
			END:VEVENT
			END:VCALENDAR
			EOF);
		return $message;
	}
}
