<?php
/**
 * @copyright 2013 Thomas Tanghus (thomas@tanghus.net)
 * @copyright 2016 Lukas Reschke lukas@owncloud.com
 * @copyright 2022 Stanimir Bozhilov (stanimir@audriga.com)
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\Request;
use OC\Security\CSRF\CsrfToken;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\IConfig;
use OCP\IRequestId;

/**
 * Class RequestTest
 *
 * @package OC\AppFramework\Http
 */
class RequestTest extends \Test\TestCase {
	/** @var string */
	protected $stream = 'fakeinput://data';
	/** @var IRequestId */
	protected $requestId;
	/** @var IConfig */
	protected $config;
	/** @var CsrfTokenManager */
	protected $csrfTokenManager;

	protected function setUp(): void {
		parent::setUp();

		if (in_array('fakeinput', stream_get_wrappers())) {
			stream_wrapper_unregister('fakeinput');
		}
		stream_wrapper_register('fakeinput', 'Test\AppFramework\Http\RequestStream');

		$this->requestId = $this->createMock(IRequestId::class);
		$this->config = $this->createMock(IConfig::class);
		$this->csrfTokenManager = $this->getMockBuilder(CsrfTokenManager::class)
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		stream_wrapper_unregister('fakeinput');
		parent::tearDown();
	}

	public function testRequestAccessors() {
		$vars = [
			'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
			'method' => 'GET',
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		// Countable
		$this->assertSame(2, count($request));
		// Array access
		$this->assertSame('Joey', $request['nickname']);
		// "Magic" accessors
		$this->assertSame('Joey', $request->{'nickname'});
		$this->assertTrue(isset($request['nickname']));
		$this->assertTrue(isset($request->{'nickname'}));
		$this->assertFalse(isset($request->{'flickname'}));
		// Only testing 'get', but same approach for post, files etc.
		$this->assertSame('Joey', $request->get['nickname']);
		// Always returns null if variable not set.
		$this->assertSame(null, $request->{'flickname'});
	}

	// urlParams has precedence over POST which has precedence over GET
	public function testPrecedence() {
		$vars = [
			'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
			'post' => ['name' => 'Jane Doe', 'nickname' => 'Janey'],
			'urlParams' => ['user' => 'jw', 'name' => 'Johnny Weissmüller'],
			'method' => 'GET'
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame(3, count($request));
		$this->assertSame('Janey', $request->{'nickname'});
		$this->assertSame('Johnny Weissmüller', $request->{'name'});
	}



	public function testImmutableArrayAccess() {
		$this->expectException(\RuntimeException::class);

		$vars = [
			'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
			'method' => 'GET'
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$request['nickname'] = 'Janey';
	}


	public function testImmutableMagicAccess() {
		$this->expectException(\RuntimeException::class);

		$vars = [
			'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
			'method' => 'GET'
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$request->{'nickname'} = 'Janey';
	}


	public function testGetTheMethodRight() {
		$this->expectException(\LogicException::class);

		$vars = [
			'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
			'method' => 'GET',
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$request->post;
	}

	public function testTheMethodIsRight() {
		$vars = [
			'get' => ['name' => 'John Q. Public', 'nickname' => 'Joey'],
			'method' => 'GET',
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('GET', $request->method);
		$result = $request->get;
		$this->assertSame('John Q. Public', $result['name']);
		$this->assertSame('Joey', $result['nickname']);
	}

	public function testJsonPost() {
		global $data;
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = [
			'method' => 'POST',
			'server' => ['CONTENT_TYPE' => 'application/json; utf-8']
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('POST', $request->method);
		$result = $request->post;
		$this->assertSame('John Q. Public', $result['name']);
		$this->assertSame('Joey', $result['nickname']);
		$this->assertSame('Joey', $request->params['nickname']);
		$this->assertSame('Joey', $request['nickname']);
	}

	public function testScimJsonPost() {
		global $data;
		$data = '{"userName":"testusername", "displayName":"Example User"}';
		$vars = [
			'method' => 'POST',
			'server' => ['CONTENT_TYPE' => 'application/scim+json; utf-8']
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('POST', $request->method);
		$result = $request->post;
		$this->assertSame('testusername', $result['userName']);
		$this->assertSame('Example User', $result['displayName']);
		$this->assertSame('Example User', $request->params['displayName']);
		$this->assertSame('Example User', $request['displayName']);
	}

	public function testCustomJsonPost() {
		global $data;
		$data = '{"propertyA":"sometestvalue", "propertyB":"someothertestvalue"}';

		// Note: the content type used here is fictional and intended to check if the regex for JSON content types works fine
		$vars = [
			'method' => 'POST',
			'server' => ['CONTENT_TYPE' => 'application/custom-type+json; utf-8']
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('POST', $request->method);
		$result = $request->post;
		$this->assertSame('sometestvalue', $result['propertyA']);
		$this->assertSame('someothertestvalue', $result['propertyB']);
	}

	public function notJsonDataProvider() {
		return [
			['this is not valid json'],
			['"just a string"'],
			['{"just a string"}'],
		];
	}

	/**
	 * @dataProvider notJsonDataProvider
	 */
	public function testNotJsonPost($testData) {
		global $data;
		$data = $testData;
		$vars = [
			'method' => 'POST',
			'server' => ['CONTENT_TYPE' => 'application/json; utf-8']
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertEquals('POST', $request->method);
		$result = $request->post;
		// ensure there's no error attempting to decode the content
	}

	public function testNotScimJsonPost() {
		global $data;
		$data = 'this is not valid scim json';
		$vars = [
			'method' => 'POST',
			'server' => ['CONTENT_TYPE' => 'application/scim+json; utf-8']
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertEquals('POST', $request->method);
		$result = $request->post;
		// ensure there's no error attempting to decode the content
	}

	public function testNotCustomJsonPost() {
		global $data;
		$data = 'this is not valid json';
		$vars = [
			'method' => 'POST',
			'server' => ['CONTENT_TYPE' => 'application/custom-type+json; utf-8']
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertEquals('POST', $request->method);
		$result = $request->post;
		// ensure there's no error attempting to decode the content
	}

	public function testPatch() {
		global $data;
		$data = http_build_query(['name' => 'John Q. Public', 'nickname' => 'Joey'], '', '&');

		$vars = [
			'method' => 'PATCH',
			'server' => ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PATCH', $request->method);
		$result = $request->patch;

		$this->assertSame('John Q. Public', $result['name']);
		$this->assertSame('Joey', $result['nickname']);
	}

	public function testJsonPatchAndPut() {
		global $data;

		// PUT content
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = [
			'method' => 'PUT',
			'server' => ['CONTENT_TYPE' => 'application/json; utf-8'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PUT', $request->method);
		$result = $request->put;

		$this->assertSame('John Q. Public', $result['name']);
		$this->assertSame('Joey', $result['nickname']);

		// PATCH content
		$data = '{"name": "John Q. Public", "nickname": null}';
		$vars = [
			'method' => 'PATCH',
			'server' => ['CONTENT_TYPE' => 'application/json; utf-8'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PATCH', $request->method);
		$result = $request->patch;

		$this->assertSame('John Q. Public', $result['name']);
		$this->assertSame(null, $result['nickname']);
	}

	public function testScimJsonPatchAndPut() {
		global $data;

		// PUT content
		$data = '{"userName": "sometestusername", "displayName": "Example User"}';
		$vars = [
			'method' => 'PUT',
			'server' => ['CONTENT_TYPE' => 'application/scim+json; utf-8'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PUT', $request->method);
		$result = $request->put;

		$this->assertSame('sometestusername', $result['userName']);
		$this->assertSame('Example User', $result['displayName']);

		// PATCH content
		$data = '{"userName": "sometestusername", "displayName": null}';
		$vars = [
			'method' => 'PATCH',
			'server' => ['CONTENT_TYPE' => 'application/scim+json; utf-8'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PATCH', $request->method);
		$result = $request->patch;

		$this->assertSame('sometestusername', $result['userName']);
		$this->assertSame(null, $result['displayName']);
	}

	public function testCustomJsonPatchAndPut() {
		global $data;

		// PUT content
		$data = '{"propertyA": "sometestvalue", "propertyB": "someothertestvalue"}';
		$vars = [
			'method' => 'PUT',
			'server' => ['CONTENT_TYPE' => 'application/custom-type+json; utf-8'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PUT', $request->method);
		$result = $request->put;

		$this->assertSame('sometestvalue', $result['propertyA']);
		$this->assertSame('someothertestvalue', $result['propertyB']);

		// PATCH content
		$data = '{"propertyA": "sometestvalue", "propertyB": null}';
		$vars = [
			'method' => 'PATCH',
			'server' => ['CONTENT_TYPE' => 'application/custom-type+json; utf-8'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PATCH', $request->method);
		$result = $request->patch;

		$this->assertSame('sometestvalue', $result['propertyA']);
		$this->assertSame(null, $result['propertyB']);
	}

	public function testPutStream() {
		global $data;
		$data = file_get_contents(__DIR__ . '/../../../data/testimage.png');

		$vars = [
			'put' => $data,
			'method' => 'PUT',
			'server' => [
				'CONTENT_TYPE' => 'image/png',
				'CONTENT_LENGTH' => (string)strlen($data)
			],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('PUT', $request->method);
		$resource = $request->put;
		$contents = stream_get_contents($resource);
		$this->assertSame($data, $contents);

		try {
			$resource = $request->put;
		} catch (\LogicException $e) {
			return;
		}
		$this->fail('Expected LogicException.');
	}


	public function testSetUrlParameters() {
		$vars = [
			'post' => [],
			'method' => 'POST',
			'urlParams' => ['id' => '2'],
		];

		$request = new Request(
			$vars,
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$newParams = ['id' => '3', 'test' => 'test2'];
		$request->setUrlParameters($newParams);
		$this->assertSame('test2', $request->getParam('test'));
		$this->assertEquals('3', $request->getParam('id'));
		$this->assertEquals('3', $request->getParams()['id']);
	}

	public function testGetRemoteAddressWithoutTrustedRemote() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies')
			->willReturn([]);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('10.0.0.2', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithNoTrustedHeader() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)->willReturnOnConsecutiveCalls(
				['10.0.0.2'],
				[]
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('10.0.0.2', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithSingleTrustedRemote() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)-> willReturnOnConsecutiveCalls(
				['10.0.0.2'],
				['HTTP_X_FORWARDED'],
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('10.4.0.4', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithMultipleTrustedRemotes() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->willReturnMap([
				['trusted_proxies', [], ['10.0.0.2', '::1']],
				['forwarded_for_headers', ['HTTP_X_FORWARDED_FOR'], ['HTTP_X_FORWARDED']],
			]);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4, ::1',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('10.4.0.4', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressIPv6WithSingleTrustedRemote() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)-> willReturnOnConsecutiveCalls(
				['2001:db8:85a3:8d3:1319:8a2e:370:7348'],
				['HTTP_X_FORWARDED'],
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '2001:db8:85a3:8d3:1319:8a2e:370:7348',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('10.4.0.4', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressVerifyPriorityHeader() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)-> willReturnOnConsecutiveCalls(
				['10.0.0.2'],
				[
					'HTTP_X_FORWARDED',
					'HTTP_X_FORWARDED_FOR',
					'HTTP_CLIENT_IP',
				],
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '10.0.0.2',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('192.168.0.233', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressIPv6VerifyPriorityHeader() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)-> willReturnOnConsecutiveCalls(
				['2001:db8:85a3:8d3:1319:8a2e:370:7348'],
				[
					'HTTP_X_FORWARDED',
					'HTTP_X_FORWARDED_FOR',
					'HTTP_CLIENT_IP',
				],
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '2001:db8:85a3:8d3:1319:8a2e:370:7348',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('192.168.0.233', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithMatchingCidrTrustedRemote() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)-> willReturnOnConsecutiveCalls(
				['192.168.2.0/24'],
				['HTTP_X_FORWARDED_FOR'],
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '192.168.2.99',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('192.168.0.233', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithNotMatchingCidrTrustedRemote() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies')
			->willReturn(['192.168.2.0/24']);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '192.168.3.99',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('192.168.3.99', $request->getRemoteAddress());
	}

	public function testGetRemoteIpv6AddressWithMatchingIpv6CidrTrustedRemote() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers']
			)->willReturnOnConsecutiveCalls(
				['2001:db8:85a3:8d3:1319:8a20::/95'],
				['HTTP_X_FORWARDED_FOR']
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '2001:db8:85a3:8d3:1319:8a21:370:7348',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('192.168.0.233', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressIpv6WithNotMatchingCidrTrustedRemote() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies')
			->willReturn(['fd::/8']);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '2001:db8:85a3:8d3:1319:8a2e:370:7348',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('2001:db8:85a3:8d3:1319:8a2e:370:7348', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressIpv6WithInvalidTrustedProxy() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies')
			->willReturn(['fx::/8']);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '2001:db8:85a3:8d3:1319:8a2e:370:7348',
					'HTTP_X_FORWARDED' => '10.4.0.5, 10.4.0.4',
					'HTTP_X_FORWARDED_FOR' => '192.168.0.233'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('2001:db8:85a3:8d3:1319:8a2e:370:7348', $request->getRemoteAddress());
	}

	public function testGetRemoteAddressWithXForwardedForIPv6() {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValue')
			->withConsecutive(
				['trusted_proxies'],
				['forwarded_for_headers'],
			)-> willReturnOnConsecutiveCalls(
				['192.168.2.0/24'],
				['HTTP_X_FORWARDED_FOR'],
			);

		$request = new Request(
			[
				'server' => [
					'REMOTE_ADDR' => '192.168.2.99',
					'HTTP_X_FORWARDED_FOR' => '[2001:db8:85a3:8d3:1319:8a2e:370:7348]',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('2001:db8:85a3:8d3:1319:8a2e:370:7348', $request->getRemoteAddress());
	}

	/**
	 * @return array
	 */
	public function httpProtocolProvider() {
		return [
			// Valid HTTP 1.0
			['HTTP/1.0', 'HTTP/1.0'],
			['http/1.0', 'HTTP/1.0'],
			['HTTp/1.0', 'HTTP/1.0'],

			// Valid HTTP 1.1
			['HTTP/1.1', 'HTTP/1.1'],
			['http/1.1', 'HTTP/1.1'],
			['HTTp/1.1', 'HTTP/1.1'],

			// Valid HTTP 2.0
			['HTTP/2', 'HTTP/2'],
			['http/2', 'HTTP/2'],
			['HTTp/2', 'HTTP/2'],

			// Invalid
			['HTTp/394', 'HTTP/1.1'],
			['InvalidProvider/1.1', 'HTTP/1.1'],
			[null, 'HTTP/1.1'],
			['', 'HTTP/1.1'],

		];
	}

	/**
	 * @dataProvider httpProtocolProvider
	 *
	 * @param mixed $input
	 * @param string $expected
	 */
	public function testGetHttpProtocol($input, $expected) {
		$request = new Request(
			[
				'server' => [
					'SERVER_PROTOCOL' => $input,
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($expected, $request->getHttpProtocol());
	}

	public function testGetServerProtocolWithOverride() {
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueString')
			->willReturnMap([
				['overwriteprotocol', '', 'customProtocol'],
				['overwritecondaddr', '', ''],
			]);

		$request = new Request(
			[],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('customProtocol', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithProtoValid() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				}

				return $default;
			});

		$requestHttps = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'HtTpS',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);
		$requestHttp = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'HTTp',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);


		$this->assertSame('https', $requestHttps->getServerProtocol());
		$this->assertSame('http', $requestHttp->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueOn() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTPS' => 'on'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);
		$this->assertSame('https', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueOff() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTPS' => 'off'
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

	public function testGetServerProtocolWithHttpsServerValueEmpty() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTPS' => ''
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

	public function testGetServerProtocolDefault() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$request = new Request(
			[],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);
		$this->assertSame('http', $request->getServerProtocol());
	}

	public function testGetServerProtocolBehindLoadBalancers() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				}

				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_PROTO' => 'https,http,http',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
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
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($matches, $request->isUserAgent($userAgent));
	}

	/**
	 * @dataProvider userAgentProvider
	 * @param string $testAgent
	 * @param array $userAgent
	 * @param bool $matches
	 */
	public function testUndefinedUserAgent($testAgent, $userAgent, $matches) {
		$request = new Request(
			[],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertFalse($request->isUserAgent($userAgent));
	}

	/**
	 * @return array
	 */
	public function userAgentProvider() {
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
				'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36',
				[
					Request::USER_AGENT_CHROME
				],
				true,
			],
			[
				'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/53.0.2785.143 Chrome/53.0.2785.143 Safari/537.36',
				[
					Request::USER_AGENT_CHROME
				],
				true,
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
			[
				'Mozilla/5.0 (Android) ownCloud-android/2.0.0',
				[
					Request::USER_AGENT_CLIENT_ANDROID
				],
				true,
			],
			[
				'Mozilla/5.0 (Android) Nextcloud-android/2.0.0',
				[
					Request::USER_AGENT_CLIENT_ANDROID
				],
				true,
			],
			[
				'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.99 Safari/537.36 Vivaldi/2.9.1705.41',
				[
					Request::USER_AGENT_CHROME
				],
				true
			],
			[
				'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.38 Safari/537.36 Brave/75',
				[
					Request::USER_AGENT_CHROME
				],
				true
			],
			[
				'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36 OPR/50.0.2762.67',
				[
					Request::USER_AGENT_CHROME
				],
				true
			]
		];
	}

	public function dataMatchClientVersion(): array {
		return [
			[
				'Mozilla/5.0 (Android) Nextcloud-android/3.24.1',
				Request::USER_AGENT_CLIENT_ANDROID,
				'3.24.1',
			],
			[
				'Mozilla/5.0 (iOS) Nextcloud-iOS/4.8.2',
				Request::USER_AGENT_CLIENT_IOS,
				'4.8.2',
			],
			[
				'Mozilla/5.0 (Windows) mirall/3.8.1',
				Request::USER_AGENT_CLIENT_DESKTOP,
				'3.8.1',
			],
			[
				'Mozilla/5.0 (Android) Nextcloud-Talk v17.10.0',
				Request::USER_AGENT_TALK_ANDROID,
				'17.10.0',
			],
			[
				'Mozilla/5.0 (iOS) Nextcloud-Talk v17.0.1',
				Request::USER_AGENT_TALK_IOS,
				'17.0.1',
			],
			[
				'Mozilla/5.0 (Windows) Nextcloud-Talk v0.6.0',
				Request::USER_AGENT_TALK_DESKTOP,
				'0.6.0',
			],
			[
				'Mozilla/5.0 (Windows) Nextcloud-Outlook v1.0.0',
				Request::USER_AGENT_OUTLOOK_ADDON,
				'1.0.0',
			],
			[
				'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v1.0.0',
				Request::USER_AGENT_THUNDERBIRD_ADDON,
				'1.0.0',
			],
		];
	}

	/**
	 * @dataProvider dataMatchClientVersion
	 * @param string $testAgent
	 * @param string $userAgent
	 * @param string $version
	 */
	public function testMatchClientVersion(string $testAgent, string $userAgent, string $version): void {
		preg_match($userAgent, $testAgent, $matches);

		$this->assertSame($version, $matches[1]);
	}

	public function testInsecureServerHostServerNameHeader() {
		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('from.server.name:8080', $request->getInsecureServerHost());
	}

	public function testInsecureServerHostHttpHostHeader() {
		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
					'HTTP_HOST' => 'from.host.header:8080',
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('from.host.header:8080', $request->getInsecureServerHost());
	}

	public function testInsecureServerHostHttpFromForwardedHeaderSingle() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				}

				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
					'HTTP_HOST' => 'from.host.header:8080',
					'HTTP_X_FORWARDED_HOST' => 'from.forwarded.host:8080',
					'REMOTE_ADDR' => '1.2.3.4',
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('from.forwarded.host:8080', $request->getInsecureServerHost());
	}

	public function testInsecureServerHostHttpFromForwardedHeaderStacked() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				}

				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'SERVER_NAME' => 'from.server.name:8080',
					'HTTP_HOST' => 'from.host.header:8080',
					'HTTP_X_FORWARDED_HOST' => 'from.forwarded.host2:8080,another.one:9000',
					'REMOTE_ADDR' => '1.2.3.4',
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('from.forwarded.host2:8080', $request->getInsecureServerHost());
	}

	public function testGetServerHostWithOverwriteHost() {
		$this->config
			->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'overwritecondaddr') {
					return '';
				} elseif ($key === 'overwritehost') {
					return 'my.overwritten.host';
				}

				return $default;
			});

		$request = new Request(
			[],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('my.overwritten.host', $request->getServerHost());
	}

	public function testGetServerHostWithTrustedDomain() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				} elseif ($key === 'trusted_domains') {
					return ['my.trusted.host'];
				}

				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_HOST' => 'my.trusted.host',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('my.trusted.host', $request->getServerHost());
	}

	public function testGetServerHostWithUntrustedDomain() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				} elseif ($key === 'trusted_domains') {
					return ['my.trusted.host'];
				}

				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_HOST' => 'my.untrusted.host',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('my.trusted.host', $request->getServerHost());
	}

	public function testGetServerHostWithNoTrustedDomain() {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				}
				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_HOST' => 'my.untrusted.host',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('', $request->getServerHost());
	}

	/**
	 * @return array
	 */
	public function dataGetServerHostTrustedDomain() {
		return [
			'is array' => ['my.trusted.host', ['my.trusted.host']],
			'is array but undefined index 0' => ['my.trusted.host', [2 => 'my.trusted.host']],
			'is string' => ['my.trusted.host', 'my.trusted.host'],
			'is null' => ['', null],
		];
	}

	/**
	 * @dataProvider dataGetServerHostTrustedDomain
	 * @param $expected
	 * @param $trustedDomain
	 */
	public function testGetServerHostTrustedDomain($expected, $trustedDomain) {
		$this->config
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) use ($trustedDomain) {
				if ($key === 'trusted_proxies') {
					return ['1.2.3.4'];
				}
				if ($key === 'trusted_domains') {
					return $trustedDomain;
				}
				return $default;
			});

		$request = new Request(
			[
				'server' => [
					'HTTP_X_FORWARDED_HOST' => 'my.untrusted.host',
					'REMOTE_ADDR' => '1.2.3.4',
				],
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($expected, $request->getServerHost());
	}

	public function testGetOverwriteHostDefaultNull() {
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('overwritehost')
			->willReturn('');
		$request = new Request(
			[],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertNull(self::invokePrivate($request, 'getOverwriteHost'));
	}

	public function testGetOverwriteHostWithOverwrite() {
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueString')
			->willReturnMap([
				['overwritehost', '', 'www.owncloud.org'],
				['overwritecondaddr', '', ''],
			]);

		$request = new Request(
			[],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('www.owncloud.org', self::invokePrivate($request, 'getOverwriteHost'));
	}


	public function testGetPathInfoNotProcessible() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('The requested uri(/foo.php) cannot be processed by the script \'/var/www/index.php\')');

		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => '/foo.php',
					'SCRIPT_NAME' => '/var/www/index.php',
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$request->getPathInfo();
	}


	public function testGetRawPathInfoNotProcessible() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('The requested uri(/foo.php) cannot be processed by the script \'/var/www/index.php\')');

		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => '/foo.php',
					'SCRIPT_NAME' => '/var/www/index.php',
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
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
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($expected, $request->getPathInfo());
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
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($expected, $request->getRawPathInfo());
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
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($expected, $request->getRawPathInfo());
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
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame($expected, $request->getPathInfo());
	}

	/**
	 * @return array
	 */
	public function genericPathInfoProvider() {
		return [
			['/core/index.php?XDEBUG_SESSION_START=14600', '/core/index.php', ''],
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
			->method('getSystemValueString')
			->with('overwritewebroot')
			->willReturn('');

		$request = new Request(
			[
				'server' => [
					'REQUEST_URI' => '/test.php'
				]
			],
			$this->requestId,
			$this->config,
			$this->csrfTokenManager,
			$this->stream
		);

		$this->assertSame('/test.php', $request->getRequestUri());
	}

	public function providesGetRequestUriWithOverwriteData() {
		return [
			['/scriptname.php/some/PathInfo', '/owncloud/', ''],
			['/scriptname.php/some/PathInfo', '/owncloud/', '123'],
		];
	}

	/**
	 * @dataProvider providesGetRequestUriWithOverwriteData
	 */
	public function testGetRequestUriWithOverwrite($expectedUri, $overwriteWebRoot, $overwriteCondAddr) {
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['overwritewebroot', '', $overwriteWebRoot],
				['overwritecondaddr', '', $overwriteCondAddr],
			]);

		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'REQUEST_URI' => '/test.php/some/PathInfo',
						'SCRIPT_NAME' => '/test.php',
					]
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$request
			->expects($this->once())
			->method('getScriptName')
			->willReturn('/scriptname.php');

		$this->assertSame($expectedUri, $request->getRequestUri());
	}

	public function testPassesCSRFCheckWithGet() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'get' => [
						'requesttoken' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						'nc_sameSiteCookiestrict' => 'true',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$token = new CsrfToken('AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds');
		$this->csrfTokenManager
			->expects($this->once())
			->method('isTokenValid')
			->with($token)
			->willReturn(true);

		$this->assertTrue($request->passesCSRFCheck());
	}

	public function testPassesCSRFCheckWithPost() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'post' => [
						'requesttoken' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						'nc_sameSiteCookiestrict' => 'true',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$token = new CsrfToken('AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds');
		$this->csrfTokenManager
			->expects($this->once())
			->method('isTokenValid')
			->with($token)
			->willReturn(true);

		$this->assertTrue($request->passesCSRFCheck());
	}

	public function testPassesCSRFCheckWithHeader() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						'nc_sameSiteCookiestrict' => 'true',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$token = new CsrfToken('AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds');
		$this->csrfTokenManager
			->expects($this->once())
			->method('isTokenValid')
			->with($token)
			->willReturn(true);

		$this->assertTrue($request->passesCSRFCheck());
	}

	public function testPassesCSRFCheckWithGetAndWithoutCookies() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'get' => [
						'requesttoken' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$this->csrfTokenManager
			->expects($this->once())
			->method('isTokenValid')
			->willReturn(true);

		$this->assertTrue($request->passesCSRFCheck());
	}

	public function testPassesCSRFCheckWithPostAndWithoutCookies() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'post' => [
						'requesttoken' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$this->csrfTokenManager
			->expects($this->once())
			->method('isTokenValid')
			->willReturn(true);

		$this->assertTrue($request->passesCSRFCheck());
	}

	public function testPassesCSRFCheckWithHeaderAndWithoutCookies() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$this->csrfTokenManager
			->expects($this->once())
			->method('isTokenValid')
			->willReturn(true);

		$this->assertTrue($request->passesCSRFCheck());
	}

	public function testFailsCSRFCheckWithHeaderAndNotAllChecksPassing() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookiestrict' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$this->csrfTokenManager
			->expects($this->never())
			->method('isTokenValid');

		$this->assertFalse($request->passesCSRFCheck());
	}

	public function testPassesStrictCookieCheckWithAllCookiesAndStrict() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName', 'getCookieParams'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'__Host-nc_sameSiteCookiestrict' => 'true',
						'__Host-nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$request
			->expects($this->any())
			->method('getCookieParams')
			->willReturn([
				'secure' => true,
				'path' => '/',
			]);

		$this->assertTrue($request->passesStrictCookieCheck());
	}

	public function testFailsStrictCookieCheckWithAllCookiesAndMissingStrict() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName', 'getCookieParams'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookiestrict' => 'true',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$request
			->expects($this->any())
			->method('getCookieParams')
			->willReturn([
				'secure' => true,
				'path' => '/',
			]);

		$this->assertFalse($request->passesStrictCookieCheck());
	}

	public function testGetCookieParams() {
		/** @var Request $request */
		$request = $this->getMockBuilder(Request::class)
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$actual = $request->getCookieParams();
		$this->assertSame(session_get_cookie_params(), $actual);
	}

	public function testPassesStrictCookieCheckWithAllCookies() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookiestrict' => 'true',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertTrue($request->passesStrictCookieCheck());
	}

	public function testPassesStrictCookieCheckWithRandomCookies() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						'RandomCookie' => 'asdf',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertTrue($request->passesStrictCookieCheck());
	}

	public function testFailsStrictCookieCheckWithSessionCookie() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertFalse($request->passesStrictCookieCheck());
	}

	public function testFailsStrictCookieCheckWithRememberMeCookie() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						'nc_token' => 'asdf',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertFalse($request->passesStrictCookieCheck());
	}

	public function testFailsCSRFCheckWithPostAndWithCookies() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'post' => [
						'requesttoken' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'foo' => 'bar',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();
		$this->csrfTokenManager
			->expects($this->never())
			->method('isTokenValid');

		$this->assertFalse($request->passesCSRFCheck());
	}

	public function testFailStrictCookieCheckWithOnlyLaxCookie() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertFalse($request->passesStrictCookieCheck());
	}

	public function testFailStrictCookieCheckWithOnlyStrictCookie() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookiestrict' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertFalse($request->passesStrictCookieCheck());
	}

	public function testPassesLaxCookieCheck() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookielax' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertTrue($request->passesLaxCookieCheck());
	}

	public function testFailsLaxCookieCheckWithOnlyStrictCookie() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookiestrict' => 'true',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertFalse($request->passesLaxCookieCheck());
	}

	public function testSkipCookieCheckForOCSRequests() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => 'AAAHGxsTCTc3BgMQESAcNR0OAR0=:MyTotalSecretShareds',
						'HTTP_OCS_APIREQUEST' => 'true',
					],
					'cookies' => [
						session_name() => 'asdf',
						'nc_sameSiteCookiestrict' => 'false',
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertTrue($request->passesStrictCookieCheck());
	}

	/**
	 * @return array
	 */
	public function invalidTokenDataProvider() {
		return [
			['InvalidSentToken'],
			['InvalidSentToken:InvalidSecret'],
			[''],
		];
	}

	/**
	 * @dataProvider invalidTokenDataProvider
	 * @param string $invalidToken
	 */
	public function testPassesCSRFCheckWithInvalidToken($invalidToken) {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[
					'server' => [
						'HTTP_REQUESTTOKEN' => $invalidToken,
					],
				],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$token = new CsrfToken($invalidToken);
		$this->csrfTokenManager
			->expects($this->any())
			->method('isTokenValid')
			->with($token)
			->willReturn(false);

		$this->assertFalse($request->passesCSRFCheck());
	}

	public function testPassesCSRFCheckWithoutTokenFail() {
		/** @var Request $request */
		$request = $this->getMockBuilder('\OC\AppFramework\Http\Request')
			->setMethods(['getScriptName'])
			->setConstructorArgs([
				[],
				$this->requestId,
				$this->config,
				$this->csrfTokenManager,
				$this->stream
			])
			->getMock();

		$this->assertFalse($request->passesCSRFCheck());
	}
}
