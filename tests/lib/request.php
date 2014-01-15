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

	/**
	 * @dataProvider userAgentProvider
	 */
	public function testUserAgent($testAgent, $userAgent, $matches) {
		$_SERVER['HTTP_USER_AGENT'] = $testAgent;
		$this->assertEquals($matches, OC_Request::isUserAgent($userAgent));
	}

	function userAgentProvider() {
		return array(
			array(
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
				OC_Request::USER_AGENT_IE,
				true
			),
			array(
				'Mozilla/5.0 (X11; Linux i686; rv:24.0) Gecko/20100101 Firefox/24.0',
				OC_Request::USER_AGENT_IE,
				false
			),
			array(
				'Mozilla/5.0 (Linux; Android 4.4; Nexus 4 Build/KRT16S) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.59 Mobile Safari/537.36',
				OC_Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				true
			),
			array(
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
				OC_Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				false
			),
			// test two values
			array(
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
				array(
					OC_Request::USER_AGENT_IE,
					OC_Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				),
				true
			),
			array(
				'Mozilla/5.0 (Linux; Android 4.4; Nexus 4 Build/KRT16S) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.59 Mobile Safari/537.36',
				array(
					OC_Request::USER_AGENT_IE,
					OC_Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				),
				true
			),
		);
	}
}
