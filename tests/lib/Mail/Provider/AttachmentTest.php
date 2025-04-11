<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Mail\Provider;

use OCP\Mail\Provider\Attachment;
use Test\TestCase;

class AttachmentTest extends TestCase {

	/** @var Attachment&MockObject */
	private Attachment $attachment;

	protected function setUp(): void {
		parent::setUp();

		$this->attachment = new Attachment(
			'This is the contents of a file',
			'example1.txt',
			'text/plain',
			false
		);

	}

	public function testName(): void {
		
		// test set by constructor
		$this->assertEquals('example1.txt', $this->attachment->getName());
		// test set by setter
		$this->attachment->setName('example2.txt');
		$this->assertEquals('example2.txt', $this->attachment->getName());

	}

	public function testType(): void {
		
		// test set by constructor
		$this->assertEquals('text/plain', $this->attachment->getType());
		// test set by setter
		$this->attachment->setType('text/html');
		$this->assertEquals('text/html', $this->attachment->getType());

	}

	public function testContents(): void {
		
		// test set by constructor
		$this->assertEquals('This is the contents of a file', $this->attachment->getContents());
		// test set by setter
		$this->attachment->setContents('This is the modified contents of a file');
		$this->assertEquals('This is the modified contents of a file', $this->attachment->getContents());

	}

	public function testEmbedded(): void {
		
		// test set by constructor
		$this->assertEquals(false, $this->attachment->getEmbedded());
		// test set by setter
		$this->attachment->setEmbedded(true);
		$this->assertEquals(true, $this->attachment->getEmbedded());

	}

}
