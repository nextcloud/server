<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Request extends PHPUnit_Framework_TestCase {

	public function setUp() {
		OC_Config::setValue('overwritewebroot', '/domain.tld/ownCloud');
	}

	public function tearDown() {
		OC_Config::setValue('overwritewebroot', '');
	}

	public function testScriptNameOverWrite() {
		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		$_SERVER["SCRIPT_FILENAME"] = __FILE__;

		$scriptName = OC_Request::scriptName();
		$this->assertEquals('/domain.tld/ownCloud/tests/lib/request.php', $scriptName);
	}
}
