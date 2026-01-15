<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Settings\Admin\Mail;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MailTest extends TestCase {

	private Mail $admin;
	private IConfig&MockObject $config;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private IInitialState&MockObject $initialState;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->admin = new Mail(
			$this->config,
			$this->l10n,
			$this->initialState,
			$this->urlGenerator,
		);
	}

	public static function dataGetForm(): array {
		return [
			[true],
			[false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetForm')]
	public function testGetForm(bool $sendmail) {
		$finder = $this->createMock(IBinaryFinder::class);
		$finder->expects(self::atLeastOnce())
			->method('findBinaryPath')
			->willReturnMap([
				['qmail', false],
				['sendmail', $sendmail ? '/usr/bin/sendmail' : false],
			]);
		$this->overwriteService(IBinaryFinder::class, $finder);

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

		$initialState = [];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, array $data) use (&$initialState): void {
				$initialState[$key] = $data;
			});

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/additional-mail',
			renderAs: ''
		);

		$this->assertEquals($expected, $this->admin->getForm());
		self::assertEquals([
			'settingsAdminMail' => [
				'configIsReadonly' => false,
				'docUrl' => '',
				'smtpModeOptions' => [
					['label' => 'SMTP', 'id' => 'smtp'],
					...($sendmail ? [['label' => 'Sendmail', 'id' => 'sendmail']] : [])
				],
				'smtpEncryptionOptions' => [
					['label' => 'None / STARTTLS', 'id' => ''],
					['label' => 'SSL/TLS', 'id' => 'ssl'],
				],
				'smtpSendmailModeOptions' => [
					['label' => 'smtp (-bs)', 'id' => 'smtp'],
					['label' => 'pipe (-t -i)', 'id' => 'pipe'],
				],
			],
			'settingsAdminMailConfig' => [
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
				'mail_noverify' => false,
			],
		], $initialState);
	}

	public function testGetSection(): void {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(10, $this->admin->getPriority());
	}
}
