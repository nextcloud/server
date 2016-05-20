<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Mail;

use OC\Mail\Message;
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
		return array(
			array(array('lukas@owncloud.com' => 'Lukas Reschke'), array('lukas@owncloud.com' => 'Lukas Reschke')),
			array(array('lukas@owncloud.com' => 'Lukas Reschke', 'lukas@öwnclöüd.com', 'lukäs@owncloud.örg' => 'Lükäs Réschke'),
				array('lukas@owncloud.com' => 'Lukas Reschke', 'lukas@xn--wncld-iuae2c.com', 'lukäs@owncloud.xn--rg-eka' => 'Lükäs Réschke')),
			array(array('lukas@öwnclöüd.com'), array('lukas@xn--wncld-iuae2c.com'))
		);
	}

	function setUp() {
		parent::setUp();

		$this->swiftMessage = $this->getMockBuilder('\Swift_Message')
			->disableOriginalConstructor()->getMock();

		$this->message = new Message($this->swiftMessage);
	}

	/**
	 * @requires function idn_to_ascii
	 * @dataProvider mailAddressProvider
	 *
	 * @param string $unconverted
	 * @param string $expected
	 */
	public function testConvertAddresses($unconverted, $expected) {
		$this->assertSame($expected, self::invokePrivate($this->message, 'convertAddresses', array($unconverted)));
	}

	public function testSetFrom() {
		$this->swiftMessage
			->expects($this->once())
			->method('setFrom')
			->with(array('lukas@owncloud.com'));
		$this->message->setFrom(array('lukas@owncloud.com'));
	}

	public function testGetFrom() {
		$this->swiftMessage
			->expects($this->once())
			->method('getFrom')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getFrom());
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
			->will($this->returnValue(['lukas@owncloud.com']));

		$this->assertSame(['lukas@owncloud.com'], $this->message->getReplyTo());
	}

	public function testSetTo() {
		$this->swiftMessage
			->expects($this->once())
			->method('setTo')
			->with(array('lukas@owncloud.com'));
		$this->message->setTo(array('lukas@owncloud.com'));
	}

	public function testGetTo() {
		$this->swiftMessage
			->expects($this->once())
			->method('getTo')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getTo());
	}

	public function testSetCc() {
		$this->swiftMessage
			->expects($this->once())
			->method('setCc')
			->with(array('lukas@owncloud.com'));
		$this->message->setCc(array('lukas@owncloud.com'));
	}

	public function testGetCc() {
		$this->swiftMessage
			->expects($this->once())
			->method('getCc')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getCc());
	}

	public function testSetBcc() {
		$this->swiftMessage
			->expects($this->once())
			->method('setBcc')
			->with(array('lukas@owncloud.com'));
		$this->message->setBcc(array('lukas@owncloud.com'));
	}

	public function testGetBcc() {
		$this->swiftMessage
			->expects($this->once())
			->method('getBcc')
			->will($this->returnValue(array('lukas@owncloud.com')));

		$this->assertSame(array('lukas@owncloud.com'), $this->message->getBcc());
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
			->will($this->returnValue('Fancy Subject'));

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
			->will($this->returnValue('Fancy Body'));

		$this->assertSame('Fancy Body', $this->message->getPlainBody());
	}

	public function testSetHtmlBody() {
		$this->swiftMessage
			->expects($this->once())
			->method('addPart')
			->with('<blink>Fancy Body</blink>', 'text/html');

		$this->message->setHtmlBody('<blink>Fancy Body</blink>');
	}

}
