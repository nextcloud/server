<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Mail;

use OC\Mail\EMailTemplate;
use OC\Mail\Mailer;
use OC\Mail\Message;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\Events\BeforeMessageSent;
use OCP\Server;
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
	/** @var IEventDispatcher&MockObject */
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
	public static function sendmailModeProvider(): array {
		return [
			'smtp' => ['smtp', ' -bs'],
			'pipe' => ['pipe', ' -t -i'],
		];
	}

	/**
	 * @dataProvider sendmailModeProvider
	 * @param $sendmailMode
	 * @param $binaryParam
	 */
	public function testGetSendmailInstanceSendMail($sendmailMode, $binaryParam): void {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['mail_smtpmode', 'smtp', 'sendmail'],
				['mail_sendmailmode', 'smtp', $sendmailMode],
			]);

		$path = Server::get(IBinaryFinder::class)->findBinaryPath('sendmail');
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
	public function testGetSendmailInstanceSendMailQmail($sendmailMode, $binaryParam): void {
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

	public function testEventForNullTransport(): void {
		$this->config
			->expects($this->exactly(1))
			->method('getSystemValueString')
			->with('mail_smtpmode', 'smtp')
			->willReturn('null');

		$message = $this->createMock(Message::class);
		$message->expects($this->once())
			->method('getSymfonyEmail')
			->willReturn((new Email())->to('foo@bar.com')->from('bar@foo.com')->text(''));

		$event = new BeforeMessageSent($message);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->equalTo($event));

		$this->mailer->send($message);
	}

	public function testGetInstanceDefault(): void {
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

	public function testGetInstanceSendmail(): void {
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

	public function testEvents(): void {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
				['mail_smtpport', 25, 25],
			]);
		$this->mailer = $this->getMockBuilder(Mailer::class)
			->onlyMethods(['getInstance'])
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

	public function testCreateMessage(): void {
		$this->config
			->expects($this->any())
			->method('getSystemValueBool')
			->with('mail_send_plaintext_only', false)
			->willReturn(false);
		$this->assertInstanceOf('\OC\Mail\Message', $this->mailer->createMessage());
	}


	public function testSendInvalidMailException(): void {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['mail_smtphost', '127.0.0.1', '127.0.0.1'],
				['mail_smtpport', 25, 25],
				['mail_smtptimeout', 10, 10],
			]);
		$this->expectException(\Exception::class);

		/** @var Message&MockObject */
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
	public static function mailAddressProvider(): array {
		return [
			['lukas@owncloud.com', true, false],
			['lukas@localhost', true, false],
			['lukas@192.168.1.1', true, false],
			['lukas@éxämplè.com', true, false],
			['asdf', false, false],
			['', false, false],
			['lukas@owncloud.org@owncloud.com', false, false],
			['test@localhost', true, false],
			['test@localhost', false, true],
		];
	}

	/**
	 * @dataProvider mailAddressProvider
	 */
	public function testValidateMailAddress($email, $expected, $strict): void {
		$this->config
			->expects($this->atMost(1))
			->method('getAppValue')
			->with('core', 'enforce_strict_email_check')
			->willReturn($strict ? 'yes' : 'no');
		$this->assertSame($expected, $this->mailer->validateMailAddress($email));
	}

	public function testCreateEMailTemplate(): void {
		$this->config->method('getSystemValueString')
			->with('mail_template_class', '')
			->willReturnArgument(1);
		$this->config->method('getAppValue')
			->with('theming', 'logoDimensions', Mailer::DEFAULT_DIMENSIONS)
			->willReturn(Mailer::DEFAULT_DIMENSIONS);

		$this->assertSame(EMailTemplate::class, get_class($this->mailer->createEMailTemplate('tests.MailerTest')));
	}

	public function testStreamingOptions(): void {
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

	public function testStreamingOptionsWrongType(): void {
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

	public function testCaching(): void {
		$symfonyMailer1 = self::invokePrivate($this->mailer, 'getInstance');
		$symfonyMailer2 = self::invokePrivate($this->mailer, 'getInstance');
		self::assertSame($symfonyMailer1, $symfonyMailer2);
	}
}
