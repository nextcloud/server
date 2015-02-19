<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;
use OC\Mail\Mailer;
use OCP\IConfig;
use OC_Defaults;

class MailerTest extends TestCase {
	/** @var IConfig */
	private $config;
	/** @var OC_Defaults */
	private $defaults;
	/** @var Mailer */
	private $mailer;

	function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->defaults = $this->getMockBuilder('\OC_Defaults')
			->disableOriginalConstructor()->getMock();
		$this->mailer = new Mailer($this->config, $this->defaults);
	}

	public function testGetMailInstance() {
		$this->assertEquals(\Swift_MailTransport::newInstance(), \Test_Helper::invokePrivate($this->mailer, 'getMailinstance'));
	}

	public function testGetSendMailInstanceSendMail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'sendmail')
			->will($this->returnValue('sendmail'));

		$this->assertEquals(\Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs'), \Test_Helper::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetSendMailInstanceSendMailQmail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'sendmail')
			->will($this->returnValue('qmail'));

		$this->assertEquals(\Swift_SendmailTransport::newInstance('/var/qmail/bin/sendmail -bs'), \Test_Helper::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetSmtpInstanceDefaults() {
		$expected = \Swift_SmtpTransport::newInstance();
		$expected->setHost('127.0.0.1');
		$expected->setTimeout(10);
		$expected->setPort(25);

		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnArgument(1));

		$this->assertEquals($expected, \Test_Helper::invokePrivate($this->mailer, 'getSmtpInstance'));
	}

	public function testGetInstanceDefault() {
		$this->assertInstanceOf('\Swift_MailTransport', \Test_Helper::invokePrivate($this->mailer, 'getInstance'));
	}

	public function testGetInstancePhp() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('php'));

		$this->assertInstanceOf('\Swift_MailTransport', \Test_Helper::invokePrivate($this->mailer, 'getInstance'));
	}

	public function testGetInstanceSmtp() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('smtp'));

		$this->assertInstanceOf('\Swift_SmtpTransport', \Test_Helper::invokePrivate($this->mailer, 'getInstance'));
	}

	public function testGetInstanceSendmail() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('sendmail'));

		$this->assertInstanceOf('\Swift_SendmailTransport', \Test_Helper::invokePrivate($this->mailer, 'getInstance'));
	}

	public function testCreateMessage() {
		$this->assertInstanceOf('\OC\Mail\Message', $this->mailer->createMessage());
	}

	/**
	 * @expectedException \Exception
	 */
	public function testSendInvalidMailException() {
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message->expects($this->once())
			->method('getSwiftMessage')
			->will($this->returnValue(new \Swift_Message()));

		$this->mailer->send($message);
	}

	/**
	 * @return array
	 */
	public function mailAddressProvider() {
		return [
			['lukas@owncloud.com', true],
			['lukas@localhost', true],
			['lukas@192.168.1.1', true],
			['lukas@éxämplè.com', true],
			['asdf', false],
			['lukas@owncloud.org@owncloud.com', false],
		];
	}

	/**
	 * @dataProvider mailAddressProvider
	 */
	public function testValidateMailAddress($email, $expected) {
		$this->assertSame($expected, $this->mailer->validateMailAddress($email));
	}

}
