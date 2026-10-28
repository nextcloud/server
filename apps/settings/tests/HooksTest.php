<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests;

use OCA\Settings\Hooks;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\EMailDetails;
use OCP\Mail\EMailDetailsRow;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class HooksTest extends TestCase {
	private IMailer&MockObject $mailer;
	private IEMailTemplate&MockObject $template;
	private IUser&MockObject $user;
	private Hooks $hooks;

	protected function setUp(): void {
		parent::setUp();

		$activityManager = $this->createMock(IActivityManager::class);
		$activityManager->method('generateEvent')->willReturn($this->createMock(IEvent::class));

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('getAbsoluteURL')->willReturn('https://cloud.example.com/');

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$l10n->method('getLanguageCode')->willReturn('de');
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')->with('settings')->willReturn($l10n);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');
		$this->user->method('getDisplayName')->willReturn('Alice');
		$this->user->method('getLastLogin')->willReturn(1);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->user);

		$this->template = $this->createMock(IEMailTemplate::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->mailer->method('createEMailTemplate')->willReturn($this->template);
		$this->mailer->method('createMessage')->willReturn($this->createMock(IMessage::class));

		$this->hooks = new Hooks(
			$activityManager,
			$this->createMock(IGroupManager::class),
			$this->createMock(IUserManager::class),
			$userSession,
			$urlGenerator,
			$this->mailer,
			$this->createMock(IConfig::class),
			$l10nFactory,
			$this->createMock(Defaults::class),
		);
	}

	public function testPasswordChangedMail(): void {
		$this->user->method('getEMailAddress')->willReturn('alice@example.com');

		$this->template->expects($this->once())->method('setLanguage')->with('de');
		$this->template->expects($this->once())->method('addBodyText')
			->with('Your password on https://cloud.example.com/ was changed.');
		$this->template->expects($this->once())->method('addBodyNote')
			->with('If you did not request this, please contact an administrator.', '', IEMailTemplate::NOTE_WARNING);
		$this->mailer->expects($this->once())->method('send');

		$this->hooks->handle(new PasswordUpdatedEvent($this->user, 'secret'));
	}

	public function testEmailChangedMail(): void {
		$this->user->method('getEMailAddress')->willReturn('new@example.com');
		$order = [];

		$this->template->expects($this->once())->method('setLanguage')->with('de');
		$this->template->expects($this->once())->method('addBodyText')
			->with('Your email address on https://cloud.example.com/ was changed.');
		$this->template->expects($this->once())->method('addBodyDetails')
			->with($this->callback(fn (EMailDetails $details): bool => $this->detailsToArray($details) === [
				'title' => 'Alice',
				'initials' => 'Alice',
				'rows' => [
					['Previous email address', [[EMailDetailsRow::PART_TEXT, 'old@example.com']]],
					['New email address', [[EMailDetailsRow::PART_TEXT, 'new@example.com']]],
				],
			]))
			->willReturnCallback(function () use (&$order): void {
				$order[] = 'details';
			});
		$this->template->expects($this->once())->method('addBodyNote')
			->with('If you did not request this, please contact an administrator.', '', IEMailTemplate::NOTE_WARNING)
			->willReturnCallback(function () use (&$order): void {
				$order[] = 'note';
			});
		$this->mailer->expects($this->once())->method('send');

		$this->hooks->handle(new UserChangedEvent($this->user, 'eMailAddress', 'new@example.com', 'old@example.com'));

		$this->assertSame(['details', 'note'], $order);
	}

	public function testEmailRemovedMail(): void {
		$this->user->method('getEMailAddress')->willReturn(null);

		$this->template->expects($this->once())->method('addBodyDetails')
			->with($this->callback(fn (EMailDetails $details): bool => $this->detailsToArray($details)['rows'] === [
				['Previous email address', [[EMailDetailsRow::PART_TEXT, 'old@example.com']]],
				['New email address', [[EMailDetailsRow::PART_MUTED, 'No email address set']]],
			]));
		$this->mailer->expects($this->once())->method('send');

		$this->hooks->handle(new UserChangedEvent($this->user, 'eMailAddress', null, 'old@example.com'));
	}

	private function detailsToArray(EMailDetails $details): array {
		return [
			'title' => $details->getTitle(),
			'initials' => $details->getInitialsName(),
			'rows' => array_map(
				fn (EMailDetailsRow $row): array => [
					$row->getLabel(),
					array_map(fn (array $part): array => [$part['type'], $part['text']], $row->getParts()),
				],
				$details->getRows(),
			),
		];
	}
}
