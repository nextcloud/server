<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

class CallbackWrapper extends Wrapper {

	/**
	 * @param resource $source
	 * @param callable $read
	 * @param callable $write
	 * @param callable $close
	 * @return resource
	 */
	protected function wrapSource($source, $read = null, $write = null, $close = null) {
		return \Icewind\Streams\CallbackWrapper::wrap($source, $read, $write, $close);
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testWrapInvalidSource() {
		$this->wrapSource('foo');
	}

	public function testReadCallback() {
		$called = false;
		$callBack = function () use (&$called) {
			$called = true;
		};

		$source = fopen('php://temp', 'r+');
		fwrite($source, 'foobar');
		rewind($source);

		$wrapped = $this->wrapSource($source, $callBack);
		$this->assertEquals('foo', fread($wrapped, 3));
		$this->assertTrue($called);
	}

	public function testWriteCallback() {
		$lastData = '';
		$callBack = function ($data) use (&$lastData) {
			$lastData = $data;
		};

		$source = fopen('php://temp', 'r+');

		$wrapped = $this->wrapSource($source, null, $callBack);
		fwrite($wrapped, 'foobar');
		$this->assertEquals('foobar', $lastData);
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
}
