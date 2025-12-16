<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OC\L10N\L10N;
use OC\URLGenerator;
use OCA\DAV\CalDAV\EventComparisonService;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Config\IUserConfig;
use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Mail\Provider\IManager;
use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IService;
use OCP\Mail\Provider\Message as MailProviderMessage;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Property\ICalendar\CalAddress;
use Symfony\Component\Mime\Email;
use Test\TestCase;
use Test\Traits\EmailValidatorTrait;

class IMipPluginCharsetTest extends TestCase {
	use EmailValidatorTrait;
	// Dependencies
	private Defaults&MockObject $defaults;
	private IAppConfig&MockObject $appConfig;
	private IUserConfig&MockObject $userConfig;
	private IDBConnection&MockObject $db;
	private IFactory $l10nFactory;
	private IManager&MockObject $mailManager;
	private IMailer&MockObject $mailer;
	private ISecureRandom&MockObject $random;
	private ITimeFactory&MockObject $timeFactory;
	private IUrlGenerator&MockObject $urlGenerator;
	private IUserSession&MockObject $userSession;
	private LoggerInterface $logger;
	private IUserManager&MockObject $userManager;

	// Services
	private EventComparisonService $eventComparisonService;
	private IMipPlugin $imipPlugin;
	private IMipService $imipService;

	// ITip Message
	private Message $itipMessage;

	protected function setUp(): void {
		// Used by IMipService and IMipPlugin
		$today = new \DateTime('2025-06-15 14:30');
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')
			->willReturn($today->getTimestamp());
		$this->timeFactory->method('getDateTime')
			->willReturn($today);

		// IMipService
		$this->urlGenerator = $this->createMock(URLGenerator::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$l10n = $this->createMock(L10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->l10nFactory->method('findGenericLanguage')
			->willReturn('en');
		$this->l10nFactory->method('findLocale')
			->willReturn('en_US');
		$this->l10nFactory->method('get')
			->willReturn($l10n);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userManager->method('getByEmail')->willReturn([]);
		$this->imipService = new IMipService(
			$this->urlGenerator,
			$this->db,
			$this->random,
			$this->l10nFactory,
			$this->timeFactory,
			$this->userManager,
			$this->userConfig,
			$this->appConfig,
		);

		// EventComparisonService
		$this->eventComparisonService = new EventComparisonService();

		// IMipPlugin
		$message = new \OC\Mail\Message(new Email(), false);
		$this->mailer = $this->createMock(IMailer::class);
		$this->mailer->method('createMessage')
			->willReturn($message);
		$this->logger = new NullLogger();
		$this->defaults = $this->createMock(Defaults::class);
		$this->defaults->method('getName')
			->willReturn('Instance Name 123');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('luigi');
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')
			->willReturn($user);
		$this->mailManager = $this->createMock(IManager::class);
		$this->imipPlugin = new IMipPlugin(
			$this->appConfig,
			$this->mailer,
			$this->logger,
			$this->timeFactory,
			$this->defaults,
			$this->userSession,
			$this->imipService,
			$this->eventComparisonService,
			$this->mailManager,
			$this->getEmailValidatorWithStrictEmailCheck(),
		);

		// ITipMessage
		$calendar = new VCalendar();
		$event = new VEvent($calendar, 'VEVENT');
		$event->UID = 'uid-1234';
		$event->SEQUENCE = 1;
		$event->SUMMARY = 'Lunch';
		$event->DTSTART = new \DateTime('2025-06-20 12:30:00');
		$organizer = new CalAddress($calendar, 'ORGANIZER', 'mailto:luigi@example.org');
		$event->add($organizer);
		$attendee = new CalAddress($calendar, 'ATTENDEE', 'mailto:jose@example.org', ['RSVP' => 'TRUE', 'CN' => 'José']);
		$event->add($attendee);
		$calendar->add($event);
		$this->itipMessage = new Message();
		$this->itipMessage->method = 'REQUEST';
		$this->itipMessage->message = $calendar;
		$this->itipMessage->sender = 'mailto:luigi@example.org';
		$this->itipMessage->senderName = 'Luigi';
		$this->itipMessage->recipient = 'mailto:' . 'jose@example.org';
	}

	public function testCharsetMailer(): void {
		// Arrange
		$symfonyEmail = null;
		$this->mailer->expects(self::once())
			->method('send')
			->willReturnCallback(function (IMessage $message) use (&$symfonyEmail): array {
				if ($message instanceof \OC\Mail\Message) {
					$symfonyEmail = $message->getSymfonyEmail();
				}
				return [];
			});

		// Act
		$this->imipPlugin->schedule($this->itipMessage);

		// Assert
		$this->assertNotNull($symfonyEmail);
		$body = $symfonyEmail->getBody()->toString();
		$this->assertStringContainsString('Content-Type: text/calendar; method=REQUEST; charset="utf-8"; name=event.ics', $body);
	}

	public function testCharsetMailProvider(): void {
		// Arrange
		$this->appConfig->method('getValueBool')
			->willReturnCallback(function ($app, $key, $default) {
				if ($app === 'core') {
					$this->assertEquals($key, 'mail_providers_enabled');
					return true;
				}
				return $default;
			});
		$mailMessage = new MailProviderMessage();
		$mailService = $this->createMockForIntersectionOfInterfaces([IService::class, IMessageSend::class]);
		$mailService->method('initiateMessage')
			->willReturn($mailMessage);
		$mailService->expects(self::once())
			->method('sendMessage');
		$this->mailManager->method('findServiceByAddress')
			->willReturn($mailService);

		// Act
		$this->imipPlugin->schedule($this->itipMessage);

		// Assert
		$attachments = $mailMessage->getAttachments();
		$this->assertCount(1, $attachments);
		$this->assertStringContainsString('text/calendar; method=REQUEST; charset="utf-8"; name=event.ics', $attachments[0]->getType());
	}
}
