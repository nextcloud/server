<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

class RetryWrapperTest extends WrapperTest {

	/**
	 * @param resource $source
	 * @return resource
	 */
	protected function wrapSource($source) {
		return \Icewind\Streams\RetryWrapper::wrap(PartialWrapper::wrap($source));
	}

	public function testReadDir() {
		$this->markTestSkipped('directories not supported');
	}

	public function testRewindDir() {
		$this->markTestSkipped('directories not supported');
	}

	public function testFailedRead() {
		$source = fopen('data://text/plain,foo', 'r');
		$wrapped = \Icewind\Streams\RetryWrapper::wrap(FailWrapper::wrap($source));
		$this->assertEquals('', fread($wrapped, 10));
	}

	public function testFailedWrite() {
		$source = fopen('php://temp', 'w');
		$wrapped = \Icewind\Streams\RetryWrapper::wrap(FailWrapper::wrap($source));
		$this->assertFalse((bool)fwrite($wrapped, 'foo'));
	}
}
