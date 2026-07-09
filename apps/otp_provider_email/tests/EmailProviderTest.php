<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\Test;

use OC\Mail\EMailTemplate;
use OC\Mail\Message;
use OCA\OTPProviderEmail\EmailProvider;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('OTP')]
class EmailProviderTest extends TestCase {
	private EmailProvider $emailProvider;
	private IFactory&MockObject $l10nFactory;
	private IL10N&MockObject $l10n;
	private LoggerInterface $logger;
	private Defaults $defaults;
	private IMailer $mailer;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);

		$this->l10nFactory->method('get')->willReturn($this->l10n);

		$this->mailer = $this->createMock(IMailer::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->l10nFactory->method('get')->willReturn($this->l10n);
		$this->defaults = $this->createMock(Defaults::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->emailProvider = new EmailProvider($this->l10nFactory, $this->mailer, $this->defaults);
	}

	public static function dataTestMaskRecipient() {
		return [
			['test@someurl.tld', 't***@s*****l.tld'],
			['test@longerurl.tld', 't***@lo*****rl.tld'],
			['longuser@longerurl.tld', 'lo****er@lo*****rl.tld'],
			['someone@someurl.tld', 's*****e@s*****l.tld'],
			['test@host.tld', 't***@h***.tld'],
			['test@hostt.tld', 't***@h***t.tld'],
		];
	}

	#[DataProvider('dataTestMaskRecipient')]
	public function testMaskRecipient($recipient, $expectedMasked) {
		$this->assertEquals($expectedMasked, $this->emailProvider->maskRecipient($recipient));
	}

	public function testSendEmail() {
		$otpPassword = 'testpw';
		$otpRecipient = 'some@recipi.ent';
		$messageToRecipient = 'message to recipient';
		$message = new Message(new Email(), false);
		$message->setHtmlBody('test');
		$template = new EMailTemplate(
			$this->createMock(Defaults::class),
			$this->createMock(IURLGenerator::class),
			$this->l10nFactory,
			24,
			24,
			'TEST',
			['otp' => $otpPassword]
		);
		$this->mailer->method('createMessage')->willReturn($message);
		$this->mailer->method('createEMailTemplate')->willReturn($template);
		$this->mailer->expects($this->once())->method('send')->with(
			$this->callback(function (Message $message) use ($otpPassword, $otpRecipient, $messageToRecipient) {
				$bodyPlain = $message->getSymfonyEmail()->getTextBody();
				$bodyHtml = $message->getSymfonyEmail()->getHtmlBody();

				$this->assertEquals([$otpRecipient], $message->getTo());

				$this->assertStringContainsString($otpPassword, $bodyPlain);
				$this->assertStringContainsString($otpPassword, $bodyHtml);
				$this->assertStringContainsString($messageToRecipient, $bodyPlain);
				$this->assertStringContainsString($messageToRecipient, $bodyHtml);

				return true;
			})
		);

		$this->emailProvider->sendOTP($otpRecipient, $otpPassword, $messageToRecipient);
	}

}
