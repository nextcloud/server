<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Cache;

use OCP\Cache\CappedMemoryCache;

/**
 * Class CappedMemoryCacheTest
 *
 * @package Test\Cache
 */
class CappedMemoryCacheTest extends TestCache {
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new CappedMemoryCache();
	}

	public function testSetOverCap(): void {
		$instance = new CappedMemoryCache(3);

		$instance->set('1', 'a');
		$instance->set('2', 'b');
		$instance->set('3', 'c');
		$instance->set('4', 'd');
		$instance->set('5', 'e');

		$this->assertFalse($instance->hasKey('1'));
		$this->assertFalse($instance->hasKey('2'));
		$this->assertTrue($instance->hasKey('3'));
		$this->assertTrue($instance->hasKey('4'));
		$this->assertTrue($instance->hasKey('5'));
	}

	public function testClear(): void {
		$value = 'ipsum lorum';
		$this->instance->set('1_value1', $value);
		$this->instance->set('1_value2', $value);
		$this->instance->set('2_value1', $value);
		$this->instance->set('3_value1', $value);

		$this->assertTrue($this->instance->clear());
		$this->assertFalse($this->instance->hasKey('1_value1'));
		$this->assertFalse($this->instance->hasKey('1_value2'));
		$this->assertFalse($this->instance->hasKey('2_value1'));
		$this->assertFalse($this->instance->hasKey('3_value1'));
	}

	public function testIndirectSet(): void {
		$this->instance->set('array', []);

		$this->instance['array'][] = 'foo';

		$this->assertEquals(['foo'], $this->instance->get('array'));

		$this->instance['array']['bar'] = 'qwerty';

		$this->assertEquals(['foo', 'bar' => 'qwerty'], $this->instance->get('array'));
	}
}
