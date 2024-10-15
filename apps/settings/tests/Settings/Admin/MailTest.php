<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Settings\Admin\Mail;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Server;
use Test\TestCase;

class MailTest extends TestCase {
	/** @var Mail */
	private $admin;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();

		$this->admin = new Mail(
			$this->config,
			$this->l10n
		);
	}

	public function testGetForm(): void {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['mail_domain', '', 'mx.nextcloud.com'],
				['mail_from_address', '', 'no-reply@nextcloud.com'],
				['mail_smtpmode', '', 'smtp'],
				['mail_smtpsecure', '', true],
				['mail_smtphost', '', 'smtp.nextcloud.com'],
				['mail_smtpport', '', 25],
				['mail_smtpauth', false, true],
				['mail_smtpname', '', 'smtp.sender.com'],
				['mail_smtppassword', '', 'mypassword'],
				['mail_sendmailmode', 'smtp', 'smtp'],
			]);

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/additional-mail',
			[
				'sendmail_is_available' => (bool)Server::get(IBinaryFinder::class)->findBinaryPath('sendmail'),
				'mail_domain' => 'mx.nextcloud.com',
				'mail_from_address' => 'no-reply@nextcloud.com',
				'mail_smtpmode' => 'smtp',
				'mail_smtpsecure' => true,
				'mail_smtphost' => 'smtp.nextcloud.com',
				'mail_smtpport' => 25,
				'mail_smtpauth' => true,
				'mail_smtpname' => 'smtp.sender.com',
				'mail_smtppassword' => '********',
				'mail_sendmailmode' => 'smtp',
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(10, $this->admin->getPriority());
	}
}
