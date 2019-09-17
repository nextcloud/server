<?php
/**
 * @copyright 2014 Lukas Reschke lukas@nextcloud.com
 * @copyright Copyright (c) 2017  Joas Schilling <coding@schilljs.com>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Settings\Tests\Controller;

use OC\Mail\Message;
use OCA\Settings\Controller\MailSettingsController;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OC\User\User;

/**
 * @package Tests\Settings\Controller
 */
class MailSettingsControllerTest extends \Test\TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var IMailer|\PHPUnit_Framework_MockObject_MockObject */
	private $mailer;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l;

	/** @var MailSettingsController */
	private $mailController;

	protected function setUp() {
		parent::setUp();

		$this->l = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->mailer = $this->createMock(IMailer::class);
		/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->createMock(IRequest::class);
		$this->mailController = new MailSettingsController(
			'settings',
			$request,
			$this->l,
			$this->config,
			$this->userSession,
			$this->mailer,
			'no-reply@nextcloud.com'
		);
	}

	public function testSetMailSettings() {
		$this->config->expects($this->exactly(2))
			->method('setSystemValues')
			->withConsecutive(
				[[
					'mail_domain' => 'nextcloud.com',
					'mail_from_address' => 'demo@nextcloud.com',
					'mail_smtpmode' => 'smtp',
					'mail_smtpsecure' => 'ssl',
					'mail_smtphost' => 'mx.nextcloud.org',
					'mail_smtpauthtype' => 'NTLM',
					'mail_smtpauth' => 1,
					'mail_smtpport' => '25',
					'mail_sendmailmode' => null,
				]],
				[[
					'mail_domain' => 'nextcloud.com',
					'mail_from_address' => 'demo@nextcloud.com',
					'mail_smtpmode' => 'smtp',
					'mail_smtpsecure' => 'ssl',
					'mail_smtphost' => 'mx.nextcloud.org',
					'mail_smtpauthtype' => 'NTLM',
					'mail_smtpauth' => null,
					'mail_smtpport' => '25',
					'mail_smtpname' => null,
					'mail_smtppassword' => null,
					'mail_sendmailmode' => null,
				]]
			);

		// With authentication
		$response = $this->mailController->setMailSettings(
			'nextcloud.com',
			'demo@nextcloud.com',
			'smtp',
			'ssl',
			'mx.nextcloud.org',
			'NTLM',
			1,
			'25',
			null
		);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		// Without authentication (testing the deletion of the stored password)
		$response = $this->mailController->setMailSettings(
			'nextcloud.com',
			'demo@nextcloud.com',
			'smtp',
			'ssl',
			'mx.nextcloud.org',
			'NTLM',
			0,
			'25',
			null
		);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());

	}

	public function testStoreCredentials() {
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

	public function testSendTestMail() {
		$user = $this->createMock(User::class);
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('Werner'));
		$user->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue('Werner Brösel'));

		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->will($this->returnValue($user));

		// Ensure that it fails when no mail address has been specified
		$response = $this->mailController->sendTestMail();
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame('You need to set your user email before being able to send test emails.', $response->getData());

		// If no exception is thrown it should work
		$this->config
			->expects($this->any())
			->method('getUserValue')
			->will($this->returnValue('mail@example.invalid'));
		$this->mailer->expects($this->once())
			->method('createMessage')
			->willReturn($this->createMock(Message::class));
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$response = $this->mailController->sendTestMail();
		$this->assertSame(Http::STATUS_OK, $response->getStatus(), $response->getData());
	}

}
