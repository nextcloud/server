<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Cache;

abstract class TestCache extends \Test\TestCase {
	/**
	 * @var \OCP\ICache cache;
	 */
	protected $instance;

	protected function tearDown() {
		if($this->instance) {
			$this->instance->clear();
		}

		parent::tearDown();
	}

	function testSimple() {
		$this->assertNull($this->instance->get('value1'));
		$this->assertFalse($this->instance->hasKey('value1'));
		
		$value='foobar';
		$this->instance->set('value1', $value);
		$this->assertTrue($this->instance->hasKey('value1'));
		$received=$this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');
		$value='ipsum lorum';
		$this->instance->set('value1', $value);
		$received=$this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value not overwritten by second set');

		$value2='foobar';
		$this->instance->set('value2', $value2);
		$received2=$this->instance->get('value2');
		$this->assertTrue($this->instance->hasKey('value1'));
		$this->assertTrue($this->instance->hasKey('value2'));
		$this->assertEquals($value, $received, 'Value changed while setting other variable');
		$this->assertEquals($value2, $received2, 'Second value not equal to original');

		$this->assertFalse($this->instance->hasKey('not_set'));
		$this->assertNull($this->instance->get('not_set'), 'Unset value not equal to null');

		$this->assertTrue($this->instance->remove('value1'));
		$this->assertFalse($this->instance->hasKey('value1'));
	}

	function testClear() {
		$value='ipsum lorum';
		$this->instance->set('1_value1', $value);
		$this->instance->set('1_value2', $value);
		$this->instance->set('2_value1', $value);
		$this->instance->set('3_value1', $value);

		$this->assertTrue($this->instance->clear('1_'));
		$this->assertFalse($this->instance->hasKey('1_value1'));
		$this->assertFalse($this->instance->hasKey('1_value2'));
		$this->assertTrue($this->instance->hasKey('2_value1'));
		$this->assertTrue($this->instance->hasKey('3_value1'));

		$this->assertTrue($this->instance->clear());
		$this->assertFalse($this->instance->hasKey('1_value1'));
		$this->assertFalse($this->instance->hasKey('1_value2'));
		$this->assertFalse($this->instance->hasKey('2_value1'));
		$this->assertFalse($this->instance->hasKey('3_value1'));
	}
}
