<?php
/**
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @copyright Copyright (c) 2023, Nextcloud GmbH
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License
 * as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version. 
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\CorsPlugin;
use OCP\IUserSession;
use OCP\IUser;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use stdClass;
use Test\TestCase;

class CorsPluginTest extends TestCase {
	/**
	 * @var IUserSession|MockObject
	 */
	private $userSession;

	/**
	 * @var IConfig|MockObject
	 */
	private $config;

	public function setUp(): void {
		parent::setUp();
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->overwriteService('AllConfig', $this->config);
	}

	public function tearDown(): void {
		$this->restoreService('AllConfig');
	}

	public function dataInit() {
		return [
			'missing origin will be ignored' => [
				[],
				'',
				'',
				false
			],
			'invalid null origin will be ignored' => [
				['Origin' => null],
				'/',
				'http://cloud.example.com/',
				false,
			],
			'invalid empty origin will be ignored' => [
				['Origin' => ''],
				'/',
				'http://cloud.example.com/',
				false,
			],
			'invalid null string origin will be ignored' => [
				['Origin' => 'null'],
				'/',
				'http://cloud.example.com/',
				false,
			],
			'invalid moz schema origin will be ignored' => [
				['Origin' => 'moz-extension://domain.tld'],
				'/',
				'http://cloud.example.com/',
				false,
			],
			'invalid chrome schema origin will be ignored' => [
				['Origin' => 'chrome-extension://domain.tld'],
				'/',
				'http://cloud.example.com/',
				false,
			],
			'same origin will be ignored' => [
				['Origin' => 'http://cloud.example.com'],
				'remote.php/dav/some/service',
				'http://cloud.example.com/remote.php/dav/some/service',
				false,
			],
			'different sub domain will register' => [
				['Origin' => 'http://www.example.com'],
				'remote.php/dav/some/service',
				'http://cloud.example.com/remote.php/dav/some/service',
				true,
			],
			'valid origin will register' => [
				['Origin' => 'http://domain.tld'],
				'remote.php/dav/some/service',
				'http://cloud.example.com/remote.php/dav/some/service',
				true,
			],
		];
	}

	/**
	 * @dataProvider dataInit
	 */
	public function testInit($requestHeaders, $requestUrl, $requestAbsolutUrl, $shouldRegister) {
		$request = new \Sabre\HTTP\Request('OPTIONS', $requestUrl, $requestHeaders);
		$request->setAbsoluteUrl($requestAbsolutUrl);

		/**
		 * @var MockObject|Server
		 */
		$server = $this->createMock(Server::class);
		$server->httpRequest = $request;
		$server->expects($this->exactly($shouldRegister ? 3 : 0))->method('on');

		$plugin = new CorsPlugin($this->userSession, $this->config);

		$plugin->initialize($server);
	}

	public function dataOnException() {
		return [
			'with allowed domain' => [
				['http://some.domain'],
				true,
			],
			'without allowed domain' => [
				['http://other.domain'],
				false,
			],
			'without any domain' => [
				[],
				false,
			],
		];
	}

	/**
	 * @dataProvider dataOnException
	 */
	public function testOnException(array $allowedDomains, bool $shouldHaveCORS) {
		$request = new \Sabre\HTTP\Request('OPTIONS', 'dav/action', ['Origin' => 'http://some.domain']);
		$request->setAbsoluteUrl('http://cloud.example.com/dav/action');

		$response = new \Sabre\HTTP\Response(200);

		$server = $this->createMock(Server::class);
		$server->httpRequest = $request;
		$server->httpResponse = $response;

		$exception = $this->createMock(\Throwable::class);

		$this->config->method('getSystemValue')->willReturnCallback(fn($name, $default) => match(true) {
			$name === 'cors.allowed-domains' => $allowedDomains,
			default => $default,
		});

		$plugin = new CorsPlugin($this->userSession, $this->config);
		$plugin->initialize($server);

		$plugin->onException($exception);
		$this->assertTrue($response->hasHeader('Access-Control-Allow-Origin') === $shouldHaveCORS);
	}

	public function dataSetOptionsHeaders() {
		return [
			'skip requests with authorization header for CSRF' => [
				// headers of the request
				['Authorization' => 'Basic YWxhZGRpbjpvcGVuc2VzYW1l', 'Origin' => 'http://some.domain'],
				// expected return value of the function
				null,
				// should the response have the cors header
				false,
				// should the response be sent directly (meaning skipped)
				false,
				//response status
				500,
			],
			'without authorization header the OPTIONS request is answered' => [
				// headers of the request
				['Origin' => 'http://some.domain'],
				// expected return value of the function
				false,
				// should the response have the cors header
				true,
				// should the response be sent directly (meaning skipped)
				true,
				//response status
				200,
			]
		];
	}

	/**
	 * @dataProvider dataSetOptionsHeaders
	 */
	public function testSetOptionsHeaders($requestHeaders, $expectedReturn, $shouldHaveCORS, $shouldSendResponse, $responseStatus) {
		$request = new \Sabre\HTTP\Request('OPTIONS', 'dav/action', $requestHeaders);
		$request->setAbsoluteUrl('http://cloud.example.com/dav/action');

		$response = new \Sabre\HTTP\Response();

		$server = $this->createMock(Server::class);
		/** @var MockObject */
		$server->sapi = $this->getMockBuilder(\stdClass::class)->addMethods(['sendResponse'])->getMock();
		$server->httpRequest = $request;
		$server->httpResponse = $response;
		$server->sapi
			->expects($shouldSendResponse ? $this->once() : $this->never())
			->method('sendResponse')
			->with($response);

		$this->config->method('getSystemValue')->willReturnCallback(fn($name, $default) => $default);

		$plugin = new CorsPlugin($this->userSession, $this->config);
		$plugin->initialize($server);
		
		$this->assertEquals($expectedReturn, $plugin->setOptionsRequestHeaders($request, $response));
		$this->assertEquals($responseStatus, $response->getStatus());
		$this->assertTrue($response->hasHeader('Access-Control-Allow-Origin') === $shouldHaveCORS);
	}

	public function dataSetCORSHeaders() {
		return [
			'no header on missing origin' => [
				// method
				'PROPFIND',
				// request headers
				[],
				// allow users domain list
				false,
				// already executed
				false,
				// user logged in
				false,
				// extra headers
				['PROPFIND'],
				// added cors methods
				null,
				// the resulting cors domain
				null,
			],
			'allowed origin will set header' => [
				// method
				'PROPFIND',
				// request headers
				['Origin' => 'http://good.example.com'],
				// allow users domain list
				false,
				// already executed
				false,
				// user logged in
				false,
				// allowed methods
				['PROPFIND', 'SEARCH'],
				// added cors methods
				['PROPFIND', 'SEARCH'],
				// the resulting cors domain
				'http://good.example.com',
			],
			'no header if already executed' => [
				// method
				'PROPFIND',
				// request headers
				['Origin' => 'http://good.example.com'],
				// allow users domain list
				false,
				// already executed
				true,
				// user logged in
				false,
				// allowed methods
				['PROPFIND'],
				// added cors methods
				null,
				// the resulting cors domain
				null,
			],
			'set header on user domain' => [
				// method
				'PROPFIND',
				// request headers
				['Origin' => 'http://users.tld'],
				// allow users domain list
				true,
				// already executed
				false,
				// user logged in
				true,
				// allowed methods
				['PROPFIND'],
				// added cors methods
				['PROPFIND'],
				// the resulting cors domain
				'http://users.tld',
			],
			'no header is set when user also do not match' => [
				// method
				'PROPFIND',
				// request headers
				['Origin' => 'http://some-other.tld'],
				// allow users domain list
				true,
				// already executed
				false,
				// user logged in
				true,
				// allowed methods
				['PROPFIND'],
				// added cors methods
				null,
				// the resulting cors domain
				null,
			],
		];
	}

	/**
	 * @dataProvider dataSetCORSHeaders
	 */
	public function testSetCORSHeaders(string $requestMethod, array $requestHeaders, bool $allowUserConfig, bool $alreadyExecuted, bool $hasUser, array $allowedHeaders, ?array $corsMethods, string|null $corsDomain) {
		$request = new \Sabre\HTTP\Request($requestMethod, 'dav/action', $requestHeaders);
		$request->setAbsoluteUrl('http://cloud.example.com/dav/action');

		$response = new \Sabre\HTTP\Response();

		if ($hasUser === true) {
			/** @var MockObject */
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('someuser');
			$this->userSession->method('getUser')->willReturn($user);
		}

		$this->config->method('getSystemValue')->willReturnCallback(fn($name, $default) => match(true) {
			$name === 'cors.allowed-domains' => ['http://good.example.com'],
			default => $default,
		});
		$this->config->method('getSystemValueBool')->with('cors.allow-user-domains')->willReturn($allowUserConfig);
		$this->config->method('getUserValue')->with('someuser', 'core', 'cors.allowed-domains')->willReturn('["http://users.tld"]');

		/** @var Server|MockObject */
		$server = $this->createMock(Server::class);
		$server->method('getAllowedMethods')->with('dav/action')->willReturn($allowedHeaders);
		$server->httpRequest = $request;
		$server->httpResponse = $response;

		/** @var CorsPlugin|MockObject */
		$plugin = new CorsPlugin($this->userSession, $this->config);
		$plugin->initialize($server);
		$plugin->setCorsHeaders($request, $response);
		if ($alreadyExecuted) {
			$response = new \Sabre\HTTP\Response();
			$plugin->setCorsHeaders($request, $response);
		}

		$this->assertEquals($corsDomain, $response->getHeader('Access-Control-Allow-Origin'));
		$this->assertEquals($corsMethods === null ? null : join(',', $corsMethods), $response->getHeader('Access-Control-Allow-Methods'));
	}
}
