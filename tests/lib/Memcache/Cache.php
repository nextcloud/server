<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OCP\IMemcache;

abstract class Cache extends \Test\Cache\TestCache {
	/**
	 * @var IMemcache cache;
	 */
	protected $instance;

	/**
	 * @return array<string, array{0: mixed}>
 	*/
	public static function roundtripValuesProvider(): array {
		return [
			'string' => ['some string value'],
			'empty string' => [''],
			'numeric string' => ['0123'],
			'float-like string' => ['3.14'],
			'scientific notation string' => ['1e3'],
			'integer' => [1234],
			'zero' => [0],
			'negative integer' => [-42],
			'float' => [3.14],
			'zero-fraction float' => [1.0],
			'negative float' => [-2.75],
			'scientific notation float' => [1.2e3],
			'boolean true' => [true],
			'boolean false' => [false],
			'null' => [null],
			'list' => [['a', 'b', 'c']],
			'associative array' => [['foo' => 'bar', 'baz' => 42]],
			'mixed nested array' => [[
				'int' => 7,
				'float' => 3.14,
				'whole-float' => 1.0,
				'string' => '3.14',
				'bool' => true,
				'null' => null,
				'list' => [1, 2.5, '3'],
			]],
		];
	}

	public function testExistsAfterSet(): void {
		$this->assertFalse($this->instance->hasKey('foo'));
		$this->instance->set('foo', 'bar');
		$this->assertTrue($this->instance->hasKey('foo'));
	}

	public function testGetAfterSet(): void {
		$this->assertNull($this->instance->get('foo'));
		$this->instance->set('foo', 'bar');
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testGetArrayAfterSet(): void {
		$this->assertNull($this->instance->get('foo'));
		$this->instance->set('foo', ['bar']);
		$this->assertEquals(['bar'], $this->instance->get('foo'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('roundtripValuesProvider')]
	public function testStoreAndReadBack(mixed $value): void {
		$this->assertNull($this->instance->get('roundtrip'), 'key should be empty before storing');

		$this->assertNotFalse($this->instance->set('roundtrip', $value), 'value should be stored');
		$this->assertTrue($this->instance->hasKey('roundtrip'), 'stored key should exist');
		$this->assertSame($value, $this->instance->get('roundtrip'), 'stored value should be read back unchanged');
	}

	public function testDoesNotExistAfterRemove(): void {
		$this->instance->set('foo', 'bar');
		$this->instance->remove('foo');
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testRemoveNonExisting(): void {
		$this->instance->remove('foo');
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testArrayAccessSet(): void {
		$this->instance['foo'] = 'bar';
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testArrayAccessGet(): void {
		$this->instance->set('foo', 'bar');
		$this->assertEquals('bar', $this->instance['foo']);
	}

	public function testArrayAccessExists(): void {
		$this->assertFalse(isset($this->instance['foo']));
		$this->instance->set('foo', 'bar');
		$this->assertTrue(isset($this->instance['foo']));
	}

	public function testArrayAccessUnset(): void {
		$this->instance->set('foo', 'bar');
		unset($this->instance['foo']);
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testAdd(): void {
		$this->assertTrue($this->instance->add('foo', 'bar'));
		$this->assertEquals('bar', $this->instance->get('foo'));
		$this->assertFalse($this->instance->add('foo', 'asd'));
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testInc(): void {
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

	public function testDec(): void {
		$this->assertFalse($this->instance->dec('foo'));
		$this->instance->set('foo', 20);
		$this->assertEquals(19, $this->instance->dec('foo'));
		$this->assertEquals(19, $this->instance->get('foo'));
		$this->assertEquals(9, $this->instance->dec('foo', 10));

		$this->instance->set('foo', 'bar');
		$this->assertFalse($this->instance->dec('foo'));
		$this->assertEquals('bar', $this->instance->get('foo'));
	}

	public function testCasNotChanged(): void {
		$this->instance->set('foo', 'bar');
		$this->assertTrue($this->instance->cas('foo', 'bar', 'asd'));
		$this->assertEquals('asd', $this->instance->get('foo'));
	}

	public function testCasChanged(): void {
		$this->instance->set('foo', 'bar1');
		$this->assertFalse($this->instance->cas('foo', 'bar', 'asd'));
		$this->assertEquals('bar1', $this->instance->get('foo'));
	}

	public function testCasNotSet(): void {
		$this->assertFalse($this->instance->cas('foo', 'bar', 'asd'));
	}

	public function testCadNotChanged(): void {
		$this->instance->set('foo', 'bar');
		$this->assertTrue($this->instance->cad('foo', 'bar'));
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testCadChanged(): void {
		$this->instance->set('foo', 'bar1');
		$this->assertFalse($this->instance->cad('foo', 'bar'));
		$this->assertTrue($this->instance->hasKey('foo'));
	}

	public function testCadNotSet(): void {
		$this->assertFalse($this->instance->cad('foo', 'bar'));
	}

	public function testNcadNotChanged(): void {
		$this->instance->set('foo', 'bar');
		$this->assertFalse($this->instance->ncad('foo', 'bar'));
		$this->assertTrue($this->instance->hasKey('foo'));
	}

	public function testNcadChanged(): void {
		$this->instance->set('foo', 'bar1');
		$this->assertTrue($this->instance->ncad('foo', 'bar'));
		$this->assertFalse($this->instance->hasKey('foo'));
	}

	public function testNcadNotSet(): void {
		$this->assertFalse($this->instance->ncad('foo', 'bar'));
	}

	#[\Override]
	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->clear();
		}

		parent::tearDown();
	}
}
