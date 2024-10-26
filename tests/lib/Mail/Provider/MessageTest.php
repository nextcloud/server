<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Mail\Provider;

use OCP\Mail\Provider\Address;
use OCP\Mail\Provider\Attachment;
use OCP\Mail\Provider\Message;
use Test\TestCase;

class MessageTest extends TestCase {

	/** @var Message&MockObject */
	private Message $message;
	/** @var Address&MockObject */
	private Address $address1;
	/** @var Address&MockObject */
	private Address $address2;
	/** @var Attachment&MockObject */
	private Attachment $attachment1;
	/** @var Attachment&MockObject */
	private Attachment $attachment2;

	protected function setUp(): void {
		parent::setUp();

		$this->message = new Message(
			['id' => 'cd02ea42-feac-4863-b9d8-484d16a587ea']
		);
		$this->address1 = new Address(
			'user1@testing.com',
			'User One'
		);
		$this->address2 = new Address(
			'user2@testing.com',
			'User Two'
		);
		$this->attachment1 = new Attachment(
			'This is the contents of the first attachment',
			'example1.txt',
			'text/plain',
			false
		);
		$this->attachment2 = new Attachment(
			'This is the contents of the second attachment',
			'example1.txt',
			'text/plain',
			false
		);

	}

	public function testId(): void {
		
		// test set by constructor
		$this->assertEquals('cd02ea42-feac-4863-b9d8-484d16a587ea', $this->message->id());

	}

	public function testFrom(): void {
		
		// test not set
		$this->assertNull($this->message->getFrom());
		// test set by setter
		$this->message->setFrom($this->address1);
		$this->assertEquals($this->address1, $this->message->getFrom());

	}

	public function testReplyTo(): void {
		
		// test not set
		$this->assertNull($this->message->getReplyTo());
		// test set by setter
		$this->message->setReplyTo($this->address1);
		$this->assertEquals($this->address1, $this->message->getReplyTo());

	}

	public function testTo(): void {
		
		// test not set
		$this->assertEquals([], $this->message->getTo());
		// test set by setter single
		$this->message->setTo($this->address1);
		$this->assertEquals([$this->address1], $this->message->getTo());
		// test set by setter multiple
		$this->message->setTo($this->address1, $this->address2);
		$this->assertEquals([$this->address1, $this->address2], $this->message->getTo());

	}

	public function testCc(): void {
		
		// test not set
		$this->assertEquals([], $this->message->getCc());
		// test set by setter single
		$this->message->setCc($this->address1);
		$this->assertEquals([$this->address1], $this->message->getCc());
		// test set by setter multiple
		$this->message->setCc($this->address1, $this->address2);
		$this->assertEquals([$this->address1, $this->address2], $this->message->getCc());

	}

	public function testBcc(): void {
		
		// test not set
		$this->assertEquals([], $this->message->getBcc());
		// test set by setter single
		$this->message->setBcc($this->address1);
		$this->assertEquals([$this->address1], $this->message->getBcc());
		// test set by setter multiple
		$this->message->setBcc($this->address1, $this->address2);
		$this->assertEquals([$this->address1, $this->address2], $this->message->getBcc());

	}

	public function testSubject(): void {
		
		// test not set
		$this->assertNull($this->message->getSubject());
		// test set by setter
		$this->message->setSubject('Testing Mail Subject');
		$this->assertEquals('Testing Mail Subject', $this->message->getSubject());

	}

	public function testBody(): void {
		
		// test not set
		$this->assertNull($this->message->getBody());
		// test set by setter - text body
		$this->message->setBody('Testing Text Body', false);
		$this->assertEquals('Testing Text Body', $this->message->getBody());
		$this->message->setBodyPlain('Testing Text Body Again', false);
		$this->assertEquals('Testing Text Body Again', $this->message->getBodyPlain());
		// test set by setter - html body
		$this->message->setBody('Testing HTML Body', true);
		$this->assertEquals('Testing HTML Body', $this->message->getBody());
		$this->message->setBodyHtml('Testing HTML Body Again', false);
		$this->assertEquals('Testing HTML Body Again', $this->message->getBodyHtml());

	}

	public function testAttachments(): void {
		
		// test not set
		$this->assertEquals([], $this->message->getAttachments());
		// test set by setter single
		$this->message->setAttachments($this->attachment1);
		$this->assertEquals([$this->attachment1], $this->message->getAttachments());
		// test set by setter multiple
		$this->message->setAttachments($this->attachment1, $this->attachment2);
		$this->assertEquals([$this->attachment1, $this->attachment2], $this->message->getAttachments());

	}
}
