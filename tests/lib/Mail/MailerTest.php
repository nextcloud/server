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

	public function testGetMailInstance() {
		$this->assertEquals(\Swift_MailTransport::newInstance(), self::invokePrivate($this->mailer, 'getMailinstance'));
	}

	public function testGetSendMailInstanceSendMail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'php')
			->will($this->returnValue('sendmail'));

		$this->assertEquals(\Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs'), self::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetSendMailInstanceSendMailQmail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('mail_smtpmode', 'php')
			->will($this->returnValue('qmail'));

		$this->assertEquals(\Swift_SendmailTransport::newInstance('/var/qmail/bin/sendmail -bs'), self::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetInstanceDefault() {
		$this->assertInstanceOf('\Swift_MailTransport', self::invokePrivate($this->mailer, 'getInstance'));
	}

	public function testGetInstancePhp() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('php'));

		$this->assertInstanceOf('\Swift_MailTransport', self::invokePrivate($this->mailer, 'getInstance'));
	}

	public function testGetInstanceSendmail() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->will($this->returnValue('sendmail'));

		$this->assertInstanceOf('\Swift_Mailer', self::invokePrivate($this->mailer, 'getInstance'));
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
		$message->expects($this->once())
			->method('getToFingerprints')
			->willReturn(array());
		$message->expects($this->once())
			->method('getCCFingerprints')
			->willReturn(array());
		$message->expects($this->once())
			->method('getBccFingerprints')
			->willReturn(array());
		$message->expects($this->once())
			->method('getFromFingerprints')
			->willReturn(array());

		$message->expects($this->once())
			->method('getTo')
			->willReturn(array());
		$message->expects($this->once())
			->method('getCc')
			->willReturn(array());
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(array());
		$message->expects($this->once())
			->method('getFrom')
			->willReturn(array());

		$this->mailer->send($message);
	}

	/**
	 * @dataProvider dataConvertGpgMessage
	 */
	public function testConvertGpgMessage($to, $cc, $bcc, $from, $to_fingerprints, $cc_fingerprints, $bcc_fingerprints, $from_fingerprints, $expect_encrypt, $expect_sign) {
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message->expects($this->once())
			->method('getToFingerprints')
			->willReturn($to_fingerprints);
		$message->expects($this->once())
			->method('getTo')
			->willReturn($to);

		$message->expects($this->once())
			->method('getCCFingerprints')
			->willReturn($cc_fingerprints);
		$message->expects($this->once())
			->method('getCc')
			->willReturn($cc);

		$message->expects($this->once())
			->method('getBccFingerprints')
			->willReturn($bcc_fingerprints);
		$message->expects($this->once())
			->method('getBcc')
			->willReturn($bcc);

		$message->expects($this->once())
			->method('getFromFingerprints')
			->willReturn($from_fingerprints);
		$message->expects($this->any())
			->method('getFrom')
			->willReturn($from);

		if($expect_encrypt && $expect_sign) {
			$message->expects($this->once())
				->method('encryptsign');
			$message->expects($this->never())
				->method('encrypt');
			$message->expects($this->never())
				->method('sign');
		}

		if($expect_encrypt && !$expect_sign) {
			$message->expects($this->never())
				->method('encryptsign');
			$message->expects($this->once())
				->method('encrypt');
			$message->expects($this->never())
				->method('sign');
		}

		if(!$expect_encrypt && $expect_sign) {
			$message->expects($this->never())
				->method('encryptsign');
			$message->expects($this->never())
				->method('encrypt');
			$message->expects($this->once())
				->method('sign');
		}

		if(!$expect_encrypt && !$expect_sign) {
			$message->expects($this->never())
				->method('encryptsign');
			$message->expects($this->never())
				->method('encrypt');
			$message->expects($this->never())
				->method('sign');
		}

		$this->assertInstanceOf('\OC\Mail\Message', self::invokePrivate($this->mailer, 'convertGpgMessage', [$message]));

	}

	public function dataConvertGpgMessage(){

		return [
			'encryptsign' => [
				['test@nextcloud.invalid'], [], [], ['nextcloud@test.invalid'],
				['abcdefghijklmnop'], [], [], ['abdefglasdlfkhöi'],
				true,
				true
			],

			'sign' => [
				['test@nextcloud.invalid'], [], [], ['nextcloud@test.invalid'],
				[], [], [], ['abdefglasdlfkhöi'],
				false,
				true
			],

			'encrypt' => [
				['test@nextcloud.invalid'], [], [], ['nextcloud@test.invalid'],
				['abcdefghijklmnop'], [], [], [],
				true,
				false
			],

			'none' => [
				['test@nextcloud.invalid'], [], [], ['nextcloud@test.invalid'],
				[], [], [], [],
				false,
				false
			],

			'more resivers than keys' => [
				['test@nextcloud.invalid'], ['test2@nextcloud.invalid'], [], ['nextcloud@test.invalid'],
				['abcdefghijklmnop'], [], [], ['asödkflnasdvölsd'],
				false,
				true
			],

		];
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
		$this->assertSame(EMailTemplate::class, get_class($this->mailer->createEMailTemplate('tests.MailerTest')));
	}
}
