<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

use PHPUnit\Framework\TestCase;

class DirectoryFilterTest extends TestCase {
	public function testFilterAcceptAll() {
		$this->filter(
			['a', 'b', 'c'],
			function () {
				return true;
			},
			['a', 'b', 'c']
		);
	}

	public function testFilterRejectAll() {
		$this->filter(
			['a', 'b', 'c'],
			function () {
				return false;
			},
			[]
		);
	}

	public function testFilterRejectLong() {
		$this->filter(
			['a', 'bb', 'c'],
			function ($file) {
				return strlen($file) < 2;
			},
			['a', 'c']
		);
	}

	private function filter(array $files, callable $filter, array $expected) {
		$source = \Icewind\Streams\IteratorDirectory::wrap($files);
		$filtered = \Icewind\Streams\DirectoryFilter::wrap($source, $filter);
		$result = [];
		while (($file = readdir($filtered)) !== false) {
			$result[] = $file;
		}
		$this->assertEquals($expected, $result);
	}
}
