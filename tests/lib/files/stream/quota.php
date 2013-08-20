<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Stream;

class Quota extends \PHPUnit_Framework_TestCase {
	public function tearDown() {
		\OC\Files\Stream\Quota::clear();
	}

	protected function getStream($mode, $limit) {
		$source = fopen('php://temp', $mode);
		return \OC\Files\Stream\Quota::wrap($source, $limit);
	}

	public function testWriteEnoughSpace() {
		$stream = $this->getStream('w+', 100);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobar', fread($stream, 100));
	}

	public function testWriteNotEnoughSpace() {
		$stream = $this->getStream('w+', 3);
		$this->assertEquals(3, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foo', fread($stream, 100));
	}

	public function testWriteNotEnoughSpaceSecondTime() {
		$stream = $this->getStream('w+', 9);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		$this->assertEquals(3, fwrite($stream, 'qwerty'));
		rewind($stream);
		$this->assertEquals('foobarqwe', fread($stream, 100));
	}

	public function testWriteEnoughSpaceRewind() {
		$stream = $this->getStream('w+', 6);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals(3, fwrite($stream, 'qwe'));
		rewind($stream);
		$this->assertEquals('qwebar', fread($stream, 100));
	}

	public function testWriteNotEnoughSpaceRead() {
		$stream = $this->getStream('w+', 6);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobar', fread($stream, 6));
		$this->assertEquals(0, fwrite($stream, 'qwe'));
	}

	public function testWriteNotEnoughSpaceExistingStream() {
		$source = fopen('php://temp', 'w+');
		fwrite($source, 'foobar');
		$stream = \OC\Files\Stream\Quota::wrap($source, 3);
		$this->assertEquals(3, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobarfoo', fread($stream, 100));
	}

	public function testWriteNotEnoughSpaceExistingStreamRewind() {
		$source = fopen('php://temp', 'w+');
		fwrite($source, 'foobar');
		$stream = \OC\Files\Stream\Quota::wrap($source, 3);
		rewind($stream);
		$this->assertEquals(6, fwrite($stream, 'qwerty'));
		rewind($stream);
		$this->assertEquals('qwerty', fread($stream, 100));
	}
}
