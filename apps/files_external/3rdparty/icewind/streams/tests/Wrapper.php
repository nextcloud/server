<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

abstract class Wrapper extends \PHPUnit_Framework_TestCase {
	/**
	 * @param resource $source
	 * @return resource
	 */
	abstract protected function wrapSource($source);

	public function testRead() {
		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$wrapped = $this->wrapSource($source);
		$this->assertEquals('foo', fread($wrapped, 3));
		$this->assertEquals('bar', fread($wrapped, 3));
		$this->assertEquals('', fread($wrapped, 3));
	}

	public function testWrite() {
		$source = fopen('php://temp', 'r+');
		rewind($source);

		$wrapped = $this->wrapSource($source);

		$this->assertEquals(6, fwrite($wrapped, 'foobar'));
		rewind($source);
		$this->assertEquals('foobar', stream_get_contents($source));
	}

	public function testClose() {
		$source = fopen('php://temp', 'r+');
		rewind($source);

		$wrapped = $this->wrapSource($source);

		fclose($wrapped);
		$this->assertFalse(is_resource($source));
	}

	public function testSeekTell() {
		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$wrapped = $this->wrapSource($source);

		$this->assertEquals(0, ftell($wrapped));

		fseek($wrapped, 2);
		$this->assertEquals(2, ftell($source));
		$this->assertEquals(2, ftell($wrapped));

		fseek($wrapped, 2, SEEK_CUR);
		$this->assertEquals(4, ftell($source));
		$this->assertEquals(4, ftell($wrapped));

		fseek($wrapped, -1, SEEK_END);
		$this->assertEquals(5, ftell($source));
		$this->assertEquals(5, ftell($wrapped));
	}

	public function testStat() {
		$source = fopen(__FILE__, 'r+');
		$wrapped = $this->wrapSource($source);
		$this->assertEquals(stat(__FILE__), fstat($wrapped));
	}

	public function testTruncate() {
		if (version_compare(phpversion(), '5.4.0', '<')) {
			$this->markTestSkipped('php <5.4 doesn\'t support truncate for stream wrappers');
		}
		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);
		$wrapped = $this->wrapSource($source);

		ftruncate($wrapped, 2);
		$this->assertEquals('fo', fread($wrapped, 10));
	}

	public function testLock() {
		$source = tmpfile();
		$wrapped = $this->wrapSource($source);
		if (!flock($wrapped, LOCK_EX)) {
			$this->fail('Unable to acquire lock');
		}
	}

	public function testStreamOptions() {
		$source = fopen('php://temp', 'r+');
		$wrapped = $this->wrapSource($source);
		stream_set_blocking($wrapped, 0);
		stream_set_timeout($wrapped, 1, 0);
		stream_set_write_buffer($wrapped, 0);
	}
}
