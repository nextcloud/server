<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Controller;

use OC\Mail\Message;
use OC\User\User;
use OCA\Settings\Controller\MailSettingsController;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @package Tests\Settings\Controller
 */
class MailSettingsControllerTest extends \Test\TestCase {
	private IConfig&MockObject $config;
	private IUserSession&MockObject $userSession;
	private IMailer&MockObject $mailer;
	private IL10N&MockObject $l;
	private IURLGenerator&MockObject $urlGenerator;
	private LoggerInterface&MockObject $logger;
	private MailSettingsController $mailController;

	protected function setUp(): void {
		parent::setUp();

		$this->l = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		/** @var IRequest&MockObject $request */
		$request = $this->createMock(IRequest::class);
		$this->mailController = new MailSettingsController(
			'settings',
			$request,
			$this->l,
			$this->config,
			$this->userSession,
			$this->urlGenerator,
			$this->mailer,
			$this->logger,
		);
	}

	public function testSetMailSettings(): void {
		$calls = [
			[[
				'mail_domain' => 'nextcloud.com',
				'mail_from_address' => 'demo@nextcloud.com',
				'mail_smtpmode' => 'smtp',
				'mail_smtpsecure' => 'ssl',
				'mail_smtphost' => 'mx.nextcloud.org',
				'mail_smtpauth' => 1,
				'mail_smtpport' => '25',
				'mail_sendmailmode' => 'smtp',
			]],
			[[
				'mail_domain' => 'nextcloud.com',
				'mail_from_address' => 'demo@nextcloud.com',
				'mail_smtpmode' => 'smtp',
				'mail_smtpsecure' => 'ssl',
				'mail_smtphost' => 'mx.nextcloud.org',
				'mail_smtpauth' => null,
				'mail_smtpport' => '25',
				'mail_smtpname' => null,
				'mail_smtppassword' => null,
				'mail_sendmailmode' => 'smtp',
			]],
		];
		$this->config->expects($this->exactly(2))
			->method('setSystemValues')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		// With authentication
		$response = $this->mailController->setMailSettings(
			'nextcloud.com',
			'demo@nextcloud.com',
			'smtp',
			'ssl',
			'mx.nextcloud.org',
			'1',
			'25',
			'smtp'
		);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		// Without authentication (testing the deletion of the stored password)
		$response = $this->mailController->setMailSettings(
			'nextcloud.com',
			'demo@nextcloud.com',
			'smtp',
			'ssl',
			'mx.nextcloud.org',
			'0',
			'25',
			'smtp'
		);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testStoreCredentials(): void {
		$this->config
			->expects($this->once())
			->method('setSystemValues')
			->with([
				'mail_smtpname' => 'UsernameToStore',
				'mail_smtppassword' => 'PasswordToStore',
			]);

		$response = $this->mailController->storeCredentials('UsernameToStore', 'PasswordToStore');
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testSendTestMail(): void {
		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('Werner');
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn('Werner Brösel');

		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user);

		// Ensure that it fails when no mail address has been specified
		$response = $this->mailController->sendTestMail();
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame('You need to set your account email before being able to send test emails. Go to  for that.', $response->getData());

		// If no exception is thrown it should work
		$this->config
			->expects($this->any())
			->method('getUserValue')
			->willReturn('mail@example.invalid');
		$this->mailer->expects($this->once())
			->method('createMessage')
			->willReturn($this->createMock(Message::class));
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$response = $this->mailController->sendTestMail();
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}
}
