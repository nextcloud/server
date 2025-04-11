<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Mail;

use OC\Mail\EMailTemplate;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use Test\TestCase;

class EMailTemplateTest extends TestCase {
	/** @var Defaults|\PHPUnit\Framework\MockObject\MockObject */
	private $defaults;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var IFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var EMailTemplate */
	private $emailTemplate;

	protected function setUp(): void {
		parent::setUp();

		$this->defaults = $this->createMock(Defaults::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IFactory::class);

		$this->l10n->method('get')
			->with('lib', '')
			->willReturn($this->createMock(IL10N::class));

		$this->emailTemplate = new EMailTemplate(
			$this->defaults,
			$this->urlGenerator,
			$this->l10n,
			252,
			120,
			'test.TestTemplate',
			[]
		);
	}

	public function testEMailTemplateCustomFooter(): void {
		$this->defaults
			->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');
		$this->defaults
			->expects($this->any())
			->method('getLogo')
			->willReturn('/img/logo-mail-header.png');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->any())
			->method('getTextColorPrimary')
			->willReturn('#ffffff');
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/img/logo-mail-header.png')
			->willReturn('https://example.org/img/logo-mail-header.png');

		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading('Welcome aboard');
		$this->emailTemplate->addBodyText('Welcome to your Nextcloud account, you can add, protect, and share your data.');
		$this->emailTemplate->addBodyText('Your username is: abc');
		$this->emailTemplate->addBodyButtonGroup(
			'Set your password', 'https://example.org/resetPassword/123',
			'Install Client', 'https://nextcloud.com/install/#install-clients'
		);
		$this->emailTemplate->addFooter(
			'TestCloud - A safe home for your data<br>This is an automatically sent email, please do not reply.'
		);

		$expectedHTML = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email.html');
		$this->assertSame($expectedHTML, $this->emailTemplate->renderHtml());
		$expectedTXT = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email.txt');
		$this->assertSame($expectedTXT, $this->emailTemplate->renderText());
	}

	public function testEMailTemplateDefaultFooter(): void {
		$this->defaults
			->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->any())
			->method('getSlogan')
			->willReturn('A safe home for your data');
		$this->defaults
			->expects($this->any())
			->method('getLogo')
			->willReturn('/img/logo-mail-header.png');
		$this->defaults
			->expects($this->any())
			->method('getTextColorPrimary')
			->willReturn('#ffffff');
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/img/logo-mail-header.png')
			->willReturn('https://example.org/img/logo-mail-header.png');

		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading('Welcome aboard');
		$this->emailTemplate->addBodyText('Welcome to your Nextcloud account, you can add, protect, and share your data.');
		$this->emailTemplate->addBodyText('Your username is: abc');
		$this->emailTemplate->addBodyButtonGroup(
			'Set your password', 'https://example.org/resetPassword/123',
			'Install Client', 'https://nextcloud.com/install/#install-clients'
		);
		$this->emailTemplate->addFooter();

		$expectedHTML = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email-custom.html');
		$this->assertSame($expectedHTML, $this->emailTemplate->renderHtml());
		$expectedTXT = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email-custom.txt');
		$this->assertSame($expectedTXT, $this->emailTemplate->renderText());
	}

	public function testEMailTemplateSingleButton(): void {
		$this->defaults
			->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->any())
			->method('getSlogan')
			->willReturn('A safe home for your data');
		$this->defaults
			->expects($this->any())
			->method('getLogo')
			->willReturn('/img/logo-mail-header.png');
		$this->defaults
			->expects($this->any())
			->method('getTextColorPrimary')
			->willReturn('#ffffff');
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/img/logo-mail-header.png')
			->willReturn('https://example.org/img/logo-mail-header.png');

		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading('Welcome aboard');
		$this->emailTemplate->addBodyText('Welcome to your Nextcloud account, you can add, protect, and share your data.');
		$this->emailTemplate->addBodyText('Your username is: abc');
		$this->emailTemplate->addBodyButton(
			'Set your password', 'https://example.org/resetPassword/123',
			false
		);
		$this->emailTemplate->addFooter();

		$expectedHTML = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email-single-button.html');
		$this->assertSame($expectedHTML, $this->emailTemplate->renderHtml());
		$expectedTXT = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email-single-button.txt');
		$this->assertSame($expectedTXT, $this->emailTemplate->renderText());
	}



	public function testEMailTemplateAlternativePlainTexts(): void {
		$this->defaults
			->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->any())
			->method('getSlogan')
			->willReturn('A safe home for your data');
		$this->defaults
			->expects($this->any())
			->method('getLogo')
			->willReturn('/img/logo-mail-header.png');
		$this->defaults
			->expects($this->any())
			->method('getTextColorPrimary')
			->willReturn('#ffffff');
		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/img/logo-mail-header.png')
			->willReturn('https://example.org/img/logo-mail-header.png');

		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading('Welcome aboard', 'Welcome aboard - text');
		$this->emailTemplate->addBodyText('Welcome to your Nextcloud account, you can add, protect, and share your data.', 'Welcome to your Nextcloud account, you can add, protect, and share your data. - text');
		$this->emailTemplate->addBodyText('Your username is: abc');
		$this->emailTemplate->addBodyButtonGroup(
			'Set your password', 'https://example.org/resetPassword/123',
			'Install Client', 'https://nextcloud.com/install/#install-clients',
			'Set your password - text', 'Install Client - text'
		);
		$this->emailTemplate->addFooter();

		$expectedHTML = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email-custom.html');
		$this->assertSame($expectedHTML, $this->emailTemplate->renderHtml());
		$expectedTXT = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email-custom-text-alternative.txt');
		$this->assertSame($expectedTXT, $this->emailTemplate->renderText());
	}
}
