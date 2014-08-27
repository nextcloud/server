<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Request extends PHPUnit_Framework_TestCase {

	public function setUp() {
		OC::$server->getConfig()->setSystemValue('overwritewebroot', '/domain.tld/ownCloud');

		OC::$server->getConfig()->setSystemValue('trusted_proxies', array());
		OC::$server->getConfig()->setSystemValue('forwarded_for_headers', array());
	}

	public function tearDown() {
		OC::$server->getConfig()->setSystemValue('overwritewebroot', '');
		OC::$server->getConfig()->setSystemValue('trusted_proxies', array());
		OC::$server->getConfig()->setSystemValue('forwarded_for_headers', array());
	}

	public function testScriptNameOverWrite() {
		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;

		$scriptName = OC_Request::scriptName();
		$this->assertEquals('/domain.tld/ownCloud/tests/lib/request.php', $scriptName);
	}

	public function testGetRemoteAddress() {
		$_SERVER['REMOTE_ADDR'] = '10.0.0.2';
		$_SERVER['HTTP_X_FORWARDED'] = '10.4.0.5, 10.4.0.4';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.0.233';

		// Without having specified a trusted remote address
		$this->assertEquals('10.0.0.2', OC_Request::getRemoteAddress());

		// With specifying a trusted remote address but no trusted header
		OC::$server->getConfig()->setSystemValue('trusted_proxies', array('10.0.0.2'));
		$this->assertEquals('10.0.0.2', OC_Request::getRemoteAddress());

		// With specifying a trusted remote address and trusted headers
		OC::$server->getConfig()->setSystemValue('trusted_proxies', array('10.0.0.2'));
		OC::$server->getConfig()->setSystemValue('forwarded_for_headers', array('HTTP_X_FORWARDED'));
		$this->assertEquals('10.4.0.5', OC_Request::getRemoteAddress());
		OC::$server->getConfig()->setSystemValue('forwarded_for_headers', array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED'));
		$this->assertEquals('192.168.0.233', OC_Request::getRemoteAddress());

		// With specifying multiple trusted remote addresses and trusted headers
		OC::$server->getConfig()->setSystemValue('trusted_proxies', array('10.3.4.2', '10.0.0.2', '127.0.3.3'));
		OC::$server->getConfig()->setSystemValue('forwarded_for_headers', array('HTTP_X_FORWARDED'));
		$this->assertEquals('10.4.0.5', OC_Request::getRemoteAddress());
		OC::$server->getConfig()->setSystemValue('forwarded_for_headers', array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED'));
		$this->assertEquals('192.168.0.233', OC_Request::getRemoteAddress());
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
			array(
				'Mozilla/5.0 (X11; Linux i686; rv:24.0) Gecko/20100101 Firefox/24.0',
				OC_Request::USER_AGENT_FREEBOX,
				false
			),
			array(
				'Mozilla/5.0',
				OC_Request::USER_AGENT_FREEBOX,
				true
			),
			array(
				'Fake Mozilla/5.0',
				OC_Request::USER_AGENT_FREEBOX,
				false
			),
		);
	}

	public function testInsecureServerHost() {
		unset($_SERVER['HTTP_X_FORWARDED_HOST']);
		unset($_SERVER['HTTP_HOST']);
		unset($_SERVER['SERVER_NAME']);
		$_SERVER['SERVER_NAME'] = 'from.server.name:8080';
		$host = OC_Request::insecureServerHost();
		$this->assertEquals('from.server.name:8080', $host);

		$_SERVER['HTTP_HOST'] = 'from.host.header:8080';
		$host = OC_Request::insecureServerHost();
		$this->assertEquals('from.host.header:8080', $host);

		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'from.forwarded.host:8080';
		$host = OC_Request::insecureServerHost();
		$this->assertEquals('from.forwarded.host:8080', $host);

		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'from.forwarded.host2:8080,another.one:9000';
		$host = OC_Request::insecureServerHost();
		$this->assertEquals('from.forwarded.host2:8080', $host);

		// clean up
		unset($_SERVER['HTTP_X_FORWARDED_HOST']);
		unset($_SERVER['HTTP_HOST']);
		unset($_SERVER['SERVER_NAME']);
	}

	public function testGetOverwriteHost() {
		unset($_SERVER['REMOTE_ADDR']);
		OC_Config::deleteKey('overwritecondaddr');
		OC_Config::deleteKey('overwritehost');
		$host = OC_Request::getOverwriteHost();
		$this->assertNull($host);

		OC_Config::setValue('overwritehost', '');
		$host = OC_Request::getOverwriteHost();
		$this->assertNull($host);

		OC_Config::setValue('overwritehost', 'host.one.test:8080');
		$host = OC_Request::getOverwriteHost();
		$this->assertEquals('host.one.test:8080', $host);

		$_SERVER['REMOTE_ADDR'] = 'somehost.test:8080';
		OC_Config::setValue('overwritecondaddr', '^somehost\..*$');
		$host = OC_Request::getOverwriteHost();
		$this->assertEquals('host.one.test:8080', $host);

		OC_Config::setValue('overwritecondaddr', '^somethingelse.*$');
		$host = OC_Request::getOverwriteHost();
		$this->assertNull($host);

		// clean up
		unset($_SERVER['REMOTE_ADDR']);
		OC_Config::deleteKey('overwritecondaddr');
		OC_Config::deleteKey('overwritehost');
	}

	/**
	 * @dataProvider trustedDomainDataProvider
	 */
	public function testIsTrustedDomain($trustedDomains, $testDomain, $result) {
		OC_Config::deleteKey('trusted_domains');
		if ($trustedDomains !== null) {
			OC_Config::setValue('trusted_domains', $trustedDomains);
		}

		$this->assertEquals($result, OC_Request::isTrustedDomain($testDomain));

		// clean up
		OC_Config::deleteKey('trusted_domains');
	}

	public function trustedDomainDataProvider() {
		$trustedHostTestList = array('host.one.test:8080', 'host.two.test:8080');
		return array(
			// empty defaults to true
			array(null, 'host.one.test:8080', true),
			array('', 'host.one.test:8080', true),
			array(array(), 'host.one.test:8080', true),

			// trust list when defined
			array($trustedHostTestList, 'host.two.test:8080', true),
			array($trustedHostTestList, 'host.two.test:9999', false),
			array($trustedHostTestList, 'host.three.test:8080', false),

			// trust localhost regardless of trust list
			array($trustedHostTestList, 'localhost', true),
			array($trustedHostTestList, 'localhost:8080', true),
			array($trustedHostTestList, '127.0.0.1', true),
			array($trustedHostTestList, '127.0.0.1:8080', true),

			// do not trust invalid localhosts
			array($trustedHostTestList, 'localhost:1:2', false),
			array($trustedHostTestList, 'localhost: evil.host', false),
		);
	}

	public function testServerHost() {
		OC_Config::deleteKey('overwritecondaddr');
		OC_Config::setValue('overwritehost', 'overwritten.host:8080');
		OC_Config::setValue(
			'trusted_domains',
			array(
				'trusted.host:8080',
				'second.trusted.host:8080'
			)
		);
		$_SERVER['HTTP_HOST'] = 'trusted.host:8080';

		// CLI always gives localhost
		$oldCLI = OC::$CLI;
		OC::$CLI = true;
		$host = OC_Request::serverHost();
		$this->assertEquals('localhost', $host);
		OC::$CLI = false;

		// overwritehost overrides trusted domain
		$host = OC_Request::serverHost();
		$this->assertEquals('overwritten.host:8080', $host);

		// trusted domain returned when used
		OC_Config::deleteKey('overwritehost');
		$host = OC_Request::serverHost();
		$this->assertEquals('trusted.host:8080', $host);

		// trusted domain returned when untrusted one in header
		$_SERVER['HTTP_HOST'] = 'untrusted.host:8080';
		OC_Config::deleteKey('overwritehost');
		$host = OC_Request::serverHost();
		$this->assertEquals('trusted.host:8080', $host);

		// clean up
		OC_Config::deleteKey('overwritecondaddr');
		OC_Config::deleteKey('overwritehost');
		unset($_SERVER['HTTP_HOST']);
		OC::$CLI = $oldCLI;
	}
}
