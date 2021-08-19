<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

use PHPUnit\Framework\TestCase;

class SeekableWrapperTest extends TestCase {
	/**
	 * @param resource $source
	 * @return resource
	 */
	protected function wrapSource($source) {
		return \Icewind\Streams\SeekableWrapper::wrap($source);
	}

	protected function getSource() {
		$source = fopen('php://temp', 'w+');
		fwrite($source, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
		fseek($source, 0);
		return $source;
	}

	public function testCantWrapDir() {
		$source = opendir(__DIR__);
		$this->assertFalse(@$this->wrapSource($source));
	}

	public function testSourceNotSeeked() {
		$source = $this->getSource();
		$wrapped = $this->wrapSource($source);
		fseek($wrapped, 6);
		$this->assertSame(6, ftell($source));
		$this->assertSame(6, ftell($wrapped));
		$this->assertSame('ipsum', fread($wrapped, '5'));
		fseek($wrapped, 6);
		$this->assertSame(6, ftell($wrapped));
		$this->assertGreaterThan(6, ftell($source));
	}

	public function testSeekRelative() {
		$source = $this->getSource();
		$wrapped = $this->wrapSource($source);
		fseek($wrapped, 6);
		fseek($wrapped, 6, SEEK_CUR);
		$this->assertSame(12, ftell($source));
		$this->assertSame(12, ftell($wrapped));
		$this->assertSame('dolor', fread($wrapped, '5'));
	}
}
