<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Mail;

use OC\Mail\Message;
use OCP\Mail\Headers\AutoSubmitted;
use OCP\Mail\IEMailTemplate;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MessageTest extends TestCase {
	/** @var Email */
	private $symfonyEmail;
	/** @var Message */
	private $message;

	/**
	 * @return array
	 */
	public function mailAddressProvider() {
		return [
			[
				['lukas@owncloud.com' => 'Lukas Reschke'],
				[new Address('lukas@owncloud.com', 'Lukas Reschke')]
			],
			[
				[
					'lukas@owncloud.com' => 'Lukas Reschke',
					'lukas@öwnclöüd.com',
					'lukäs@owncloud.örg' => 'Lükäs Réschke'
				],
				[
					new Address('lukas@owncloud.com', 'Lukas Reschke'),
					new Address('lukas@öwnclöüd.com'),
					new Address('lukäs@owncloud.örg', 'Lükäs Réschke')
				]
			],
			[
				['lukas@öwnclöüd.com'],
				[new Address('lukas@öwnclöüd.com')]
			],
		];
	}

	/**
	 * @return array
	 */
	public function getMailAddressProvider() {
		return [
			[[], []],
			[['lukas@owncloud.com' => 'Lukas Reschke'], ['lukas@owncloud.com' => 'Lukas Reschke']],
		];
	}

	protected function setUp(): void {
		parent::setUp();

		$this->symfonyEmail = $this->getMockBuilder(Email::class)
			->disableOriginalConstructor()->getMock();

		$this->message = new Message($this->symfonyEmail, false);
	}

	/**
	 * @dataProvider mailAddressProvider
	 *
	 * @param string $unconverted
	 * @param string $expected
	 */
	public function testConvertAddresses($unconverted, $expected) {
		$this->assertEquals($expected, self::invokePrivate($this->message, 'convertAddresses', [$unconverted]));
	}

	public function testSetRecipients(): void {
		$this->message = $this->message->setFrom(['pierres-general-store@stardewvalley.com' => 'Pierres General Store']);
		$this->message = $this->message->setTo(['lewis-tent@stardewvalley.com' => "Lewis' Tent Life"]);
		$this->message = $this->message->setReplyTo(['penny@stardewvalley-library.co.edu' => 'Penny']);
		$this->message = $this->message->setCc(['gunther@stardewvalley-library.co.edu' => 'Gunther']);
		$this->message = $this->message->setBcc(['pam@stardewvalley-bus.com' => 'Pam']);

		$this->symfonyEmail
			->expects($this->once())
			->method('from')
			->willReturn(new Address('pierres-general-store@stardewvalley.com', 'Pierres General Store'));
		$this->symfonyEmail
			->expects($this->once())
			->method('to')
			->willReturn(new Address('lewis-tent@stardewvalley.com', "Lewis' Tent Life"));
		$this->symfonyEmail
			->expects($this->once())
			->method('replyTo')
			->willReturn(new Address('penny@stardewvalley-library.co.edu', 'Penny'));
		$this->symfonyEmail
			->expects($this->once())
			->method('cc')
			->willReturn(new Address('gunther@stardewvalley-library.co.edu', 'Gunther'));
		$this->symfonyEmail
			->expects($this->once())
			->method('bcc')
			->willReturn(new Address('pam@stardewvalley-bus.com', 'Pam'));

		$this->message->setRecipients();
	}

	public function testSetTo() {
		$expected = ['pierres-general-store@stardewvalley.com' => 'Pierres General Store'];

		$message = $this->message->setTo(['pierres-general-store@stardewvalley.com' => 'Pierres General Store']);

		$this->assertEquals($expected, $message->getTo());
	}
	public function testSetRecipientsException(): void {
		$message = $this->message->setTo(['lewis-tent@~~~~.com' => "Lewis' Tent Life"]);

		$this->symfonyEmail
			->expects($this->once())
			->method('to')
			->willThrowException(new RfcComplianceException());

		$this->expectException(RfcComplianceException::class);
		$message->setRecipients();
	}

	public function testSetRecipientsEmptyValues(): void {
		$message = $this->message->setTo([]);

		$this->symfonyEmail
			->expects($this->once())
			->method('to');

		$message->setRecipients();
	}

	public function testSetGetFrom() {
		$expected = ['pierres-general-store@stardewvalley.com' => 'Pierres General Store'];

		$message = $this->message->setFrom(['pierres-general-store@stardewvalley.com' => 'Pierres General Store']);

		$this->assertEquals($expected, $message->getFrom());
	}

	public function testSetGetTo() {
		$expected = ['lewis-tent@stardewvalley.com' => "Lewis' Tent Life"];

		$message = $this->message->setTo(['lewis-tent@stardewvalley.com' => "Lewis' Tent Life"]);

		$this->assertEquals($expected, $message->getTo());
	}

	public function testSetGetReplyTo() {
		$expected = ['penny@stardewvalley-library.co.edu' => 'Penny'];

		$message = $this->message->setReplyTo(['penny@stardewvalley-library.co.edu' => 'Penny']);

		$this->assertEquals($expected, $message->getReplyTo());
	}

	public function testSetGetCC() {
		$expected = ['gunther@stardewvalley-library.co.edu' => 'Gunther'];

		$message = $this->message->setCc(['gunther@stardewvalley-library.co.edu' => 'Gunther']);

		$this->assertEquals($expected, $message->getCc());
	}

	public function testSetGetBCC() {
		$expected = ['pam@stardewvalley-bus.com' => 'Pam'];

		$message = $this->message->setBcc(['pam@stardewvalley-bus.com' => 'Pam']);

		$this->assertEquals($expected, $message->getBcc());
	}

	public function testSetPlainBody() {
		$this->symfonyEmail
			->expects($this->once())
			->method('text')
			->with('Fancy Body');

		$this->message->setPlainBody('Fancy Body');
	}

	public function testGetPlainBody() {
		$this->symfonyEmail
			->expects($this->once())
			->method('getTextBody')
			->willReturn('Fancy Body');

		$this->assertSame('Fancy Body', $this->message->getPlainBody());
	}

	public function testSetHtmlBody() {
		$this->symfonyEmail
			->expects($this->once())
			->method('html')
			->with('<blink>Fancy Body</blink>', 'utf-8');

		$this->message->setHtmlBody('<blink>Fancy Body</blink>');
	}

	public function testPlainTextRenderOption() {
		/** @var MockObject|Email $symfonyEmail */
		$symfonyEmail = $this->getMockBuilder(Email::class)
			->disableOriginalConstructor()->getMock();
		/** @var MockObject|IEMailTemplate $template */
		$template = $this->getMockBuilder(IEMailTemplate::class)
			->disableOriginalConstructor()->getMock();

		$message = new Message($symfonyEmail, true);

		$template
			->expects($this->never())
			->method('renderHTML');
		$template
			->expects($this->once())
			->method('renderText');
		$template
			->expects($this->once())
			->method('renderSubject');

		$message->useTemplate($template);
	}

	public function testBothRenderingOptions() {
		/** @var MockObject|Email $symfonyEmail */
		$symfonyEmail = $this->getMockBuilder(Email::class)
			->disableOriginalConstructor()->getMock();
		/** @var MockObject|IEMailTemplate $template */
		$template = $this->getMockBuilder(IEMailTemplate::class)
			->disableOriginalConstructor()->getMock();

		$message = new Message($symfonyEmail, false);

		$template
			->expects($this->once())
			->method('renderHTML');
		$template
			->expects($this->once())
			->method('renderText');
		$template
			->expects($this->once())
			->method('renderSubject');

		$message->useTemplate($template);
	}

	public function testSetAutoSubmitted1() {
		$headers = new Headers($this->createMock(HeaderInterface::class));
		$headers->addTextHeader(AutoSubmitted::HEADER, "yes");
		$symfonyEmail = $this->createMock(Email::class);

		$symfonyEmail->method('getHeaders')
			->willReturn($headers);

		$message = new Message($symfonyEmail, false);
		$message->setAutoSubmitted(AutoSubmitted::VALUE_AUTO_GENERATED);
		$this->assertNotSame('no', $message->getAutoSubmitted());
	}

	public function testSetAutoSubmitted2() {
		$headers = new Headers($this->createMock(HeaderInterface::class));
		$headers->addTextHeader(AutoSubmitted::HEADER, 'no');
		$symfonyEmail = $this->createMock(Email::class);

		$symfonyEmail->method('getHeaders')
			->willReturn($headers);

		$message = new Message($symfonyEmail, false);
		$message->setAutoSubmitted(AutoSubmitted::VALUE_AUTO_GENERATED);
		$this->assertSame('auto-generated', $message->getAutoSubmitted());
	}

	public function testGetAutoSubmitted() {
		$headers = new Headers($this->createMock(HeaderInterface::class));
		$headers->addTextHeader(AutoSubmitted::HEADER, 'no');
		$symfonyEmail = $this->createMock(Email::class);

		$symfonyEmail->method('getHeaders')
			->willReturn($headers);

		$message = new Message($symfonyEmail, false);
		$this->assertSame("no", $message->getAutoSubmitted());
	}
}
