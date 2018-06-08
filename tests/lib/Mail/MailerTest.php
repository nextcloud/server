<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Mail;

use OC\Mail\EMailTemplate;
use OC\Mail\Mailer;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use Test\TestCase;

class MailerTest extends TestCase {
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var Mailer */
	private $mailer;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->mailer = new Mailer(
			$this->config,
			$this->logger,
			$this->defaults,
			$this->urlGenerator,
			$this->l10n
		);
	}

	public function testGetSendMailInstanceSendMail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'smtp')
			->will($this->returnValue('sendmail'));

		$this->assertEquals(new \Swift_SendmailTransport('/usr/sbin/sendmail -bs'), self::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetSendMailInstanceSendMailQmail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'smtp')
			->will($this->returnValue('qmail'));

		$this->assertEquals(new \Swift_SendmailTransport('/var/qmail/bin/sendmail -bs'), self::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetInstanceDefault() {
		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		$this->assertInstanceOf(\Swift_Mailer::class, $mailer);
		$this->assertInstanceOf(\Swift_SmtpTransport::class, $mailer->getTransport());
	}

	public function testGetInstanceSendmail() {
		$this->config
			->method('getSystemValue')
			->with('mail_smtpmode', 'smtp')
			->willReturn('sendmail');

		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		$this->assertInstanceOf(\Swift_Mailer::class, $mailer);
		$this->assertInstanceOf(\Swift_SendmailTransport::class, $mailer->getTransport());
	}

	public function testCreateMessage() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->with('mail_send_plaintext_only', false)
			->will($this->returnValue(false));
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

	public function testCreateEMailTemplate() {
		$this->config->method('getSystemValue')
			->with('mail_template_class', '')
			->willReturnArgument(1);

		$this->assertSame(EMailTemplate::class, get_class($this->mailer->createEMailTemplate('tests.MailerTest')));
	}
}
