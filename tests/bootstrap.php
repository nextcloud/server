<?php

require_once __DIR__.'/../lib/base.php';

if(!class_exists('PHPUnit_Framework_TestCase')){
	require_once('PHPUnit/Autoload.php');
}

//SimpleTest compatibility
abstract class UnitTestCase extends PHPUnit_Framework_TestCase{
	function assertEqual($expected, $actual, $string=''){
		$this->assertEquals($expected, $actual, $string);
	}

	function assertNotEqual($expected, $actual, $string=''){
		$this->assertNotEquals($expected, $actual, $string);
	}

	static function assertTrue($actual, $string=''){
		parent::assertTrue((bool)$actual, $string);
	}

	static function assertFalse($actual, $string=''){
		parent::assertFalse((bool)$actual, $string);
	}
}

OC_Hook::clear();
