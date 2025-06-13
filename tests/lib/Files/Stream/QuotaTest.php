<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Stream;

use OC\Files\Stream\Quota;

class QuotaTest extends \Test\TestCase {
	/**
	 * @param string $mode
	 * @param integer $limit
	 * @return resource
	 */
	protected function getStream($mode, $limit) {
		$source = fopen('php://temp', $mode);
		return Quota::wrap($source, $limit);
	}

	public function testWriteEnoughSpace(): void {
		$stream = $this->getStream('w+', 100);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobar', fread($stream, 100));
	}

	public function testWriteNotEnoughSpace(): void {
		$stream = $this->getStream('w+', 3);
		$this->assertEquals(3, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foo', fread($stream, 100));
	}

	public function testWriteNotEnoughSpaceSecondTime(): void {
		$stream = $this->getStream('w+', 9);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		$this->assertEquals(3, fwrite($stream, 'qwerty'));
		rewind($stream);
		$this->assertEquals('foobarqwe', fread($stream, 100));
	}

	public function testWriteEnoughSpaceRewind(): void {
		$stream = $this->getStream('w+', 6);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals(3, fwrite($stream, 'qwe'));
		rewind($stream);
		$this->assertEquals('qwebar', fread($stream, 100));
	}

	public function testWriteNotEnoughSpaceRead(): void {
		$stream = $this->getStream('w+', 6);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobar', fread($stream, 6));
		$this->assertEquals(0, fwrite($stream, 'qwe'));
	}

	public function testWriteNotEnoughSpaceExistingStream(): void {
		$source = fopen('php://temp', 'w+');
		fwrite($source, 'foobar');
		$stream = Quota::wrap($source, 3);
		$this->assertEquals(3, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobarfoo', fread($stream, 100));
	}

	public function testWriteNotEnoughSpaceExistingStreamRewind(): void {
		$source = fopen('php://temp', 'w+');
		fwrite($source, 'foobar');
		$stream = Quota::wrap($source, 3);
		rewind($stream);
		$this->assertEquals(6, fwrite($stream, 'qwerty'));
		rewind($stream);
		$this->assertEquals('qwerty', fread($stream, 100));
	}

	public function testFseekReturnsSuccess(): void {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		$this->assertEquals(0, fseek($stream, 3, SEEK_SET));
		$this->assertEquals(0, fseek($stream, -1, SEEK_CUR));
		$this->assertEquals(0, fseek($stream, -4, SEEK_END));
	}

	public function testWriteAfterSeekEndWithEnoughSpace(): void {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		fseek($stream, -3, SEEK_END);
		$this->assertEquals(11, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdefghijk', fread($stream, 100));
	}

	public function testWriteAfterSeekEndWithNotEnoughSpace(): void {
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

	public function testWriteAfterSeekSetWithEnoughSpace(): void {
		$stream = $this->getStream('w+', 100);
		fwrite($stream, '0123456789');
		fseek($stream, 7, SEEK_SET);
		$this->assertEquals(11, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdefghijk', fread($stream, 100));
	}

	public function testWriteAfterSeekSetWithNotEnoughSpace(): void {
		$stream = $this->getStream('w+', 13);
		fwrite($stream, '0123456789');
		fseek($stream, 7, SEEK_SET);
		$this->assertEquals(6, fwrite($stream, 'abcdefghijk'));
		rewind($stream);
		$this->assertEquals('0123456abcdef', fread($stream, 100));
	}

	public function testWriteAfterSeekCurWithEnoughSpace(): void {
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

	public function testWriteAfterSeekCurWithNotEnoughSpace(): void {
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
