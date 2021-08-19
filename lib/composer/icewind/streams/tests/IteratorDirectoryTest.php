<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

use \BadMethodCallException;
use PHPUnit\Framework\TestCase;

class IteratorDirectoryTest extends TestCase {

	/**
	 * @param \Iterator | array $source
	 * @return resource
	 */
	protected function wrapSource($source) {
		return \Icewind\Streams\IteratorDirectory::wrap($source);
	}

	public function testNoContext() {
		$this->expectException(BadMethodCallException::class);
		$context = stream_context_create([]);
		stream_wrapper_register('iterator', '\Icewind\Streams\IteratorDirectory');
		try {
			opendir('iterator://', $context);
			stream_wrapper_unregister('iterator');
		} catch (\Exception $e) {
			stream_wrapper_unregister('iterator');
			throw $e;
		}
	}

	public function testNoSource() {
		$this->expectException(BadMethodCallException::class);
		$context = stream_context_create([
			'dir' => [
				'foo' => 'bar'
			]
		]);
		stream_wrapper_register('iterator', '\Icewind\Streams\IteratorDirectory');
		try {
			opendir('iterator://', $context);
			stream_wrapper_unregister('iterator');
		} catch (\Exception $e) {
			stream_wrapper_unregister('iterator');
			throw $e;
		}
	}

	public function testWrapInvalidSource() {
		$this->expectException(BadMethodCallException::class);
		$this->wrapSource(2);
	}

	public function fileListProvider() {
		$longList = array_fill(0, 500, 'foo');
		return [
			[
				[
					'foo',
					'bar',
					'qwerty'
				]
			],
			[
				[
					'with spaces',
					'under_scores',
					'日本語',
					'character %$_',
					'.',
					'0',
					'double "quotes"',
					"single 'quotes'"
				]
			],
			[
				[
					'single item'
				]
			],
			[
				$longList
			],
			[
				[]
			]
		];
	}

	protected function basicTest($fileList, $dh) {
		$result = [];

		while (($file = readdir($dh)) !== false) {
			$result[] = $file;
		}

		$this->assertEquals($fileList, $result);

		rewinddir($dh);
		if (count($fileList)) {
			$this->assertEquals($fileList[0], readdir($dh));
		} else {
			$this->assertFalse(readdir($dh));
		}
	}

	/**
	 * @dataProvider fileListProvider
	 */
	public function testBasicIterator($fileList) {
		$iterator = new \ArrayIterator($fileList);
		$dh = $this->wrapSource($iterator);
		$this->basicTest($fileList, $dh);
	}

	/**
	 * @dataProvider fileListProvider
	 */
	public function testBasicArray($fileList) {
		$dh = $this->wrapSource($fileList);
		$this->basicTest($fileList, $dh);
	}
}
