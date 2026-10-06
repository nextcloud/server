<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Listener;

use OC\URLGenerator;
use OCA\DAV\CalDAV\Proxy\Proxy;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCA\DAV\Listener\CalendarDelegateActionListener;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\EMailDetails;
use OCP\Mail\EMailDetailsRow;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CalendarDelegateActionListenerTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private IUserManager&MockObject $userManager;
	private IMailer&MockObject $mailer;
	private IEMailTemplate&MockObject $template;
	private LoggerInterface&MockObject $logger;
	private CalendarDelegateActionListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters)
		);
		$l10n->method('l')->willReturn('date');
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')->willReturn($l10n);

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getDateTime')->willReturn(new \DateTime('20240601T000000'));

		$imipService = new IMipService(
			$this->createMock(URLGenerator::class),
			$this->createMock(IDBConnection::class),
			$this->createMock(ISecureRandom::class),
			$l10nFactory,
			$timeFactory,
			$this->createMock(IUserManager::class),
			$this->createMock(IUserConfig::class),
			$this->createMock(IAppConfig::class),
		);

		$proxy = new Proxy();
		$proxy->setProxyId('principals/users/bob');
		$proxyMapper = $this->createMock(ProxyMapper::class);
		$proxyMapper->method('getProxiesOf')
			->with('principals/users/alice')
			->willReturn([$proxy]);

		$this->template = $this->createMock(IEMailTemplate::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->mailer->method('createEMailTemplate')->willReturn($this->template);
		$this->mailer->method('createMessage')->willReturn($this->createMock(IMessage::class));

		$this->userSession = $this->createMock(IUserSession::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new CalendarDelegateActionListener(
			$this->userSession,
			$this->userManager,
			$proxyMapper,
			$this->mailer,
			$l10nFactory,
			$imipService,
			$this->logger,
		);
	}

	public static function actorEmailProvider(): array {
		return [
			'with email' => ['bob@example.com', 'bob@example.com'],
			'without email' => [null, ''],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'actorEmailProvider')]
	public function testMailShowsActorAndCalendar(?string $actorEmail, string $expectedSubline): void {
		$actor = $this->createMock(IUser::class);
		$actor->method('getUID')->willReturn('bob');
		$actor->method('getDisplayName')->willReturn('Bob');
		$actor->method('getEMailAddress')->willReturn($actorEmail);
		$this->userSession->method('getUser')->willReturn($actor);

		$owner = $this->createMock(IUser::class);
		$owner->method('getUID')->willReturn('alice');
		$owner->method('getEMailAddress')->willReturn('alice@example.com');
		$this->userManager->method('get')->with('alice')->willReturn($owner);

		$this->template->expects(self::once())
			->method('addBodySender')
			->with('Bob', $expectedSubline);
		$this->template->expects(self::once())
			->method('addBodyDetails')
			->with($this->callback(function (EMailDetails $details): bool {
				$rows = array_map(
					static fn (EMailDetailsRow $row): array => [$row->getLabel(), array_column($row->getParts(), 'text')],
					$details->getRows(),
				);
				self::assertSame('Team sync', $details->getTitle());
				self::assertContains(['Calendar', ['Work']], $rows);
				return true;
			}));
		$this->mailer->expects(self::once())->method('send');
		$this->logger->expects(self::never())->method('warning');

		$calendarData = <<<ICS
			BEGIN:VCALENDAR
			VERSION:2.0
			BEGIN:VEVENT
			UID:event-1
			DTSTART;TZID=Europe/Berlin:20240701T080000
			DTEND;TZID=Europe/Berlin:20240701T090000
			SUMMARY:Team sync
			END:VEVENT
			END:VCALENDAR
			ICS;

		$this->listener->handle(new CalendarObjectCreatedEvent(
			1,
			['principaluri' => 'principals/users/alice', '{DAV:}displayname' => 'Work'],
			[],
			['calendardata' => $calendarData],
		));
	}
}
