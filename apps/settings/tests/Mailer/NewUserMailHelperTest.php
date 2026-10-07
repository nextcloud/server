<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Mailer;

use OC\Mail\EMailTemplate;
use OC\Mail\Message;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\Headers\AutoSubmitted;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NewUserMailHelperTest extends TestCase {
	private Defaults&MockObject $defaults;
	private IURLGenerator&MockObject $urlGenerator;
	private IL10N&MockObject $l10n;
	private IFactory&MockObject $l10nFactory;
	private IMailer&MockObject $mailer;
	private ISecureRandom&MockObject $secureRandom;
	private ITimeFactory&MockObject $timeFactory;
	private IConfig&MockObject $config;
	private ICrypto&MockObject $crypto;
	private NewUserMailHelper $newUserMailHelper;

	protected function setUp(): void {
		parent::setUp();

		$this->defaults = $this->createMock(Defaults::class);
		$this->defaults->method('getLogo')
			->willReturn('myLogo');
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->mailer = $this->createMock(IMailer::class);
		$template = new EMailTemplate(
			$this->defaults,
			$this->urlGenerator,
			$this->l10nFactory,
			null,
			null,
			'test.TestTemplate',
			[]
		);
		$this->mailer->method('createEMailTemplate')
			->willReturn($template);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($arg) {
				switch ($arg) {
					case 'secret':
						return 'MyInstanceWideSecret';
					case 'customclient_desktop':
						return 'https://nextcloud.com/install/#install-clients';
				}
				return '';
			});
		$this->crypto = $this->createMock(ICrypto::class);
		$this->l10n->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->l10nFactory->method('get')
			->willReturnCallback(function ($text, $lang) {
				return $this->l10n;
			});

		$this->newUserMailHelper = new NewUserMailHelper(
			$this->defaults,
			$this->urlGenerator,
			$this->l10nFactory,
			$this->mailer,
			$this->secureRandom,
			$this->timeFactory,
			$this->config,
			$this->crypto,
			'no-reply@nextcloud.com'
		);
	}

	public function testGenerateTemplateWithPasswordResetToken(): void {
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with(21, ISecureRandom::CHAR_ALPHANUMERIC)
			->willReturn('MySuperLongSecureRandomToken');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(12345);
		/** @var IUser&MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->any())
			->method('getEmailAddress')
			->willReturn('recipient@example.com');
		$this->crypto
			->expects($this->once())
			->method('encrypt')
			->with('12345:MySuperLongSecureRandomToken', 'recipient@example.comMyInstanceWideSecret')
			->willReturn('TokenCiphertext');
		$user
			->expects($this->any())
			->method('getUID')
			->willReturn('john');
		$this->config
			->expects($this->once())
			->method('setUserValue')
			->with('john', 'core', 'lostpassword', 'TokenCiphertext');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', ['userId' => 'john', 'token' => 'MySuperLongSecureRandomToken'])
			->willReturn('https://example.com/resetPassword/MySuperLongSecureRandomToken');
		$user
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('john');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->atLeastOnce())
			->method('getDefaultColorPrimary')
			->willReturn('#00679e');
		$this->defaults
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
			->willReturn('#ffffff');

		$expectedHtmlBody = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="-webkit-font-smoothing:antialiased">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<title></title>
	<style type="text/css">
		:root{color-scheme:light dark;supported-color-schemes:light dark}
		body{margin:0;padding:0;width:100%!important}
		@media only screen and (max-width:640px){
			.nc-card{border-radius:0!important;width:100%!important}
			.nc-pad{padding-left:20px!important;padding-right:20px!important}
			.nc-button{display:block!important;margin:0 0 12px 0!important}
		}
		@media (prefers-color-scheme:dark){
			.nc-page{background:#181818!important}
			.nc-card{background:#222222!important}
			.nc-text{color:#ebebeb!important}
			.nc-muted{color:#a6a6a6!important}
			.nc-link{color:#ebebeb!important}
			.nc-border{border-color:#3b3b3b!important}
			.nc-note-neutral,.nc-secondary{background:#2c2c2c!important}
			.nc-note-info{background:#10303f!important}
			.nc-note-warning{background:#3a2e10!important}
			.nc-note-error{background:#3d1717!important}
		}
	</style>
</head>
<body class="nc-page" style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-text-size-adjust:100%;Margin:0;background:#f4f4f5;box-sizing:border-box;margin:0;min-width:100%;padding:0;width:100%!important">
<table role="presentation" class="nc-page" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;border-collapse:collapse;border-spacing:0;width:100%">
	<tr>
		<td align="center" style="padding:32px 0">
			<table role="presentation" class="nc-card" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-collapse:separate;border-radius:16px;border-spacing:0;max-width:600px;overflow:hidden;text-align:left;width:600px">				<tr>
					<td class="nc-pad" style="background:#00679e;padding:24px 36px">
						<img class="logo" src="" alt="TestCloud" style="-ms-interpolation-mode:bicubic;border:none;display:block;max-height:48px;max-width:200px;outline:0;text-decoration:none;width:auto">
					</td>
				</tr>				<tr>
					<td class="nc-pad" style="padding:32px 36px 8px">
						<h1 class="nc-text" style="Margin:0;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;line-height:1.3;margin:0">Welcome aboard</h1>
					</td>
				</tr>				<tr>
					<td class="nc-pad" style="padding:16px 36px 12px">						<p class="nc-muted" style="Margin:0 0 20px;color:#5c5c5c;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.5;margin:0 0 20px">Welcome to your TestCloud account, you can add, protect, and share your data.</p>						<p class="nc-muted" style="Margin:0 0 20px;color:#5c5c5c;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.5;margin:0 0 20px">Your Login is: john</p>						<table role="presentation" cellpadding="0" cellspacing="0" style="Margin:8px 0 20px;border-collapse:collapse;border-spacing:0;margin:8px 0 20px">
							<tr>
								<td>
									<a class="nc-button" href="https://example.com/resetPassword/MySuperLongSecureRandomToken" style="background:#00679e;border:1px solid #00679e;border-radius:8px;color:#ffffff;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 12px 12px 0;padding:13px 24px;text-align:center;text-decoration:none">Set your password</a><a class="nc-button nc-secondary nc-border nc-text" href="https://nextcloud.com/install/#install-clients" style="background:#ffffff;border:1px solid #d1d1d1;border-radius:8px;color:#222222;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 0 12px 0;padding:13px 24px;text-align:center;text-decoration:none">Install Client</a>
								</td>
							</tr>
						</table>					</td>
				</tr>				<tr>
					<td class="nc-pad nc-border nc-muted" style="border-top:1px solid #e5e5e5;color:#767676;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:13px;line-height:1.6;padding:24px 36px 32px">TestCloud<br>This is an automatically sent email, please do not reply.</td>
				</tr>			</table>
		</td>
	</tr>
</table>
</body>
</html>
EOF;
		$expectedTextBody = <<<EOF
Welcome aboard

Welcome to your TestCloud account, you can add, protect, and share your data.

Your Login is: john


Set your password: https://example.com/resetPassword/MySuperLongSecureRandomToken
Install Client: https://nextcloud.com/install/#install-clients


EOF;
		$expectedTextBody .= "\n-- \n";
		$expectedTextBody .= <<<EOF
TestCloud
This is an automatically sent email, please do not reply.
EOF;

		$result = $this->newUserMailHelper->generateTemplate($user, true);
		$this->assertEquals($expectedHtmlBody, $result->renderHtml());
		$this->assertEquals($expectedTextBody, $result->renderText());
		$this->assertSame('OC\Mail\EMailTemplate', get_class($result));
	}

	public function testGenerateTemplateWithoutPasswordResetToken(): void {
		$this->urlGenerator
			->expects($this->any())
			->method('getAbsoluteURL')
			->willReturnMap([
				['/','https://example.com/'],
				['myLogo',''],
			]);

		/** @var IUser&MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('John Doe');
		$user
			->expects($this->any())
			->method('getUID')
			->willReturn('john');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->atLeastOnce())
			->method('getDefaultColorPrimary')
			->willReturn('#00679e');
		$this->defaults
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
			->willReturn('#ffffff');

		$expectedHtmlBody = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="-webkit-font-smoothing:antialiased">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<title></title>
	<style type="text/css">
		:root{color-scheme:light dark;supported-color-schemes:light dark}
		body{margin:0;padding:0;width:100%!important}
		@media only screen and (max-width:640px){
			.nc-card{border-radius:0!important;width:100%!important}
			.nc-pad{padding-left:20px!important;padding-right:20px!important}
			.nc-button{display:block!important;margin:0 0 12px 0!important}
		}
		@media (prefers-color-scheme:dark){
			.nc-page{background:#181818!important}
			.nc-card{background:#222222!important}
			.nc-text{color:#ebebeb!important}
			.nc-muted{color:#a6a6a6!important}
			.nc-link{color:#ebebeb!important}
			.nc-border{border-color:#3b3b3b!important}
			.nc-note-neutral,.nc-secondary{background:#2c2c2c!important}
			.nc-note-info{background:#10303f!important}
			.nc-note-warning{background:#3a2e10!important}
			.nc-note-error{background:#3d1717!important}
		}
	</style>
</head>
<body class="nc-page" style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-text-size-adjust:100%;Margin:0;background:#f4f4f5;box-sizing:border-box;margin:0;min-width:100%;padding:0;width:100%!important">
<table role="presentation" class="nc-page" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;border-collapse:collapse;border-spacing:0;width:100%">
	<tr>
		<td align="center" style="padding:32px 0">
			<table role="presentation" class="nc-card" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-collapse:separate;border-radius:16px;border-spacing:0;max-width:600px;overflow:hidden;text-align:left;width:600px">				<tr>
					<td class="nc-pad" style="background:#00679e;padding:24px 36px">
						<img class="logo" src="" alt="TestCloud" style="-ms-interpolation-mode:bicubic;border:none;display:block;max-height:48px;max-width:200px;outline:0;text-decoration:none;width:auto">
					</td>
				</tr>				<tr>
					<td class="nc-pad" style="padding:32px 36px 8px">
						<h1 class="nc-text" style="Margin:0;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;line-height:1.3;margin:0">Welcome aboard John Doe</h1>
					</td>
				</tr>				<tr>
					<td class="nc-pad" style="padding:16px 36px 12px">						<p class="nc-muted" style="Margin:0 0 20px;color:#5c5c5c;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.5;margin:0 0 20px">Welcome to your TestCloud account, you can add, protect, and share your data.</p>						<p class="nc-muted" style="Margin:0 0 20px;color:#5c5c5c;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.5;margin:0 0 20px">Your Login is: john</p>						<table role="presentation" cellpadding="0" cellspacing="0" style="Margin:8px 0 20px;border-collapse:collapse;border-spacing:0;margin:8px 0 20px">
							<tr>
								<td>
									<a class="nc-button" href="https://example.com/" style="background:#00679e;border:1px solid #00679e;border-radius:8px;color:#ffffff;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 12px 12px 0;padding:13px 24px;text-align:center;text-decoration:none">Go to TestCloud</a><a class="nc-button nc-secondary nc-border nc-text" href="https://nextcloud.com/install/#install-clients" style="background:#ffffff;border:1px solid #d1d1d1;border-radius:8px;color:#222222;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 0 12px 0;padding:13px 24px;text-align:center;text-decoration:none">Install Client</a>
								</td>
							</tr>
						</table>					</td>
				</tr>				<tr>
					<td class="nc-pad nc-border nc-muted" style="border-top:1px solid #e5e5e5;color:#767676;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:13px;line-height:1.6;padding:24px 36px 32px">TestCloud<br>This is an automatically sent email, please do not reply.</td>
				</tr>			</table>
		</td>
	</tr>
</table>
</body>
</html>
EOF;
		$expectedTextBody = <<<EOF
Welcome aboard John Doe

Welcome to your TestCloud account, you can add, protect, and share your data.

Your Login is: john


Go to TestCloud: https://example.com/
Install Client: https://nextcloud.com/install/#install-clients


EOF;
		$expectedTextBody .= "\n-- \n";
		$expectedTextBody .= <<<EOF
TestCloud
This is an automatically sent email, please do not reply.
EOF;

		$result = $this->newUserMailHelper->generateTemplate($user, false);
		$this->assertEquals($expectedHtmlBody, $result->renderHtml());
		$this->assertEquals($expectedTextBody, $result->renderText());
		$this->assertSame('OC\Mail\EMailTemplate', get_class($result));
	}

	public function testGenerateTemplateWithoutUserId(): void {
		$this->urlGenerator
			->expects($this->any())
			->method('getAbsoluteURL')
			->willReturnMap([
				['/', 'https://example.com/'],
				['myLogo', ''],
			]);

		/** @var IUser&MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('John Doe');
		$user
			->expects($this->any())
			->method('getUID')
			->willReturn('john');
		$user
			->expects($this->atLeastOnce())
			->method('getBackendClassName')
			->willReturn('LDAP');
		$this->defaults
			->expects($this->any())
			->method('getName')
			->willReturn('TestCloud');
		$this->defaults
			->expects($this->atLeastOnce())
			->method('getDefaultColorPrimary')
			->willReturn('#00679e');
		$this->defaults
			->expects($this->atLeastOnce())
			->method('getDefaultTextColorPrimary')
			->willReturn('#ffffff');

		$expectedHtmlBody = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="-webkit-font-smoothing:antialiased">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<title></title>
	<style type="text/css">
		:root{color-scheme:light dark;supported-color-schemes:light dark}
		body{margin:0;padding:0;width:100%!important}
		@media only screen and (max-width:640px){
			.nc-card{border-radius:0!important;width:100%!important}
			.nc-pad{padding-left:20px!important;padding-right:20px!important}
			.nc-button{display:block!important;margin:0 0 12px 0!important}
		}
		@media (prefers-color-scheme:dark){
			.nc-page{background:#181818!important}
			.nc-card{background:#222222!important}
			.nc-text{color:#ebebeb!important}
			.nc-muted{color:#a6a6a6!important}
			.nc-link{color:#ebebeb!important}
			.nc-border{border-color:#3b3b3b!important}
			.nc-note-neutral,.nc-secondary{background:#2c2c2c!important}
			.nc-note-info{background:#10303f!important}
			.nc-note-warning{background:#3a2e10!important}
			.nc-note-error{background:#3d1717!important}
		}
	</style>
</head>
<body class="nc-page" style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-text-size-adjust:100%;Margin:0;background:#f4f4f5;box-sizing:border-box;margin:0;min-width:100%;padding:0;width:100%!important">
<table role="presentation" class="nc-page" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;border-collapse:collapse;border-spacing:0;width:100%">
	<tr>
		<td align="center" style="padding:32px 0">
			<table role="presentation" class="nc-card" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-collapse:separate;border-radius:16px;border-spacing:0;max-width:600px;overflow:hidden;text-align:left;width:600px">				<tr>
					<td class="nc-pad" style="background:#00679e;padding:24px 36px">
						<img class="logo" src="" alt="TestCloud" style="-ms-interpolation-mode:bicubic;border:none;display:block;max-height:48px;max-width:200px;outline:0;text-decoration:none;width:auto">
					</td>
				</tr>				<tr>
					<td class="nc-pad" style="padding:32px 36px 8px">
						<h1 class="nc-text" style="Margin:0;color:#222222;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;line-height:1.3;margin:0">Welcome aboard John Doe</h1>
					</td>
				</tr>				<tr>
					<td class="nc-pad" style="padding:16px 36px 12px">						<p class="nc-muted" style="Margin:0 0 20px;color:#5c5c5c;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.5;margin:0 0 20px">Welcome to your TestCloud account, you can add, protect, and share your data.</p>						<table role="presentation" cellpadding="0" cellspacing="0" style="Margin:8px 0 20px;border-collapse:collapse;border-spacing:0;margin:8px 0 20px">
							<tr>
								<td>
									<a class="nc-button" href="https://example.com/" style="background:#00679e;border:1px solid #00679e;border-radius:8px;color:#ffffff;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 12px 12px 0;padding:13px 24px;text-align:center;text-decoration:none">Go to TestCloud</a><a class="nc-button nc-secondary nc-border nc-text" href="https://nextcloud.com/install/#install-clients" style="background:#ffffff;border:1px solid #d1d1d1;border-radius:8px;color:#222222;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.2;margin:0 0 12px 0;padding:13px 24px;text-align:center;text-decoration:none">Install Client</a>
								</td>
							</tr>
						</table>					</td>
				</tr>				<tr>
					<td class="nc-pad nc-border nc-muted" style="border-top:1px solid #e5e5e5;color:#767676;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',Arial,sans-serif;font-size:13px;line-height:1.6;padding:24px 36px 32px">TestCloud<br>This is an automatically sent email, please do not reply.</td>
				</tr>			</table>
		</td>
	</tr>
</table>
</body>
</html>
EOF;
		$expectedTextBody = <<<EOF
Welcome aboard John Doe

Welcome to your TestCloud account, you can add, protect, and share your data.


Go to TestCloud: https://example.com/
Install Client: https://nextcloud.com/install/#install-clients


EOF;
		$expectedTextBody .= "\n-- \n";
		$expectedTextBody .= <<<EOF
TestCloud
This is an automatically sent email, please do not reply.
EOF;

		$result = $this->newUserMailHelper->generateTemplate($user, false);
		$this->assertEquals($expectedHtmlBody, $result->renderHtml());
		$this->assertEquals($expectedTextBody, $result->renderText());
		$this->assertSame('OC\Mail\EMailTemplate', get_class($result));
	}

	public function testSendMail(): void {
		/** @var IUser&MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->willReturn('recipient@example.com');
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('John Doe');
		/** @var IEMailTemplate&MockObject $emailTemplate */
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$message = $this->createMock(Message::class);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['recipient@example.com' => 'John Doe']);
		$message
			->expects($this->once())
			->method('setFrom')
			->with(['no-reply@nextcloud.com' => 'TestCloud']);
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($emailTemplate);
		$message
			->expects($this->once())
			->method('setAutoSubmitted')
			->with(AutoSubmitted::VALUE_AUTO_GENERATED);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('TestCloud');
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);

		$this->newUserMailHelper->sendMail($user, $emailTemplate);
	}
}
