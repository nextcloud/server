<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Memcache;

abstract class Cache extends \Test_Cache {
	public function testExistsAfterSet() {
		$this->assertFalse($this->instance->hasKey('foo'));
		$this->instance->set('foo', 'bar');
		$this->assertTrue($this->instance->hasKey('foo'));
	}

	public function testGetAfterSet() {
		$this->assertNull($this->instance->get('foo'));
		$this->instance->set('foo', 'bar');
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testDoesNotExistAfterRemove() {
		$this->instance->set('foo', 'bar');
		$this->instance->remove('foo');
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testArrayAccessSet() {
		$this->instance['foo'] = 'bar';
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testArrayAccessGet() {
		$this->instance->set('foo', 'bar');
		$this->assertEquals('bar', $this->instance['foo']);
	}

	public function testArrayAccessExists() {
		$this->assertFalse(isset($this->instance['foo']));
		$this->instance->set('foo', 'bar');
		$this->assertTrue(isset($this->instance['foo']));
	}

	public function testArrayAccessUnset() {
		$this->instance->set('foo', 'bar');
		unset($this->instance['foo']);
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->clear();
		}

		parent::tearDown();
	}
}
