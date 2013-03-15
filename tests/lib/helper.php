<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Helper extends PHPUnit_Framework_TestCase {

	function testHumanFileSize() {
		$result = OC_Helper::humanFileSize(0);
		$expected = '0 B';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::humanFileSize(1024);
		$expected = '1 kB';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::humanFileSize(10000000);
		$expected = '9.5 MB';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::humanFileSize(500000000000);
		$expected = '465.7 GB';
		$this->assertEquals($result, $expected);
	}

	function testComputerFileSize() {
		$result = OC_Helper::computerFileSize("0 B");
		$expected = '0.0';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::computerFileSize("1 kB");
		$expected = '1024.0';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::computerFileSize("9.5 MB");
		$expected = '9961472.0';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::computerFileSize("465.7 GB");
		$expected = '500041567436.8';
		$this->assertEquals($result, $expected);
	}

	function testGetMimeType() {
		$dir=OC::$SERVERROOT.'/tests/data';
		$result = OC_Helper::getMimeType($dir."/");
		$expected = 'httpd/unix-directory';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/data.tar.gz");
		$expected = 'application/x-gzip';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/data.zip");
		$expected = 'application/zip';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/logo-wide.svg");
		$expected = 'image/svg+xml';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/logo-wide.png");
		$expected = 'image/png';
		$this->assertEquals($result, $expected);
	}

	function testGetStringMimeType() {
		$result = OC_Helper::getStringMimeType("/data/data.tar.gz");
		$expected = 'text/plain; charset=us-ascii';
		$this->assertEquals($result, $expected);
	}

	function testIssubdirectory() {
		$result = OC_Helper::issubdirectory("./data/", "/anotherDirectory/");
		$this->assertFalse($result);

		$result = OC_Helper::issubdirectory("./data/", "./data/");
		$this->assertTrue($result);

		mkdir("data/TestSubdirectory", 0777);
		$result = OC_Helper::issubdirectory("data/TestSubdirectory/", "data");
		rmdir("data/TestSubdirectory");
		$this->assertTrue($result);
	}

	function testMb_array_change_key_case() {
		$arrayStart = array(
			"Foo" => "bar",
			"Bar" => "foo",
			);
		$arrayResult = array(
			"foo" => "bar",
			"bar" => "foo",
			);
		$result = OC_Helper::mb_array_change_key_case($arrayStart);
		$expected = $arrayResult;
		$this->assertEquals($result, $expected);

		$arrayStart = array(
			"foo" => "bar",
			"bar" => "foo",
			);
		$arrayResult = array(
			"FOO" => "bar",
			"BAR" => "foo",
			);
		$result = OC_Helper::mb_array_change_key_case($arrayStart, MB_CASE_UPPER);
		$expected = $arrayResult;
		$this->assertEquals($result, $expected);	
	}

	function testMb_substr_replace() {
		$result = OC_Helper::mb_substr_replace("This  is a teststring", "string", 5);
		$expected = "This string is a teststring";
		$this->assertEquals($result, $expected);
	}

	function testMb_str_replace() {
		$result = OC_Helper::mb_str_replace("teststring", "string", "This is a teststring");
		$expected = "This is a string";
		$this->assertEquals($result, $expected);
	}

	function testRecursiveArraySearch() {
		$haystack = array(
			"Foo" => "own",
			"Bar" => "Cloud",
			);

		$result = OC_Helper::recursiveArraySearch($haystack, "own");
		$expected = "Foo";
		$this->assertEquals($result, $expected);

		$result = OC_Helper::recursiveArraySearch($haystack, "NotFound");
		$this->assertFalse($result);
	}
}
