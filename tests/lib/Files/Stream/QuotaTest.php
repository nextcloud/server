<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Stream;

class QuotaTest extends \Test\TestCase {
	protected function tearDown() {
		\OC\Files\Stream\Quota::clear();
		parent::tearDown();
	}

	/**
	 * @param string $mode
	 * @param integer $limit
	 */
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

	public function testFseekReturnsSuccess() {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		$this->assertEquals(0, fseek($stream, 3, SEEK_SET));
		$this->assertEquals(0, fseek($stream, -1, SEEK_CUR));
		$this->assertEquals(0, fseek($stream, -4, SEEK_END));
	}

	public function testWriteAfterSeekEndWithEnoughSpace() {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		fseek($stream, -3, SEEK_END);
		$this->assertEquals(11, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdefghijk', fread($stream, 100));
	}

	public function testWriteAfterSeekEndWithNotEnoughSpace() {
		$stream = $this->getStream('w+', 13);
		fwrite($stream, '0123456789');
		// seek forward first to potentially week out
		// potential limit calculation errors
		fseek($stream, 4, SEEK_SET);
		// seek to the end
		fseek($stream, -3, SEEK_END);
		$this->assertEquals(6, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdef', fread($stream, 100));
	}

	public function testWriteAfterSeekSetWithEnoughSpace() {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		fseek($stream, 7, SEEK_SET);
		$this->assertEquals(11, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdefghijk', fread($stream, 100));
	}

	public function testWriteAfterSeekSetWithNotEnoughSpace() {
		$stream = $this->getStream('w+', 13);
		fwrite($stream, '0123456789');
		fseek($stream, 7, SEEK_SET);
		$this->assertEquals(6, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdef', fread($stream, 100));
	}

	public function testWriteAfterSeekCurWithEnoughSpace() {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		rewind($stream);
		fseek($stream, 3, SEEK_CUR);
		fseek($stream, 5, SEEK_CUR);
		fseek($stream, -1, SEEK_CUR);
		$this->assertEquals(11, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdefghijk', fread($stream, 100));
	}

	public function testWriteAfterSeekCurWithNotEnoughSpace() {
		$stream = $this->getStream('w+', 13);
		fwrite($stream, '0123456789');
		rewind($stream);
		fseek($stream, 3, SEEK_CUR);
		fseek($stream, 5, SEEK_CUR);
		fseek($stream, -1, SEEK_CUR);
		$this->assertEquals(6, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdef', fread($stream, 100));
	}
}
