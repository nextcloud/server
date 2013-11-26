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

	/**
	 * @dataProvider rawPathInfoProvider
	 * @param $expected
	 * @param $requestUri
	 * @param $scriptName
	 */
	public function testRawPathInfo($expected, $requestUri, $scriptName) {
		$_SERVER['REQUEST_URI'] = $requestUri;
		$_SERVER['SCRIPT_NAME'] = $scriptName;
		$rawPathInfo = OC_Request::getRawPathInfo();
		$this->assertEquals($expected, $rawPathInfo);
	}

	function rawPathInfoProvider() {
		return array(
			array('/core/ajax/translations.php', 'index.php/core/ajax/translations.php', 'index.php'),
			array('/core/ajax/translations.php', '/index.php/core/ajax/translations.php', '/index.php'),
			array('/core/ajax/translations.php', '//index.php/core/ajax/translations.php', '/index.php'),
			array('', '/oc/core', '/oc/core/index.php'),
			array('', '/oc/core/', '/oc/core/index.php'),
			array('', '/oc/core/index.php', '/oc/core/index.php'),
			array('/core/ajax/translations.php', '/core/ajax/translations.php', 'index.php'),
			array('/core/ajax/translations.php', '//core/ajax/translations.php', '/index.php'),
			array('/core/ajax/translations.php', '/oc/core/ajax/translations.php', '/oc/index.php'),
			array('/1', '/oc/core/1', '/oc/core/index.php'),
		);
	}

	/**
	 * @dataProvider rawPathInfoThrowsExceptionProvider
	 * @expectedException Exception
	 *
	 * @param $requestUri
	 * @param $scriptName
	 */
	public function testRawPathInfoThrowsException($requestUri, $scriptName) {
		$_SERVER['REQUEST_URI'] = $requestUri;
		$_SERVER['SCRIPT_NAME'] = $scriptName;
		OC_Request::getRawPathInfo();
	}

	function rawPathInfoThrowsExceptionProvider() {
		return array(
			array('/oc/core1', '/oc/core/index.php'),
		);
	}
}
