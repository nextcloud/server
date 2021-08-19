<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

class CallbackWrapperTest extends WrapperTest {

	/**
	 * @param resource $source
	 * @param callable $read
	 * @param callable $write
	 * @param callable $close
	 * @param callable $readDir
	 * @param callable $preClose
	 * @return resource
	 */
	protected function wrapSource($source, $read = null, $write = null, $close = null, $readDir = null, $preClose = null) {
		return \Icewind\Streams\CallbackWrapper::wrap($source, $read, $write, $close, $readDir, $preClose);
	}

	public function testWrapInvalidSource() {
		$this->expectException(\BadMethodCallException::class);
		$this->wrapSource('foo');
	}

	public function testReadCallback() {
		$called = false;
		$bytesRead = 0;
		$callBack = function ($count) use (&$called, &$bytesRead) {
			$called = true;
			$bytesRead += $count;
		};

		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$wrapped = $this->wrapSource($source, $callBack);
		$this->assertSame('foo', fread($wrapped, 3));
		$this->assertTrue($called);

		$this->assertSame('bar', fread($wrapped, 1000));
		$this->assertSame(6, $bytesRead);
	}

	public function testWriteCallback() {
		$lastData = '';
		$callBack = function ($data) use (&$lastData) {
			$lastData = $data;
		};

		$source = fopen('php://temp', 'r+');

		$wrapped = $this->wrapSource($source, null, $callBack);
		fwrite($wrapped, 'foobar');
		$this->assertSame('foobar', $lastData);
	}

	public function testCloseCallback() {
		$called = false;
		$callBack = function () use (&$called) {
			$called = true;
		};

		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$wrapped = $this->wrapSource($source, null, null, $callBack);
		fclose($wrapped);
		$this->assertTrue($called);
	}

	public function testReadDirCallback() {
		$called = false;
		$callBack = function () use (&$called) {
			$called = true;
		};

		$source = opendir(sys_get_temp_dir());

		$wrapped = $this->wrapSource($source, null, null, null, $callBack);
		readdir($wrapped);
		$this->assertTrue($called);
	}

	public function testPreCloseCallback() {
		$called = false;

		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$callBack = function ($stream) use (&$called, $source) {
			$called = true;
			$this->assertSame($stream, $source);
		};

		$wrapped = $this->wrapSource($source, null, null, null, null, $callBack);
		fclose($wrapped);
		$this->assertTrue($called);
	}
}
