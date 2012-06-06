<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

abstract class Test_Cache extends UnitTestCase {
	/**
	 * @var OC_Cache cache;
	 */
	protected $instance;

	public function tearDown(){
		$this->instance->clear();
	}

	function testSimple(){
		$this->assertNull($this->instance->get('value1'));
		$this->assertFalse($this->instance->hasKey('value1'));
		
		$value='foobar';
		$this->instance->set('value1',$value);
		$this->assertTrue($this->instance->hasKey('value1'));
		$received=$this->instance->get('value1');
		$this->assertEqual($value,$received,'Value recieved from cache not equal to the original');
		$value='ipsum lorum';
		$this->instance->set('value1',$value);
		$received=$this->instance->get('value1');
		$this->assertEqual($value,$received,'Value not overwritten by second set');

		$value2='foobar';
		$this->instance->set('value2',$value2);
		$received2=$this->instance->get('value2');
		$this->assertTrue($this->instance->hasKey('value1'));
		$this->assertTrue($this->instance->hasKey('value2'));
		$this->assertEqual($value,$received,'Value changed while setting other variable');
		$this->assertEqual($value2,$received2,'Second value not equal to original');

		$this->assertFalse($this->instance->hasKey('not_set'));
		$this->assertNull($this->instance->get('not_set'),'Unset value not equal to null');

		$this->assertTrue($this->instance->remove('value1'));
	}

	function testTTL(){
		$value='foobar';
		$this->instance->set('value1',$value,1);
		$value2='foobar';
		$this->instance->set('value2',$value2);
		sleep(2);
		$this->assertFalse($this->instance->hasKey('value1'));
		$this->assertNull($this->instance->get('value1'));
		$this->assertTrue($this->instance->hasKey('value2'));
		$this->assertEqual($value2,$this->instance->get('value2'));
	}
}
