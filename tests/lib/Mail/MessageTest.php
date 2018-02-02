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
use Swift_Mime_HeaderSet;
use Swift_Mime_Headers_UnstructuredHeader;
use Swift_Mime_Headers_ParameterizedHeader;
use Test\TestCase;
use OCP\IGpg;

class MessageTest extends TestCase {
	/** @var Swift_Message */
	private $swiftMessage;
	/** @var Message */
	private $message;
	/** @var IGpg|\PHPUnit_Framework_MockObject_MockObject*/
	private $gpg;

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
			->disableOriginalClone()
			->disableOriginalConstructor()->getMock();

		$this->gpg = $this->createMock(IGpg::class);

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

	public function testSetFromWithFingerprint() {
		$this->swiftMessage
			->expects($this->once())
			->method('setFrom')
			->with(array('lukas@owncloud.com'));
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setFrom(array('lukas@owncloud.com'), $fingerprints);
		$this->assertEquals($fingerprints, $this->message->getFromFingerprints());
	}

	public function testSetFromAutocryptHeader() {
		$this->swiftMessage
			->expects($this->once())
			->method('setFrom')
			->with(array('lukas@owncloud.com'));
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];

		$headers = $this->createMock("\Swift_Mime_HeaderSet");
		$headers->expects($this->once())
			->method("addParameterizedHeader");

		$this->swiftMessage
			->expects($this->once())
			->method("getHeaders")
			->willReturn($headers);

		$this->swiftMessage
			->expects($this->once())
			->method("attach");

		$this->message->setFrom(array('lukas@owncloud.com'), [$fingerprints[0]]);
		$this->assertEquals([$fingerprints[0]], $this->message->getFromFingerprints());
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

	public function testSetToWithFingerprint() {
		$this->swiftMessage
			->expects($this->once())
			->method('setTo')
			->with(array('lukas@owncloud.com'));
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setTo(array('lukas@owncloud.com'), $fingerprints);
		$this->assertEquals($fingerprints, $this->message->getToFingerprints());
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

	public function testSetCcWithFingerprint() {
		$this->swiftMessage
			->expects($this->once())
			->method('setCc')
			->with(array('lukas@owncloud.com'));
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setCc(array('lukas@owncloud.com'), $fingerprints);
		$this->assertEquals($fingerprints, $this->message->getCcFingerprints());
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

	public function testSetBccWithFingerprint() {
		$this->swiftMessage
			->expects($this->once())
			->method('setBcc')
			->with(array('lukas@owncloud.com'));
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setBcc(array('lukas@owncloud.com'), $fingerprints);
		$this->assertEquals($fingerprints, $this->message->getBccFingerprints());
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

	public function testMessageContentToString(){
		$messageString = "\n This is a Test Message \r Conntent with Multiple \r\n Lines\n and \r\n differnt \n Line \r endings and \n\n\n multiple new lines \r at the end \n\n\n\n";
		$finalString = "This is a Test Message\r\n Conntent with Multiple\r\n Lines\r\n and\r\n differnt\r\n Line\r\n endings and\r\n\r\n\r\n multiple new lines\r\n at the end\r\n";

		$headers = $this->createMock("\Swift_Mime_HeaderSet");
		$headers->expects($this->exactly(8))
			->method("remove");

		$this->swiftMessage
			->expects($this->exactly(8))
			->method('getHeaders')
			->willReturn($headers);
		$this->swiftMessage
			->expects($this->once())
			->method("toString")
			->willReturn($messageString);

		$this->assertEquals($finalString, $this->invokePrivate($this->message, "messageContentToString"));
	}

	public function testSign() {
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setFrom(array('lukas@owncloud.com'), $fingerprints);


		$header = $this->createMock("\Swift_Mime_Headers_ParameterizedHeader");
		$header->expects($this->once())
			->method("setParameters");
		$header->expects($this->once())
			->method("setValue")
			->with("multipart/signed");

		$headers = $this->createMock("\Swift_Mime_HeaderSet");
		$headers->expects($this->once())
			->method("removeAll")
			->with("Content-Transfer-Encoding");
		$headers->expects($this->any())
			->method("get")
			->willReturn($header);

		$this->swiftMessage
			->expects($this->once())
			->method("setEncoder")
			->with(new \Swift_Mime_ContentEncoder_RawContentEncoder);
		$this->swiftMessage
			->expects($this->once())
			->method("setChildren")
			->with([]);
		$this->swiftMessage
			->expects($this->once())
			->method("setBoundary");
		$this->swiftMessage
			->expects($this->once())
			->method("setBody");
		$this->swiftMessage
			->expects($this->any())
			->method("getHeaders")
			->willReturn($headers);


		$this->gpg
			->expects($this->once())
			->method("sign");
		$this->message->sign($this->gpg);
	}

	public function testEncrypt() {
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setTo(array('lukas@owncloud.com', 'test@invalid.email'), $fingerprints);


		$header = $this->createMock("\Swift_Mime_Headers_ParameterizedHeader");
		$header->expects($this->once())
			->method("setParameters");
		$header->expects($this->once())
			->method("setValue")
			->with("multipart/encrypted");

		$headers = $this->createMock("\Swift_Mime_HeaderSet");
		$headers->expects($this->once())
			->method("removeAll")
			->with("Content-Transfer-Encoding");
		$headers->expects($this->any())
			->method("get")
			->willReturn($header);

		$this->swiftMessage
			->expects($this->once())
			->method("setEncoder")
			->with(new \Swift_Mime_ContentEncoder_RawContentEncoder);
		$this->swiftMessage
			->expects($this->once())
			->method("setChildren")
			->with([]);
		$this->swiftMessage
			->expects($this->once())
			->method("setBoundary");
		$this->swiftMessage
			->expects($this->once())
			->method("setBody");
		$this->swiftMessage
			->expects($this->any())
			->method("getHeaders")
			->willReturn($headers);


		$this->gpg
			->expects($this->once())
			->method("encrypt");
		$this->message->encrypt($this->gpg);
	}

	public function testEncryptSign() {
		$fingerprints = ["abcdefghijklmnopqrstuvwxyz","zyxwvutsrpqonmlkjihgfedcba"];
		$this->message->setTo(array('lukas@owncloud.com'), [$fingerprints[0]]);
		$this->message->setFrom(array('test@bla.invalid'), [$fingerprints[1],$fingerprints[0]]);


		$header = $this->createMock("\Swift_Mime_Headers_ParameterizedHeader");
		$header->expects($this->exactly(2))
			->method("setParameters");
		$header->expects($this->exactly(2))
			->method("setValue");

		$headers = $this->createMock("\Swift_Mime_HeaderSet");
		$headers->expects($this->exactly(2))
			->method("removeAll")
			->with("Content-Transfer-Encoding");
		$headers->expects($this->any())
			->method("get")
			->willReturn($header);

		$this->swiftMessage
			->expects($this->once())
			->method("setEncoder")
			->with(new \Swift_Mime_ContentEncoder_RawContentEncoder);
		$this->swiftMessage
			->expects($this->once())
			->method("setChildren")
			->with([]);
		$this->swiftMessage
			->expects($this->once())
			->method("setBoundary");
		$this->swiftMessage
			->expects($this->once())
			->method("setBody");
		$this->swiftMessage
			->expects($this->any())
			->method("getHeaders")
			->willReturn($headers);


		$this->gpg
			->expects($this->once())
			->method("encrypt");
		$this->gpg
			->expects($this->once())
			->method("sign");
		$this->message->encryptsign($this->gpg);
	}
}
