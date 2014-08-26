<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

class NullWrapper extends Wrapper {

	/**
	 * @param resource $source
	 * @return resource
	 */
	protected function wrapSource($source) {
		return \Icewind\Streams\NullWrapper::wrap($source);
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testNoContext() {
		stream_wrapper_register('null', '\Icewind\Streams\NullWrapper');
		$context = stream_context_create(array());
		try {
			fopen('null://', 'r+', false, $context);
			stream_wrapper_unregister('null');
		} catch (\Exception $e) {
			stream_wrapper_unregister('null');
			throw $e;
		}
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testNoSource() {
		stream_wrapper_register('null', '\Icewind\Streams\NullWrapper');
		$context = stream_context_create(array(
			'null' => array(
				'source' => 'bar'
			)
		));
		try {
			fopen('null://', 'r+', false, $context);
		} catch (\Exception $e) {
			stream_wrapper_unregister('null');
			throw $e;
		}
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testWrapInvalidSource() {
		$this->wrapSource('foo');
	}
}
