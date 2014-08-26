<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

class IteratorDirectory extends \PHPUnit_Framework_TestCase {

	/**
	 * @param \Iterator | array $source
	 * @return resource
	 */
	protected function wrapSource($source) {
		return \Icewind\Streams\IteratorDirectory::wrap($source);
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testNoContext() {
		$context = stream_context_create(array());
		stream_wrapper_register('iterator', '\Icewind\Streams\IteratorDirectory');
		try {
			opendir('iterator://', $context);
			stream_wrapper_unregister('iterator');
		} catch (\Exception $e) {
			stream_wrapper_unregister('iterator');
			throw $e;
		}
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testInvalidSource() {
		$context = stream_context_create(array(
			'dir' => array(
				'array' => 2
			)
		));
		stream_wrapper_register('iterator', '\Icewind\Streams\IteratorDirectory');
		try {
			opendir('iterator://', $context);
			stream_wrapper_unregister('iterator');
		} catch (\Exception $e) {
			stream_wrapper_unregister('iterator');
			throw $e;
		}
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testWrapInvalidSource() {
		$this->wrapSource(2);
	}

	public function fileListProvider() {
		$longList = array_fill(0, 500, 'foo');
		return array(
			array(
				array(
					'foo',
					'bar',
					'qwerty'
				)
			),
			array(
				array(
					'with spaces',
					'under_scores',
					'日本語',
					'character %$_',
					'.',
					'0',
					'double "quotes"',
					"single 'quotes'"
				)
			),
			array(
				array(
					'single item'
				)
			),
			array(
				$longList
			),
			array(
				array()
			)
		);
	}

	protected function basicTest($fileList, $dh) {
		$result = array();

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
