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
use Swift_Message;
use Test\TestCase;

class MessageTest extends TestCase {
	/** @var Swift_Message */
	private $swiftMessage;
	/** @var Message */
	private $message;

	/**
	 * @return array
	 */
	public function mailAddressProvider() {
		return [
			[['lukas@owncloud.com' => 'Lukas Reschke'], ['lukas@owncloud.com' => 'Lukas Reschke']],
			[['lukas@owncloud.com' => 'Lukas Reschke', 'lukas@öwnclöüd.com', 'lukäs@owncloud.örg' => 'Lükäs Réschke'],
				['lukas@owncloud.com' => 'Lukas Reschke', 'lukas@xn--wncld-iuae2c.com', 'lukäs@owncloud.xn--rg-eka' => 'Lükäs Réschke']],
			[['lukas@öwnclöüd.com'], ['lukas@xn--wncld-iuae2c.com']],
		];
	}

	/**
	 * @return array
	 */
	public function getMailAddressProvider() {
		return [
			[null, []],
			[['lukas@owncloud.com' => 'Lukas Reschke'], ['lukas@owncloud.com' => 'Lukas Reschke']],
		];
	}

	protected function setUp(): void {
		parent::setUp();

		$this->swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()->getMock();

		$this->message = new Message($this->swiftMessage, false);
	}

	/**
	 * @requires function idn_to_ascii
	 * @dataProvider mailAddressProvider
	 *
	 * @param string $unconverted
	 * @param string $expected
	 */
	public function testConvertAddresses($unconverted, $expected) {
		$this->assertSame($expected, self::invokePrivate($this->message, 'convertAddresses', [$unconverted]));
	}

	public function testSetFrom() {
		$this->swiftMessage
			->expects($this->once())
			->method('setFrom')
			->with(['lukas@owncloud.com']);
		$this->message->setFrom(['lukas@owncloud.com']);
	}


	/**
	 * @dataProvider getMailAddressProvider
	 *
	 * @param $swiftresult
	 * @param $return
	 */
	public function testGetFrom($swiftresult, $return) {
		$this->swiftMessage
			->expects($this->once())
			->method('getFrom')
			->willReturn($swiftresult);

		$this->assertSame($return, $this->message->getFrom());
	}

	public function testSetReplyTo() {
		$this->swiftMessage
			->expects($this->once())
			->method('setReplyTo')
			->with(['lukas@owncloud.com']);
		$this->message->setReplyTo(['lukas@owncloud.com']);
	}

	public function testGetReplyTo() {
		$this->swiftMessage
			->expects($this->once())
			->method('getReplyTo')
			->willReturn('lukas@owncloud.com');

		$this->assertSame('lukas@owncloud.com', $this->message->getReplyTo());
	}

	/** @dataProvider dataSetTo */
	public function testSetTo(array $to, array $expected) {
		$this->swiftMessage
			->expects($this->once())
			->method('setTo')
			->with($expected);
		$this->message->setTo($to);
	}

	public function dataSetTo(): array {
		return [
			[['robot@example.com'], ['robot@example.com']],
			[['robot'], ['robot' => 'robot']],
			[['robot' => 'robot display name'], ['robot' => 'robot display name']],
			[['example@🤖.com'], ['example@xn--yp9h.com']],
			[['example@🤖.com' => 'A robot'], ['example@xn--yp9h.com' => 'A robot']],
		];
	}

	/**
	 * @dataProvider  getMailAddressProvider
	 */
	public function testGetTo($swiftresult, $return) {
		$this->swiftMessage
			->expects($this->once())
			->method('getTo')
			->willReturn($swiftresult);

		$this->assertSame($return, $this->message->getTo());
	}

	public function testSetCc() {
		$this->swiftMessage
			->expects($this->once())
			->method('setCc')
			->with(['lukas@owncloud.com']);
		$this->message->setCc(['lukas@owncloud.com']);
	}

	/**
	 * @dataProvider  getMailAddressProvider
	 */
	public function testGetCc($swiftresult, $return) {
		$this->swiftMessage
			->expects($this->once())
			->method('getCc')
			->willReturn($swiftresult);

		$this->assertSame($return, $this->message->getCc());
	}

	public function testSetBcc() {
		$this->swiftMessage
			->expects($this->once())
			->method('setBcc')
			->with(['lukas@owncloud.com']);
		$this->message->setBcc(['lukas@owncloud.com']);
	}

	/**
	 * @dataProvider  getMailAddressProvider
	 */
	public function testGetBcc($swiftresult, $return) {
		$this->swiftMessage
			->expects($this->once())
			->method('getBcc')
			->willReturn($swiftresult);

		$this->assertSame($return, $this->message->getBcc());
	}

	public function testSetSubject() {
		$this->swiftMessage
			->expects($this->once())
			->method('setSubject')
			->with('Fancy Subject');

		$this->message->setSubject('Fancy Subject');
	}

	public function testGetSubject() {
		$this->swiftMessage
			->expects($this->once())
			->method('getSubject')
			->willReturn('Fancy Subject');

		$this->assertSame('Fancy Subject', $this->message->getSubject());
	}

	public function testSetPlainBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('setBody')
			->with('Fancy Body');

		$this->message->setPlainBody('Fancy Body');
	}

	public function testGetPlainBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('getBody')
			->willReturn('Fancy Body');

		$this->assertSame('Fancy Body', $this->message->getPlainBody());
	}

	public function testSetHtmlBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('addPart')
			->with('<blink>Fancy Body</blink>', 'text/html');

		$this->message->setHtmlBody('<blink>Fancy Body</blink>');
	}

	public function testPlainTextRenderOption() {
		/** @var \PHPUnit\Framework\MockObject\MockObject|Swift_Message $swiftMessage */
		$swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|IEMailTemplate $template */
		$template = $this->getMockBuilder('\OCP\Mail\IEMailTemplate')
			->disableOriginalConstructor()->getMock();

		$message = new Message($swiftMessage, true);

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
		/** @var \PHPUnit\Framework\MockObject\MockObject|Swift_Message $swiftMessage */
		$swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|IEMailTemplate $template */
		$template = $this->getMockBuilder('\OCP\Mail\IEMailTemplate')
			->disableOriginalConstructor()->getMock();

		$message = new Message($swiftMessage, false);

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
		$swiftMimeSimpleHeaderSet = $this->getMockBuilder('\Swift_Mime_SimpleHeaderSet')
			->disableOriginalConstructor()
			->getMock();
		$swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->getMock();

		$swiftMessage->method('getHeaders')->willReturn($swiftMimeSimpleHeaderSet);

		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('has')
			->with('Auto-Submitted');
		$swiftMimeSimpleHeaderSet->expects($this->never())
			->method('remove');
		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('addTextHeader')
			->with('Auto-Submitted', AutoSubmitted::VALUE_AUTO_GENERATED);

		$message = new Message($swiftMessage, false);
		$message->setAutoSubmitted(AutoSubmitted::VALUE_AUTO_GENERATED);
	}

	public function testSetAutoSubmitted2() {
		$swiftMimeSimpleHeaderSet = $this->getMockBuilder('\Swift_Mime_SimpleHeaderSet')
			->disableOriginalConstructor()
			->getMock();
		$swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->getMock();

		$swiftMessage->method('getHeaders')->willReturn($swiftMimeSimpleHeaderSet);

		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('has')
			->with('Auto-Submitted')
			->willReturn(true);
		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('remove')
			->with('Auto-Submitted');
		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('addTextHeader')
			->with('Auto-Submitted', AutoSubmitted::VALUE_AUTO_GENERATED);

		$message = new Message($swiftMessage, false);
		$message->setAutoSubmitted(AutoSubmitted::VALUE_AUTO_GENERATED);
	}

	public function testGetAutoSubmitted1() {
		$swiftMimeSimpleHeaderSet = $this->getMockBuilder('\Swift_Mime_SimpleHeaderSet')
			->disableOriginalConstructor()
			->getMock();
		$swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->getMock();

		$swiftMessage->method('getHeaders')->willReturn($swiftMimeSimpleHeaderSet);

		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('has')
			->with('Auto-Submitted');
		$swiftMimeSimpleHeaderSet->expects($this->never())
			->method('get');

		$message = new Message($swiftMessage, false);
		$this->assertSame("no", $message->getAutoSubmitted());
	}
	public function testGetAutoSubmitted2() {
		$swiftMimeHeader = $this->getMockBuilder('\Swift_Mime_Header')
			->disableOriginalConstructor()
			->getMockForAbstractClass();
		$swiftMimeSimpleHeaderSet = $this->getMockBuilder('\Swift_Mime_SimpleHeaderSet')
			->disableOriginalConstructor()
			->getMock();
		$swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->getMock();


		$swiftMessage->method('getHeaders')->willReturn($swiftMimeSimpleHeaderSet);
		$swiftMimeHeader->method('toString')->willReturn(AutoSubmitted::VALUE_AUTO_GENERATED);

		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('has')
			->with('Auto-Submitted')
			->willReturn(true);
		$swiftMimeSimpleHeaderSet->expects($this->once())
			->method('get')
			->willReturn($swiftMimeHeader);

		$message = new Message($swiftMessage, false);
		$this->assertSame(AutoSubmitted::VALUE_AUTO_GENERATED, $message->getAutoSubmitted());
	}
}
