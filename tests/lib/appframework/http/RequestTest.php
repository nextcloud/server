<?php
/**
 * @copyright 2013 Thomas Tanghus (thomas@tanghus.net)
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\AppFramework\Http;

use OCP\Security\ISecureRandom;
use OCP\IConfig;

/**
 * Class RequestTest
 *
 * @package OC\AppFramework\Http
 */
class RequestTest extends \Test\TestCase {
	/** @var string */
	protected $stream = 'fakeinput://data';
	/** @var ISecureRandom */
	protected $secureRandom;
	/** @var IConfig */
	protected $config;

	protected function setUp() {
		parent::setUp();

		require_once __DIR__ . '/requeststream.php';
		if (in_array('fakeinput', stream_get_wrappers())) {
			stream_wrapper_unregister('fakeinput');
		}
		stream_wrapper_register('fakeinput', 'RequestStream');

		$this->secureRandom = $this->getMockBuilder('\OCP\Security\ISecureRandom')->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
	}

	protected function tearDown() {
		stream_wrapper_unregister('fakeinput');
		parent::tearDown();
	}

	public function testRequestAccessors() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		// Countable
		$this->assertEquals(2, count($request));
		// Array access
		$this->assertEquals('Joey', $request['nickname']);
		// "Magic" accessors
		$this->assertEquals('Joey', $request->{'nickname'});
		$this->assertTrue(isset($request['nickname']));
		$this->assertTrue(isset($request->{'nickname'}));
		$this->assertEquals(false, isset($request->{'flickname'}));
		// Only testing 'get', but same approach for post, files etc.
		$this->assertEquals('Joey', $request->get['nickname']);
		// Always returns null if variable not set.
		$this->assertEquals(null, $request->{'flickname'});

	}

	// urlParams has precedence over POST which has precedence over GET
	public function testPrecedence() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'post' => array('name' => 'Jane Doe', 'nickname' => 'Janey'),
			'urlParams' => array('user' => 'jw', 'name' => 'Johnny Weissmüller'),
			'method' => 'GET'
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals(3, count($request));
		$this->assertEquals('Janey', $request->{'nickname'});
		$this->assertEquals('Johnny Weissmüller', $request->{'name'});
	}


	/**
	* @expectedException \RuntimeException
	*/
	public function testImmutableArrayAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET'
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request['nickname'] = 'Janey';
	}

	/**
	* @expectedException \RuntimeException
	*/
	public function testImmutableMagicAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET'
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request->{'nickname'} = 'Janey';
	}

	/**
	* @expectedException \LogicException
	*/
	public function testGetTheMethodRight() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request->post;
	}

	public function testTheMethodIsRight() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('GET', $request->method);
		$result = $request->get;
		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
	}

	public function testJsonPost() {
		global $data;
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = array(
			'method' => 'POST',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8')
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('POST', $request->method);
		$result = $request->post;
		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
		$this->assertEquals('Joey', $request->params['nickname']);
		$this->assertEquals('Joey', $request['nickname']);
	}

	public function testPatch() {
		global $data;
		$data = http_build_query(array('name' => 'John Q. Public', 'nickname' => 'Joey'), '', '&');

		$vars = array(
			'method' => 'PATCH',
			'server' => array('CONTENT_TYPE' => 'application/x-www-form-urlencoded'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PATCH', $request->method);
		$result = $request->patch;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
	}

	public function testJsonPatchAndPut() {
		global $data;

		// PUT content
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = array(
			'method' => 'PUT',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PUT', $request->method);
		$result = $request->put;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);

		// PATCH content
		$data = '{"name": "John Q. Public", "nickname": null}';
		$vars = array(
			'method' => 'PATCH',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PATCH', $request->method);
		$result = $request->patch;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals(null, $result['nickname']);
	}

	public function testPutStream() {
		global $data;
		$data = file_get_contents(__DIR__ . '/../../../data/testimage.png');

		$vars = array(
			'put' => $data,
			'method' => 'PUT',
			'server' => array('CONTENT_TYPE' => 'image/png'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('PUT', $request->method);
		$resource = $request->put;
		$contents = stream_get_contents($resource);
		$this->assertEquals($data, $contents);

		try {
			$resource = $request->put;
		} catch(\LogicException $e) {
			return;
		}
		$this->fail('Expected LogicException.');

	}


	public function testSetUrlParameters() {
		$vars = array(
			'post' => array(),
			'method' => 'POST',
			'urlParams' => array('id' => '2'),
		);

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$newParams = array('id' => '3', 'test' => 'test2');
		$request->setUrlParameters($newParams);
		$this->assertEquals('test2', $request->getParam('test'));
		$this->assertEquals('3', $request->getParam('id'));
		$this->assertEquals('3', $request->getParams()['id']);
	}

	public function testGetIdWithModUnique() {
		$vars = [
			'server' => [
				'UNIQUE_ID' => 'GeneratedUniqueIdByModUnique'
			],
		];

		$request = new Request(
			$vars,
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('GeneratedUniqueIdByModUnique', $request->getId());
	}

	public function testGetIdWithoutModUnique() {
		$lowRandomSource = $this->getMockBuilder('\OCP\Security\ISecureRandom')
			->disableOriginalConstructor()->getMock();
		$lowRandomSource->expects($this->once())
			->method('generate')
			->with('20')
			->will($this->returnValue('GeneratedByOwnCloudItself'));

		$this->secureRandom
			->expects($this->once())
			->method('getLowStrengthGenerator')
			->will($this->returnValue($lowRandomSource));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('GeneratedByOwnCloudItself', $request->getId());
	}

	public function testGetIdWithoutModUniqueStable() {
		$request = new Request(
			[],
			\OC::$server->getSecureRandom(),
			$this->config,
			$this->stream
		);
		$firstId = $request->getId();
		$secondId = $request->getId();
		$this->assertSame($firstId, $secondId);
	}

	public function testGetRemoteAddressWithoutTrustedRemote() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue([]));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('10.0.0.2', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithNoTrustedHeader() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue(['10.0.0.2']));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('forwarded_for_headers')
			->will($this->returnValue([]));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('10.0.0.2', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithSingleTrustedRemote() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue(['10.0.0.2']));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('forwarded_for_headers')
			->will($this->returnValue(['HTTP_X_FORWARDED']));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('10.4.0.5', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressVerifyPriorityHeader() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('trusted_proxies')
			->will($this->returnValue(['10.0.0.2']));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('forwarded_for_headers')
			->will($this->returnValue([
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED'
			]));

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('192.168.0.233', $request->getRemoteAddress());
	}

	public function testGetServerProtocolWithOverride() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue('customProtocol'));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('overwritecondaddr')
			->will($this->returnValue(''));
		$this->config
			->expects($this->at(2))
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue('customProtocol'));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('customProtocol', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithProtoValid() {
		$this->config
				->expects($this->exactly(2))
				->method('getSystemValue')
				->with('overwriteprotocol')
				->will($this->returnValue(''));

		$requestHttps = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'HtTpS'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$requestHttp = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'HTTp'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);


		$this->assertSame('https', $requestHttps->getServerProtocol());
		$this->assertSame('http', $requestHttp->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueOn() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[
				'server' => [
					'HTTPS' => 'on'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame('https', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueOff() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[
				'server' => [
					'HTTPS' => 'off'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

	public function testGetServerProtocolDefault() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

	public function testGetServerProtocolBehindLoadBalancers() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwriteprotocol')
			->will($this->returnValue(''));

		$request = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'https,http,http'
				],
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('https', $request->getServerProtocol());
	}

	/**
	 * @dataProvider userAgentProvider
	 * @param string $testAgent
	 * @param array $userAgent
	 * @param bool $matches
	 */
	public function testUserAgent($testAgent, $userAgent, $matches) {
		$request = new Request(
			[
				'server' => [
					'HTTP_USER_AGENT' => $testAgent,
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals($matches, $request->isUserAgent($userAgent));
	}

	/**
	 * @return array
	 */
	function userAgentProvider() {
		return [
			[
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
				[
					Request::USER_AGENT_IE
				],
				true,
			],
			[
				'Mozilla/5.0 (X11; Linux i686; rv:24.0) Gecko/20100101 Firefox/24.0',
				[
					Request::USER_AGENT_IE
				],
				false,
			],
			[
				'Mozilla/5.0 (Linux; Android 4.4; Nexus 4 Build/KRT16S) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.59 Mobile Safari/537.36',
				[
					Request::USER_AGENT_ANDROID_MOBILE_CHROME
				],
				true,
			],
			[
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
				[
					Request::USER_AGENT_ANDROID_MOBILE_CHROME
				],
				false,
			],
			[
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
				[
					Request::USER_AGENT_IE,
					Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				],
				true,
			],
			[
				'Mozilla/5.0 (Linux; Android 4.4; Nexus 4 Build/KRT16S) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.59 Mobile Safari/537.36',
				[
					Request::USER_AGENT_IE,
					Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				],
				true,
			],
			[
				'Mozilla/5.0 (X11; Linux i686; rv:24.0) Gecko/20100101 Firefox/24.0',
				[
					Request::USER_AGENT_FREEBOX
				],
				false,
			],
			[
				'Mozilla/5.0',
				[
					Request::USER_AGENT_FREEBOX
				],
				true,
			],
			[
				'Fake Mozilla/5.0',
				[
					Request::USER_AGENT_FREEBOX
				],
				false,
			],
		];
	}

	public function testInsecureServerHostServerNameHeader() {
		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('from.server.name:8080',  $request->getInsecureServerHost());
	}

	public function testInsecureServerHostHttpHostHeader() {
		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
					'HTTP_HOST' => 'from.host.header:8080',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('from.host.header:8080',  $request->getInsecureServerHost());
	}

	public function testInsecureServerHostHttpFromForwardedHeaderSingle() {
		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
					'HTTP_HOST' => 'from.host.header:8080',
					'HTTP_X_FORWARDED_HOST' => 'from.forwarded.host:8080',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('from.forwarded.host:8080',  $request->getInsecureServerHost());
	}

	public function testInsecureServerHostHttpFromForwardedHeaderStacked() {
		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
					'HTTP_HOST' => 'from.host.header:8080',
					'HTTP_X_FORWARDED_HOST' => 'from.forwarded.host2:8080,another.one:9000',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('from.forwarded.host2:8080',  $request->getInsecureServerHost());
	}

	public function testGetServerHost() {
		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('localhost',  $request->getServerHost());
	}

	public function testGetOverwriteHostDefaultNull() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwritehost')
			->will($this->returnValue(''));
		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertNull(self::invokePrivate($request, 'getOverwriteHost'));
	}

	public function testGetOverwriteHostWithOverwrite() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('overwritehost')
			->will($this->returnValue('www.owncloud.org'));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('overwritecondaddr')
			->will($this->returnValue(''));
		$this->config
			->expects($this->at(2))
			->method('getSystemValue')
			->with('overwritehost')
			->will($this->returnValue('www.owncloud.org'));

		$request = new Request(
			[],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('www.owncloud.org', self::invokePrivate($request, 'getOverwriteHost'));
	}

	public function testGetPathInfoWithSetEnv() {
		$request = new Request(
			[
				'server' => [
					'PATH_INFO' => 'apps/files/',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals('apps/files/',  $request->getPathInfo());
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage The requested uri(/foo.php) cannot be processed by the script '/var/www/index.php')
	 */
	public function testGetPathInfoNotProcessible() {
		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => '/foo.php',
					'SCRIPT_NAME' => '/var/www/index.php',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request->getPathInfo();
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage The requested uri(/foo.php) cannot be processed by the script '/var/www/index.php')
	 */
	public function testGetRawPathInfoNotProcessible() {
		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => '/foo.php',
					'SCRIPT_NAME' => '/var/www/index.php',
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$request->getRawPathInfo();
	}

	/**
	 * @dataProvider genericPathInfoProvider
	 * @param string $requestUri
	 * @param string $scriptName
	 * @param string $expected
	 */
	public function testGetPathInfoWithoutSetEnvGeneric($requestUri, $scriptName, $expected) {
		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => $requestUri,
					'SCRIPT_NAME' => $scriptName,
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals($expected, $request->getPathInfo());
	}

	/**
	 * @dataProvider genericPathInfoProvider
	 * @param string $requestUri
	 * @param string $scriptName
	 * @param string $expected
	 */
	public function testGetRawPathInfoWithoutSetEnvGeneric($requestUri, $scriptName, $expected) {
		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => $requestUri,
					'SCRIPT_NAME' => $scriptName,
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals($expected, $request->getRawPathInfo());
	}

	/**
	 * @dataProvider rawPathInfoProvider
	 * @param string $requestUri
	 * @param string $scriptName
	 * @param string $expected
	 */
	public function testGetRawPathInfoWithoutSetEnv($requestUri, $scriptName, $expected) {
		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => $requestUri,
					'SCRIPT_NAME' => $scriptName,
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals($expected, $request->getRawPathInfo());
	}

	/**
	 * @dataProvider pathInfoProvider
	 * @param string $requestUri
	 * @param string $scriptName
	 * @param string $expected
	 */
	public function testGetPathInfoWithoutSetEnv($requestUri, $scriptName, $expected) {
		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => $requestUri,
					'SCRIPT_NAME' => $scriptName,
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertEquals($expected, $request->getPathInfo());
	}

	/**
	 * @return array
	 */
	public function genericPathInfoProvider() {
		return [
			['/index.php/apps/files/', 'index.php', '/apps/files/'],
			['/index.php/apps/files/../&amp;/&?someQueryParameter=QueryParam', 'index.php', '/apps/files/../&amp;/&'],
			['/remote.php/漢字編碼方法 / 汉字编码方法', 'remote.php', '/漢字編碼方法 / 汉字编码方法'],
			['///removeTrailin//gSlashes///', 'remote.php', '/removeTrailin/gSlashes/'],
			['/', '/', ''],
			['', '', ''],
		];
	}

	/**
	 * @return array
	 */
	public function rawPathInfoProvider() {
		return [
			['/foo%2Fbar/subfolder', '', 'foo%2Fbar/subfolder'],
		];
	}

	/**
	 * @return array
	 */
	public function pathInfoProvider() {
		return [
			['/foo%2Fbar/subfolder', '', 'foo/bar/subfolder'],
		];
	}

	public function testGetRequestUriWithoutOverwrite() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('overwritewebroot')
			->will($this->returnValue(''));

		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => '/test.php'
				]
			],
			$this->secureRandom,
			$this->config,
			$this->stream
		);

		$this->assertSame('/test.php', $request->getRequestUri());
	}

	public function testGetRequestUriWithOverwrite() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('overwritewebroot')
			->will($this->returnValue('/owncloud/'));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('overwritecondaddr')
			->will($this->returnValue(''));

		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'REQUEST_URI' => '/test.php/some/PathInfo',
						'SCRIPT_NAME' => '/test.php',
					]
				],
				$this->secureRandom,
				$this->config,
				$this->stream
			])
			->getMock();
		$request
			->expects($this->once())
			->method('getScriptName')
			->will($this->returnValue('/scriptname.php'));

		$this->assertSame('/scriptname.php/some/PathInfo', $request->getRequestUri());
	}
}
