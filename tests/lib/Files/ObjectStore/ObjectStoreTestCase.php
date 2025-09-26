<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use Test\TestCase;

abstract class ObjectStoreTestCase extends TestCase {
	/** @var string[] */
	private $cleanup = [];

	private $instance = null;

	/**
	 * @return IObjectStore
	 */
	abstract protected function getInstance();

	protected function cleanupAfter(string $urn) {
		$this->cleanup[] = $urn;
	}

	public function setUp(): void {
		parent::setUp();

		$this->instance = $this->getInstance();
	}

	public function tearDown(): void {
		if ($this->instance) {
			foreach ($this->cleanup as $urn) {
				$this->instance->deleteObject($urn);
			}
		}

		parent::tearDown();
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

	protected function assertOnlyExpectedWarnings(array $warnings): void {
		$onlyFopenWarnings = array_reduce(
			$warnings,
			fn (bool $ok, string $warning) => $ok && str_starts_with($warning, 'fopen('),
			true,
		);
		$this->assertTrue($onlyFopenWarnings);
	}

	public function testDelete(): void {
		$stream = $this->stringToStream('foobar');

		$instance = $this->getInstance();

		$instance->writeObject('2', $stream);

		$instance->deleteObject('2');

		$warnings = [];
		try {
			set_error_handler(
				function (int $errno, string $errstr) use (&$warnings): void {
					$warnings[] = $errstr;
				},
			);
			// to to read to verify that the object no longer exists
			$instance->readObject('2');
			$this->fail();
		} catch (\Exception $e) {
			$this->assertOnlyExpectedWarnings($warnings);
		} finally {
			restore_error_handler();
		}
	}

	public function testReadNonExisting(): void {
		$instance = $this->getInstance();

		$warnings = [];
		try {
			set_error_handler(
				function (int $errno, string $errstr) use (&$warnings): void {
					$warnings[] = $errstr;
				},
			);
			$instance->readObject('non-existing');
			$this->fail();
		} catch (\Exception $e) {
			$this->assertOnlyExpectedWarnings($warnings);
		} finally {
			restore_error_handler();
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
