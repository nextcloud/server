<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_VObject extends PHPUnit_Framework_TestCase {

	public function setUp() {
		Sabre\VObject\Property::$classMap['SUMMARY'] = 'OC\VObject\StringProperty';
	}

	function testStringProperty() {
		$property = Sabre\VObject\Property::create('SUMMARY', 'Escape;this,please');
		$this->assertEquals("SUMMARY:Escape\;this\,please\r\n", $property->serialize());
	}
}