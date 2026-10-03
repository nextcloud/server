<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Stream;

use Icewind\Streams\Wrapper;
use OC\Files\Stream\Quota;

class ShortWriteStream extends Wrapper {
	public static function wrap($source) {
		$context = stream_context_create([
			'shortwrite' => [
				'source' => $source,
			],
		]);

		return Wrapper::wrapSource($source, $context, 'shortwrite', self::class);
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->source = $this->loadContext('shortwrite')['source'];
		return true;
	}

	#[\Override]
	public function dir_opendir($path, $options) {
		return false;
	}

	#[\Override]
	public function stream_write($data) {
		if ($data === '') {
			return 0;
		}

		/*
		 * This fixture intentionally handles only the two payloads used by the
		 * regression test: "abc" and "def". PHP may call stream_write() again
		 * with "bc" or "ef" after the deliberate one-byte short write.
		 */
		if ($data[0] !== 'a' && $data[0] !== 'd') {
			// This is the remainder passed after the deliberate short write.
			return 0;
		}

		// Deliberately write exactly one byte.
		return fwrite($this->source, $data[0]);
	}
}

class QuotaTest extends \Test\TestCase {
	/**
	 * @param string $mode
	 * @param int|float $limit
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

	public function testWriteAccountsForBytesActuallyWritten(): void {
		$source = fopen('php://temp', 'w+');
		$stream = Quota::wrap(ShortWriteStream::wrap($source), 3);

		$this->assertSame(1, fwrite($stream, 'abc'));
		$this->assertSame(1, fwrite($stream, 'def'));

		rewind($stream);
		$this->assertSame('ad', fread($stream, 100));
	}

	public function testShortReadOnlyConsumesBytesActuallyRead(): void {
		$source = fopen('php://temp', 'w+');
		fwrite($source, 'abc');
		rewind($source);

		$stream = Quota::wrap($source, 5);

		$this->assertSame('abc', fread($stream, 100));
		$this->assertSame(2, fwrite($stream, 'wxyz'));

		rewind($stream);
		$this->assertSame('abcwx', fread($stream, 100));
	}

	public function testFailedSeekSetDoesNotChangePositionOrQuota(): void {
		$stream = $this->getStream('w+', 3);
		$this->assertSame(1, fwrite($stream, 'a'));

		$this->assertSame(-1, fseek($stream, -1, SEEK_SET));
		$this->assertSame(2, fwrite($stream, 'bcdef'));

		rewind($stream);
		$this->assertSame('abc', fread($stream, 100));
	}

	public function testFailedSeekEndDoesNotChangePositionOrQuota(): void {
		$stream = $this->getStream('w+', 3);
		$this->assertSame(1, fwrite($stream, 'a'));

		$this->assertSame(-1, fseek($stream, -100, SEEK_END));
		$this->assertSame(2, fwrite($stream, 'bcdef'));

		rewind($stream);
		$this->assertSame('abc', fread($stream, 100));
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
		// Seek forward first to exercise the limit calculation.
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

	public function testWriteAfterNegativeRemainingAllowanceIsRejected(): void {
		$stream = $this->getStream('w+', 3);
		$this->assertSame(3, fwrite($stream, 'abc'));

		$this->assertSame(0, fseek($stream, 10, SEEK_SET));
		$this->assertSame(0, fwrite($stream, 'def'));

		rewind($stream);
		$this->assertSame('abc', fread($stream, 100));
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
