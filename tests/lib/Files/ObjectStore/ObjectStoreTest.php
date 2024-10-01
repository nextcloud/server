<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Test\TestCase;

abstract class ObjectStoreTest extends TestCase {
	/** @var string[] */
	private $cleanup = [];

	/**
	 * @return \OCP\Files\ObjectStore\IObjectStore
	 */
	abstract protected function getInstance();

	protected function cleanupAfter(string $urn) {
		$this->cleanup[] = $urn;
	}

	public function tearDown(): void {
		parent::tearDown();

		$instance = $this->getInstance();
		foreach ($this->cleanup as $urn) {
			$instance->deleteObject($urn);
		}
	}

	protected function stringToStream($data) {
		$stream = fopen('php://temp', 'w+');
		fwrite($stream, $data);
		rewind($stream);
		return $stream;
	}

	public function testWriteRead(): void {
		$stream = $this->stringToStream('foobar');

		$instance = $this->getInstance();

		$instance->writeObject('1', $stream);

		$result = $instance->readObject('1');
		$instance->deleteObject('1');

		$this->assertEquals('foobar', stream_get_contents($result));
	}

	public function testDelete(): void {
		$stream = $this->stringToStream('foobar');

		$instance = $this->getInstance();

		$instance->writeObject('2', $stream);

		$instance->deleteObject('2');

		try {
			// to to read to verify that the object no longer exists
			$instance->readObject('2');
			$this->fail();
		} catch (\Exception $e) {
			// dummy assert to keep phpunit happy
			$this->assertEquals(1, 1);
		}
	}

	public function testReadNonExisting(): void {
		$instance = $this->getInstance();

		try {
			$instance->readObject('non-existing');
			$this->fail();
		} catch (\Exception $e) {
			// dummy assert to keep phpunit happy
			$this->assertEquals(1, 1);
		}
	}

	public function testDeleteNonExisting(): void {
		$instance = $this->getInstance();

		try {
			$instance->deleteObject('non-existing');
			$this->fail();
		} catch (\Exception $e) {
			// dummy assert to keep phpunit happy
			$this->assertEquals(1, 1);
		}
	}

	public function testExists(): void {
		$stream = $this->stringToStream('foobar');

		$instance = $this->getInstance();
		$this->assertFalse($instance->objectExists('2'));

		$instance->writeObject('2', $stream);

		$this->assertTrue($instance->objectExists('2'));

		$instance->deleteObject('2');

		$this->assertFalse($instance->objectExists('2'));
	}

	public function testCopy(): void {
		$this->cleanupAfter('source');
		$this->cleanupAfter('target');

		$stream = $this->stringToStream('foobar');

		$instance = $this->getInstance();

		$instance->writeObject('source', $stream);

		$this->assertFalse($instance->objectExists('target'));

		$instance->copyObject('source', 'target');

		$this->assertTrue($instance->objectExists('target'));

		$this->assertEquals('foobar', stream_get_contents($instance->readObject('target')));
	}

	public function testFseekSize(): void {
		$instance = $this->getInstance();

		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$size = filesize($textFile);
		$instance->writeObject('source', fopen($textFile, 'r'));

		$fh = $instance->readObject('source');

		fseek($fh, 0, SEEK_END);
		$pos = ftell($fh);

		$this->assertEquals($size, $pos);
	}
}
