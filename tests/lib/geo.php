<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Geo extends \Test\TestCase {
	
	/**
	 * @medium
	 */
	function testTimezone() {
		$result = OC_Geo::timezone(3, 3);
		$expected = 'Africa/Porto-Novo';
		$this->assertEquals($expected, $result);

		$result = OC_Geo::timezone(-3,-3333);
		$expected = 'Pacific/Enderbury';
		$this->assertEquals($expected, $result);
	}
}
