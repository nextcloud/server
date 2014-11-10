<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_VObject extends \Test\TestCase {

	protected function setUp() {
		parent::setUp();

		Sabre\VObject\Property::$classMap['SUMMARY'] = 'OC\VObject\StringProperty';
		Sabre\VObject\Property::$classMap['ORG'] = 'OC\VObject\CompoundProperty';
	}

	function testStringProperty() {
		$property = Sabre\VObject\Property::create('SUMMARY', 'Escape;this,please');
		$this->assertEquals("SUMMARY:Escape\;this\,please\r\n", $property->serialize());
	}

	function testCompoundProperty() {

		$arr = array(
			'ABC, Inc.',
			'North American Division',
			'Marketing;Sales',
		);

		$property = Sabre\VObject\Property::create('ORG');
		$property->setParts($arr);

		$this->assertEquals('ABC\, Inc.;North American Division;Marketing\;Sales', $property->value);
		$this->assertEquals('ORG:ABC\, Inc.;North American Division;Marketing\;Sales' . "\r\n", $property->serialize());
		$this->assertEquals(3, count($property->getParts()));
		$parts = $property->getParts();
		$this->assertEquals('Marketing;Sales', $parts[2]);
	}
}
