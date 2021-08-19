<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

use PHPUnit\Framework\TestCase;

abstract class WrapperTest extends TestCase {
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
		$this->assertSame('foo', fread($wrapped, 3));
		$this->assertSame('bar', fread($wrapped, 3));
		$this->assertSame('', fread($wrapped, 3));
	}

	public function testWrite() {
		$source = fopen('php://temp', 'r+');
		rewind($source);

		$wrapped = $this->wrapSource($source);

		$this->assertSame(6, fwrite($wrapped, 'foobar'));
		rewind($source);
		$this->assertSame('foobar', stream_get_contents($source));
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

		$this->assertSame(0, ftell($wrapped));

		fseek($wrapped, 2);
		$this->assertSame(2, ftell($source));
		$this->assertSame(2, ftell($wrapped));

		fseek($wrapped, 2, SEEK_CUR);
		$this->assertSame(4, ftell($source));
		$this->assertSame(4, ftell($wrapped));

		fseek($wrapped, -1, SEEK_END);
		$this->assertSame(5, ftell($source));
		$this->assertSame(5, ftell($wrapped));
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
		$this->assertSame('fo', fread($wrapped, 10));
	}

	public function testLock() {
		$source = tmpfile();
		$wrapped = $this->wrapSource($source);
		if (!flock($wrapped, LOCK_EX)) {
			$this->fail('Unable to acquire lock');
		}
		$this->assertTrue(true);
	}

	public function testStreamOptions() {
		$source = fopen('php://temp', 'r+');
		$wrapped = $this->wrapSource($source);
		stream_set_blocking($wrapped, 0);
		stream_set_timeout($wrapped, 1, 0);
		stream_set_write_buffer($wrapped, 0);
		$this->assertTrue(true);
	}

	public function testReadDir() {
		$source = opendir(__DIR__);
		$content = [];
		while (($name = readdir($source)) !== false) {
			$content[] = $name;
		}
		closedir($source);

		$source = opendir(__DIR__);
		$wrapped = $this->wrapSource($source);
		$wrappedContent = [];
		while (($name = readdir($wrapped)) !== false) {
			$wrappedContent[] = $name;
		}
		$this->assertEquals($content, $wrappedContent);
	}

	public function testRewindDir() {
		$source = opendir(__DIR__);
		$content = [];
		while (($name = readdir($source)) !== false) {
			$content[] = $name;
		}
		closedir($source);

		$source = opendir(__DIR__);
		$wrapped = $this->wrapSource($source);
		$this->assertSame($content[0], readdir($wrapped));
		$this->assertSame($content[1], readdir($wrapped));
		$this->assertSame($content[2], readdir($wrapped));
		rewinddir($wrapped);
		$this->assertSame($content[0], readdir($wrapped));
	}

	public function testDoubleClose() {
		$source = fopen('php://temp', 'r+');
		rewind($source);

		$wrapped = $this->wrapSource($source);

		fclose($source);
		fclose($wrapped);
		$this->assertFalse(is_resource($source));
	}
}
