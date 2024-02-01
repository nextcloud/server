<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 *
 * @author Arne Hamann <github@arne.email>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Mail;

use OC\Mail\EMailTemplate;
use OC\Mail\Mailer;
use OC\Mail\Message;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\Events\BeforeMessageSent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Test\TestCase;

class MailerTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;
	/** @var Defaults|MockObject */
	private $defaults;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var IL10N|MockObject */
	private $l10n;
	/** @var Mailer */
	private $mailer;
	/** @var IEventDispatcher */
	private $dispatcher;


	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->mailer = new Mailer(
			$this->config,
			$this->logger,
			$this->defaults,
			$this->urlGenerator,
			$this->l10n,
			$this->dispatcher,
			$this->createMock(IFactory::class)
		);
	}

	/**
	 * @return array
	 */
	public function sendmailModeProvider(): array {
		return [
			'smtp' => ['smtp', ' -bs'],
			'pipe' => ['pipe', ' -t'],
		];
	}

	/**
	 * @dataProvider sendmailModeProvider
	 * @param $sendmailMode
	 * @param $binaryParam
	 */
	public function testGetSendmailInstanceSendMail($sendmailMode, $binaryParam) {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'sendmail'],
				['mail_sendmailmode', 'smtp', $sendmailMode],
			]);

		$path = \OC_Helper::findBinaryPath('sendmail');
		if ($path === false) {
			$path = '/usr/sbin/sendmail';
		}

		$expected = new SendmailTransport($path . $binaryParam, null, $this->logger);
		$this->assertEquals($expected, self::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	/**
	 * @dataProvider sendmailModeProvider
	 * @param $sendmailMode
	 * @param $binaryParam
	 */
	public function testGetSendmailInstanceSendMailQmail($sendmailMode, $binaryParam) {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'qmail'],
				['mail_sendmailmode', 'smtp', $sendmailMode],
			]);

		$sendmail = new SendmailTransport('/var/qmail/bin/sendmail' . $binaryParam, null, $this->logger);
		$this->assertEquals($sendmail, self::invokePrivate($this->mailer, 'getSendMailInstance'));
	}

	public function testGetInstanceDefault() {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);
		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		$this->assertInstanceOf(SymfonyMailer::class, $mailer);
		$transport = self::invokePrivate($mailer, 'transport');
		$this->assertInstanceOf(EsmtpTransport::class, $transport);
	}

	public function testGetInstanceSendmail() {
		$this->config
			->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'sendmail'],
				['mail_sendmailmode', 'smtp', 'smtp'],
			]);

		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		$this->assertInstanceOf(SymfonyMailer::class, $mailer);
		$transport = self::invokePrivate($mailer, 'transport');
		$this->assertInstanceOf(SendmailTransport::class, $transport);
	}

	public function testEvents() {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
				['mail_smtpport', 25, 25],
			]);
		$this->mailer = $this->getMockBuilder(Mailer::class)
			->setMethods(['getInstance'])
			->setConstructorArgs(
				[
					$this->config,
					$this->logger,
					$this->defaults,
					$this->urlGenerator,
					$this->l10n,
					$this->dispatcher,
					$this->createMock(IFactory::class)
				]
			)
			->getMock();

		$message = $this->createMock(Message::class);

		$event = new BeforeMessageSent($message);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->equalTo($event));

		$this->mailer->send($message);
	}

	public function testCreateMessage() {
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('mail_send_plaintext_only', false)
			->willReturn(false);
		$this->assertInstanceOf('\OC\Mail\Message', $this->mailer->createMessage());
	}


	public function testSendInvalidMailException() {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);
		$this->expectException(\Exception::class);

		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message->expects($this->once())
			->method('getSymfonyEmail')
			->willReturn(new Email());

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
			['', false],
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
		$this->config->method('getSystemValueString')
			->with('mail_template_class', '')
			->willReturnArgument(1);

		$this->assertSame(EMailTemplate::class, get_class($this->mailer->createEMailTemplate('tests.MailerTest')));
	}

	public function testStreamingOptions() {
		$this->config->method('getSystemValue')
			->willReturnMap([
				['mail_smtpstreamoptions', [], ['foo' => 1]],
			]);
		$this->config->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'smtp'],
				['overwrite.cli.url', '', ''],
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
			]);
		$this->config->method('getSystemValueInt')
			->willReturnMap([
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);
		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		/** @var EsmtpTransport $transport */
		$transport = self::invokePrivate($mailer, 'transport');
		$this->assertInstanceOf(EsmtpTransport::class, $transport);
		$this->assertEquals(1, count($transport->getStream()->getStreamOptions()));
		$this->assertTrue(isset($transport->getStream()->getStreamOptions()['foo']));
	}

	public function testStreamingOptionsWrongType() {
		$this->config->method('getSystemValue')
			->willReturnMap([
				['mail_smtpstreamoptions', [], 'bar'],
			]);
		$this->config->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'smtp'],
				['overwrite.cli.url', '', ''],
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
			]);
		$this->config->method('getSystemValueInt')
			->willReturnMap([
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);

		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		/** @var EsmtpTransport $transport */
		$transport = self::invokePrivate($mailer, 'transport');
		$this->assertInstanceOf(EsmtpTransport::class, $transport);
		$this->assertEquals(0, count($transport->getStream()->getStreamOptions()));
	}

	public function testLocalDomain(): void {
		$this->config->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'smtp'],
				['overwrite.cli.url', '', 'https://some.valid.url.com:8080'],
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
			]);
		$this->config->method('getSystemValueInt')
			->willReturnMap([
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);

		/** @var SymfonyMailer $mailer */
		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		self::assertInstanceOf(SymfonyMailer::class, $mailer);

		/** @var EsmtpTransport $transport */
		$transport = self::invokePrivate($mailer, 'transport');
		self::assertInstanceOf(EsmtpTransport::class, $transport);
		self::assertEquals('some.valid.url.com', $transport->getLocalDomain());
	}

	public function testLocalDomainInvalidUrl(): void {
		$this->config->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'smtp'],
				['overwrite.cli.url', '', 'https:only.slash.does.not.work:8080'],
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
			]);
		$this->config->method('getSystemValueInt')
			->willReturnMap([
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);

		/** @var SymfonyMailer $mailer */
		$mailer = self::invokePrivate($this->mailer, 'getInstance');
		self::assertInstanceOf(SymfonyMailer::class, $mailer);

		/** @var EsmtpTransport $transport */
		$transport = self::invokePrivate($mailer, 'transport');
		self::assertInstanceOf(EsmtpTransport::class, $transport);
		self::assertEquals('[127.0.0.1]', $transport->getLocalDomain());
	}
}
