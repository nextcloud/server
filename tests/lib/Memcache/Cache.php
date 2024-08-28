<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

abstract class Cache extends \Test\Cache\TestCache {
	/**
	 * @var \OCP\IMemcache cache;
	 */
	protected $instance;

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

	public function testGetArrayAfterSet() {
		$this->assertNull($this->instance->get('foo'));
		$this->instance->set('foo', ['bar']);
		$this->assertEquals(['bar'], $this->instance->get('foo'));
	}

	public function testDoesNotExistAfterRemove() {
		$this->instance->set('foo', 'bar');
		$this->instance->remove('foo');
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testRemoveNonExisting() {
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

	public function testAdd() {
		$this->assertTrue($this->instance->add('foo', 'bar'));
		$this->assertEquals('bar', $this->instance->get('foo'));
		$this->assertFalse($this->instance->add('foo', 'asd'));
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testInc() {
		$this->assertEquals(1, $this->instance->inc('foo'));
		$this->assertEquals(1, $this->instance->get('foo'));
		$this->assertEquals(2, $this->instance->inc('foo'));
		$this->assertEquals(2, $this->instance->get('foo'));
		$this->assertEquals(12, $this->instance->inc('foo', 10));
		$this->assertEquals(12, $this->instance->get('foo'));

		$this->instance->set('foo', 'bar');
		$this->assertFalse($this->instance->inc('foo'));
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testDec() {
		$this->assertFalse($this->instance->dec('foo'));
		$this->instance->set('foo', 20);
		$this->assertEquals(19, $this->instance->dec('foo'));
		$this->assertEquals(19, $this->instance->get('foo'));
		$this->assertEquals(9, $this->instance->dec('foo', 10));

		$this->instance->set('foo', 'bar');
		$this->assertFalse($this->instance->dec('foo'));
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testCasNotChanged() {
		$this->instance->set('foo', 'bar');
		$this->assertTrue($this->instance->cas('foo', 'bar', 'asd'));
		$this->assertEquals('asd', $this->instance->get('foo'));
	}

	public function testCasChanged() {
		$this->instance->set('foo', 'bar1');
		$this->assertFalse($this->instance->cas('foo', 'bar', 'asd'));
		$this->assertEquals('bar1', $this->instance->get('foo'));
	}

	public function testCasNotSet() {
		$this->assertFalse($this->instance->cas('foo', 'bar', 'asd'));
	}

	public function testCadNotChanged() {
		$this->instance->set('foo', 'bar');
		$this->assertTrue($this->instance->cad('foo', 'bar'));
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testCadChanged() {
		$this->instance->set('foo', 'bar1');
		$this->assertFalse($this->instance->cad('foo', 'bar'));
		$this->assertTrue($this->instance->hasKey('foo'));
	}

	public function testCadNotSet() {
		$this->assertFalse($this->instance->cad('foo', 'bar'));
	}

	public function testNcadNotChanged() {
		$this->instance->set('foo', 'bar');
		$this->assertFalse($this->instance->ncad('foo', 'bar'));
		$this->assertTrue($this->instance->hasKey('foo'));
	}

	public function testNcadChanged() {
		$this->instance->set('foo', 'bar1');
		$this->assertTrue($this->instance->ncad('foo', 'bar'));
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testNcadNotSet() {
		$this->assertFalse($this->instance->ncad('foo', 'bar'));
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->clear();
		}

		parent::tearDown();
	}
}
