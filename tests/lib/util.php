<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Util extends PHPUnit_Framework_TestCase {

	// Constructor
	function Test_Util() {
		date_default_timezone_set("UTC");
	}

	function testFormatDate() {
		$result = OC_Util::formatDate(1350129205);
		$expected = 'October 13, 2012 11:53';
		$this->assertEquals($expected, $result);

		$result = OC_Util::formatDate(1102831200, true);
		$expected = 'December 12, 2004';
		$this->assertEquals($expected, $result);
	}

	function testCallRegister() {
		$result = strlen(OC_Util::callRegister());
		$this->assertEquals(20, $result);
	}

	function testSanitizeHTML() {
		$badString = "<script>alert('Hacked!');</script>";
		$result = OC_Util::sanitizeHTML($badString);
		$this->assertEquals("&lt;script&gt;alert(&#039;Hacked!&#039;);&lt;/script&gt;", $result);

		$goodString = "This is an harmless string.";
		$result = OC_Util::sanitizeHTML($goodString);
		$this->assertEquals("This is an harmless string.", $result);
	}

	function testGenerate_random_bytes() {
		$result = strlen(OC_Util::generate_random_bytes(59));
		$this->assertEquals(59, $result);
	}
}