<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Geo extends UnitTestCase {
	function testTimezone() {
		$result = OC_Geo::timezone(3,3);
		$expected = 'Africa/Porto-Novo';
		$this->assertEquals($result, $expected);

		$result = OC_Geo::timezone(-3,-3333);
		$expected = 'Pacific/Enderbury';
		$this->assertEquals($result, $expected);
	}
}