<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Tests\Settings\Controller;

use OC\Mail\Message;
use OC\Settings\Controller\MailSettingsController;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Mail\IMailer;

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
//		$this->mailer = $this->getMockBuilder(IMailer::class)
//			->setMethods(['send'])
//			->getMock();
		$this->mailController = new MailSettingsController(
			'settings',
			$this->createMock(IRequest::class),
			$this->l,
			$this->config,
			$this->userSession,
			$this->mailer,
			'no-reply@owncloud.com'
		);
	}

	public function testSetMailSettings() {
		$this->l
			->expects($this->exactly(2))
			->method('t')
			->will($this->returnValue('Saved'));

		$this->config->expects($this->exactly(2))
			->method('setSystemValues')
			->withConsecutive(
				[[
					'mail_domain' => 'owncloud.com',
					'mail_from_address' => 'demo@owncloud.com',
					'mail_smtpmode' => 'smtp',
					'mail_smtpsecure' => 'ssl',
					'mail_smtphost' => 'mx.owncloud.org',
					'mail_smtpauthtype' => 'NTLM',
					'mail_smtpauth' => 1,
					'mail_smtpport' => '25',
				]],
				[[
					'mail_domain' => 'owncloud.com',
					'mail_from_address' => 'demo@owncloud.com',
					'mail_smtpmode' => 'smtp',
					'mail_smtpsecure' => 'ssl',
					'mail_smtphost' => 'mx.owncloud.org',
					'mail_smtpauthtype' => 'NTLM',
					'mail_smtpauth' => null,
					'mail_smtpport' => '25',
					'mail_smtpname' => null,
					'mail_smtppassword' => null,
				]]
			);

		// With authentication
		$response = $this->mailController->setMailSettings(
			'owncloud.com',
			'demo@owncloud.com',
			'smtp',
			'ssl',
			'mx.owncloud.org',
			'NTLM',
			1,
			'25'
		);
		$expectedResponse = array('data' => array('message' =>'Saved'), 'status' => 'success');
		$this->assertSame($expectedResponse, $response);

		// Without authentication (testing the deletion of the stored password)
		$response = $this->mailController->setMailSettings(
			'owncloud.com',
			'demo@owncloud.com',
			'smtp',
			'ssl',
			'mx.owncloud.org',
			'NTLM',
			0,
			'25'
		);
		$expectedResponse = array('data' => array('message' =>'Saved'), 'status' => 'success');
		$this->assertSame($expectedResponse, $response);

	}

	public function testStoreCredentials() {
		$this->l
			->expects($this->once())
			->method('t')
			->will($this->returnValue('Saved'));

		$this->config
			->expects($this->once())
			->method('setSystemValues')
			->with([
				'mail_smtpname' => 'UsernameToStore',
				'mail_smtppassword' => 'PasswordToStore',
			]);

		$response = $this->mailController->storeCredentials('UsernameToStore', 'PasswordToStore');
		$expectedResponse = array('data' => array('message' =>'Saved'), 'status' => 'success');

		$this->assertSame($expectedResponse, $response);
	}

	public function testSendTestMail() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('Werner'));
		$user->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue('Werner Brösel'));

		$this->l
			->expects($this->any())
			->method('t')
			->will(
				$this->returnValueMap(
					array(
						array('You need to set your user email before being able to send test emails.', array(),
							'You need to set your user email before being able to send test emails.'),
						array('A problem occurred while sending the e-mail. Please revisit your settings.', array(),
							'A problem occurred while sending the e-mail. Please revisit your settings.'),
						array('Email sent', array(), 'Email sent'),
						array('test email settings', array(), 'test email settings'),
						array('If you received this email, the settings seem to be correct.', array(),
							'If you received this email, the settings seem to be correct.')
					)
				));
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->will($this->returnValue($user));

		// Ensure that it fails when no mail address has been specified
		$response = $this->mailController->sendTestMail();
		$expectedResponse = array('data' => array('message' =>'You need to set your user email before being able to send test emails.'), 'status' => 'error');
		$this->assertSame($expectedResponse, $response);

		// If no exception is thrown it should work
		$this->config
			->expects($this->any())
			->method('getUserValue')
			->will($this->returnValue('mail@example.invalid'));
		$this->mailer->expects($this->once())
			->method('createMessage')
			->willReturn($this->createMock(Message::class));
		$response = $this->mailController->sendTestMail();
		$expectedResponse = array('data' => array('message' =>'Email sent'), 'status' => 'success');
		$this->assertSame($expectedResponse, $response);
	}

}
