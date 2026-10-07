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
use OCP\Mail\EMailDetails;
use OCP\Mail\IEMailTemplate;
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

	#[\Override]
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
			->expects($this->atLeastOnce())
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
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
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
			->expects($this->atLeastOnce())
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
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
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
			->expects($this->atLeastOnce())
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
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
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
			->expects($this->atLeastOnce())
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
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
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

	public function testEMailTemplateEmbedsLogo(): void {
		$this->defaults->method('getDefaultColorPrimary')->willReturn('#0082c9');
		$this->defaults->method('getName')->willReturn('TestCloud');
		$this->defaults->method('getLogoImage')
			->willReturn(['content' => 'PNGDATA', 'mimeType' => 'image/png']);
		$this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

		$this->emailTemplate->addHeader();

		$this->assertStringContainsString('src="cid:logo"', $this->emailTemplate->renderHtml());
		$this->assertSame(
			[['name' => 'logo', 'content' => 'PNGDATA', 'mimeType' => 'image/png']],
			$this->emailTemplate->getInlineImages()
		);
	}

	private function mockThemingColors(): void {
		$this->defaults->method('getDefaultColorPrimary')->willReturn('#0082c9');
		$this->defaults->method('getDefaultTextColorPrimary')->willReturn('#ffffff');
		$this->defaults->method('getName')->willReturn('TestCloud');
		$this->defaults->method('getLogo')->willReturn('/img/logo-mail-header.png');
		$this->urlGenerator->method('getAbsoluteURL')
			->willReturn('https://example.org/img/logo-mail-header.png');
	}

	public function testEMailTemplateBlocks(): void {
		$this->mockThemingColors();

		$details = new EMailDetails('Q3 board review');
		$details->setSubtitle('Thursday, October 15, 2026')
			->setDateBadge('Oct', '15');
		$details->addRow('When')
			->text('14:00 - 15:30 (Europe/Berlin)');
		$details->addRow('Where')
			->text('Meeting room 2')
			->link('Join in Nextcloud Talk', 'https://example.org/call/abc?x=1&y=2');
		$details->addRow('Attendees')
			->text('Alice Martin (organizer)')
			->text('You')
			->muted('and 3 others');

		$this->emailTemplate->addHeader();
		$this->emailTemplate->addBodySender('Alice Martin', 'alice.martin@example.org');
		$this->emailTemplate->addHeading('Alice Martin invited you to an event');
		$this->emailTemplate->addBodyDetails($details);
		$this->emailTemplate->addBodyNote("We'll go through the Q3 numbers.\nPlease review the report beforehand.", 'Description');
		$this->emailTemplate->addBodyNote('The password is sent in a separate email.', 'This share is password protected.', IEMailTemplate::NOTE_WARNING);
		$this->emailTemplate->addBodyButtons([
			['text' => 'Accept', 'url' => 'https://example.org/invitation/accept'],
			['text' => 'Maybe', 'url' => 'https://example.org/invitation/tentative'],
			['text' => 'Decline', 'url' => 'https://example.org/invitation/decline'],
		], 'Will you attend?');
		$this->emailTemplate->addFooter('TestCloud - A safe home for your data');

		$expectedHTML = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/blocks-email.html');
		$this->assertSame($expectedHTML, $this->emailTemplate->renderHtml());
		$expectedTXT = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/blocks-email.txt');
		$this->assertSame($expectedTXT, $this->emailTemplate->renderText());
	}

	public function testEMailTemplateBlocksEscapeHtml(): void {
		$this->mockThemingColors();

		$details = (new EMailDetails('<b>title</b>'))
			->setSubtitle('<i>subtitle</i>')
			->setInitials('<script>');
		$details->addRow('<u>label</u>')
			->text('<img src=x>')
			->link('<em>link</em>', 'https://example.org/"onmouseover="alert(1)')
			->muted('<s>muted</s>');

		$this->emailTemplate->addBodySender('<b>Eve</b>', '<i>eve@example.org</i>');
		$this->emailTemplate->addBodyDetails($details);
		$this->emailTemplate->addBodyNote('<p>note</p>', '<b>label</b>');
		$this->emailTemplate->addBodyNote('<p>note</p>', '<b>title</b>', IEMailTemplate::NOTE_ERROR);
		$this->emailTemplate->addBodyButtons([['text' => '<b>Go</b>', 'url' => 'https://example.org/"><script>']], '<b>label</b>');

		$html = $this->emailTemplate->renderHtml();
		foreach (['<b>', '<i>', '<u>', '<s>', '<em>', '<img', '<script>', '<p>', '"onmouseover'] as $raw) {
			$this->assertStringNotContainsString($raw, $html);
		}
		$this->assertStringContainsString('&lt;b&gt;Eve&lt;/b&gt;', $html);
		$this->assertStringContainsString('https://example.org/&quot;onmouseover=&quot;alert(1)', $html);

		$text = $this->emailTemplate->renderText();
		$this->assertStringContainsString('<b>Eve</b> (<i>eve@example.org</i>)', $text);
		$this->assertStringContainsString('<b>title</b> <p>note</p>', $text);
	}

	public function testEMailTemplateDetailsLeadingVisual(): void {
		$this->mockThemingColors();

		$details = (new EMailDetails('Board prep'))->setInitials('board prep');
		$this->emailTemplate->addBodyDetails($details);
		$html = $this->emailTemplate->renderHtml();

		$this->assertStringContainsString('>BP</div>', $html);
		$this->assertStringNotContainsString('text-transform:uppercase', $html);
		// No row, so no separator under the title
		$this->assertStringNotContainsString('border-bottom:1px solid #ddd;', $html);
		$this->assertSame('Board prep' . PHP_EOL . PHP_EOL, $this->emailTemplate->renderText());
	}

	public function testEMailTemplateDetailsLinkWithUrlAsText(): void {
		$this->mockThemingColors();

		$details = new EMailDetails('Event');
		$details->addRow('Link')->link('https://example.org/event', 'https://example.org/event');
		$this->emailTemplate->addBodyDetails($details);

		$this->assertSame('Event' . PHP_EOL . '  Link: https://example.org/event' . PHP_EOL . PHP_EOL, $this->emailTemplate->renderText());
	}

	public function testEMailTemplateBlocksIgnoredAfterFooter(): void {
		$this->mockThemingColors();

		$this->emailTemplate->addFooter('Footer');
		$html = $this->emailTemplate->renderHtml();
		$text = $this->emailTemplate->renderText();

		$this->emailTemplate->addBodySender('Alice Martin');
		$this->emailTemplate->addBodyNote('Note');
		$this->emailTemplate->addBodyDetails(new EMailDetails('Details'));
		$this->emailTemplate->addBodyButtons([['text' => 'Open', 'url' => 'https://example.org']]);

		$this->assertSame($html, $this->emailTemplate->renderHtml());
		$this->assertSame($text, $this->emailTemplate->renderText());
	}
}
