<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
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
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Test\TestCase;

class CorsPluginTest extends TestCase {
	/**
	 * @var Server
	 */
	private $server;

	/**
	 * @var CorsPlugin
	 */
	private $plugin;

	/**
	 * @var IUserSession | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $userSession;

	/**
	 * @var IConfig | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $config;

	public function setUp(): void {
		parent::setUp();
		$this->server = new Server();

		$this->server->sapi = $this->getMockBuilder(\stdClass::class)
			->setMethods(['sendResponse'])
			->getMock();

		$this->server->httpRequest->setMethod('OPTIONS');

		$this->userSession = $this->createMock(IUserSession::class);

		$this->config = $this->createMock(IConfig::class);
		$this->overwriteService('AllConfig', $this->config);

		$this->plugin = new CorsPlugin($this->userSession);

		/** @var ServerPlugin | \PHPUnit\Framework\MockObject\MockObject $extraMethodPlugin */
		$extraMethodPlugin = $this->createMock(ServerPlugin::class);
		$extraMethodPlugin->method('getHTTPMethods')
			->with('owncloud/remote.php/dav/files/user1/target/path')
			->willReturn(['EXTRA']);
		$extraMethodPlugin->method('getFeatures')->willReturn([]);

		$this->server->addPlugin($extraMethodPlugin);
	}

	public function tearDown(): void {
		$this->restoreService('AllConfig');
	}

	public function optionsCases() {
		$allowedDomains = '["https://requesterdomain.tld", "http://anotherdomain.tld"]';

		$allowedHeaders = [
			'OC-Checksum', 'OC-Total-Length', 'OCS-APIREQUEST', 'X-OC-Mtime',
			'OC-RequestAppPassword', 'Accept',
			'Authorization', 'Brief', 'Content-Length', 'Content-Range',
			'Content-Type', 'Date', 'Depth', 'Destination', 'Host', 'If', 'If-Match',
			'If-Modified-Since', 'If-None-Match', 'If-Range', 'If-Unmodified-Since',
			'Location', 'Lock-Token', 'Overwrite', 'Prefer', 'Range', 'Schedule-Reply',
			'Timeout', 'User-Agent', 'X-Expected-Entity-Length', 'Accept-Language',
			'Access-Control-Request-Method', 'Access-Control-Allow-Origin', 'Cache-Control', 'ETag',
			'OC-Autorename', 'OC-CalDav-Import', 'OC-Chunked', 'OC-Etag', 'OC-FileId',
			'OC-LazyOps', 'OC-Total-File-Length', 'Origin', 'X-Request-ID', 'X-Requested-With'
		];
		$allowedMethods = [
			'GET',
			'OPTIONS',
			'POST',
			'PUT',
			'DELETE',
			'MKCOL',
			'PROPFIND',
			'PATCH',
			'PROPPATCH',
			'REPORT',
			'HEAD',
			'COPY',
			'MOVE',
			'EXTRA',
		];
		$allowedMethodsUnAuthenticated = [
			'GET',
			'OPTIONS',
			'POST',
			'PUT',
			'DELETE',
			'MKCOL',
			'PROPFIND',
			'PATCH',
			'PROPPATCH',
			'REPORT',
			'HEAD',
			'COPY',
			'MOVE',
		];

		return [
			'OPTIONS headers' =>
			[
				$allowedDomains,
				false,
				[
					'Origin' => 'https://requesterdomain.tld',
				],
				200,
				[
					'Access-Control-Allow-Headers' => \implode(',', $allowedHeaders),
					'Access-Control-Allow-Origin' => '*',
					'Access-Control-Allow-Methods' => \implode(',', $allowedMethodsUnAuthenticated),
				],
				false
			],
			'OPTIONS headers with user' =>
			[
				$allowedDomains,
				true,
				[
					'Origin' => 'https://requesterdomain.tld',
					'Authorization' => 'abc',
				],
				200,
				[
					'Access-Control-Allow-Headers' => \implode(',', $allowedHeaders),
					'Access-Control-Allow-Origin' => 'https://requesterdomain.tld',
					'Access-Control-Allow-Methods' => \implode(',', $allowedMethods),
				],
				true
			],
			'OPTIONS headers no user' =>
			[
				$allowedDomains,
				false,
				[
					'Origin' => 'https://requesterdomain.tld',
					'Authorization' => 'abc',
				],
				200,
				[
					'Access-Control-Allow-Headers' => null,
					'Access-Control-Allow-Origin' => null,
					'Access-Control-Allow-Methods' => null,
				],
				true
			],
			'OPTIONS headers domain not allowed' =>
			[
				'[]',
				true,
				[
					'Origin' => 'https://requesterdomain.tld',
					'Authorization' => 'abc',
				],
				200,
				[
					'Access-Control-Allow-Headers' => null,
					'Access-Control-Allow-Origin' => null,
					'Access-Control-Allow-Methods' => null,
				],
				true
			],
			'OPTIONS headers not allowed but no cross-domain' =>
			[
				'[]',
				true,
				[
					'Origin' => 'https://requesterdomain.tld',
					'Authorization' => 'abc',
				],
				200,
				[
					'Access-Control-Allow-Headers' => null,
					'Access-Control-Allow-Origin' => null,
					'Access-Control-Allow-Methods' => null,
				],
				true
			],
			'OPTIONS headers allowed but no cross-domain' =>
			[
				'["currentdomain.tld:8080"]',
				true,
				[
					'Origin' => 'https://currentdomain.tld:8080',
					'Authorization' => 'abc',
				],
				200,
				[
					'Access-Control-Allow-Headers' => null,
					'Access-Control-Allow-Origin' => null,
					'Access-Control-Allow-Methods' => null,
				],
				true
			],
			'OPTIONS headers allowed, cross-domain through different port' =>
			[
				'["https://currentdomain.tld:8443"]',
				true,
				[
					'Origin' => 'https://currentdomain.tld:8443',
					'Authorization' => 'abc',
				],
				200,
				[
					'Access-Control-Allow-Headers' => \implode(',', $allowedHeaders),
					'Access-Control-Allow-Origin' => 'https://currentdomain.tld:8443',
					'Access-Control-Allow-Methods' => \implode(',', $allowedMethods),
				],
				true
			],
			'no Origin header' =>
			[
				$allowedDomains,
				true,
				[
				],
				200,
				[
					'Access-Control-Allow-Headers' => null,
					'Access-Control-Allow-Origin' => null,
					'Access-Control-Allow-Methods' => null,
				],
				true
			],
		];
	}

	/**
	 * @dataProvider optionsCases
	 * @param $allowedDomains
	 * @param $hasUser
	 * @param $requestHeaders
	 * @param $expectedStatus
	 * @param array $expectedHeaders
	 * @param bool $expectDavHeaders
	 */
	public function testOptionsHeaders($allowedDomains, $hasUser, $requestHeaders, $expectedStatus, array $expectedHeaders, $expectDavHeaders = false) {
		$this->server->sapi->expects($this->once())->method('sendResponse')->with($this->server->httpResponse);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('someuser');

		if ($hasUser) {
			$this->userSession->method('getUser')->willReturn($user);
		} else {
			$this->userSession->method('getUser')->willReturn(null);
		}

		$this->config->method('getSystemValue')->willReturn([]);
		$this->config->method('getUserValue')
			->with('someuser', 'core', 'domains')
			->willReturn($allowedDomains);

		$this->server->httpRequest->setHeaders($requestHeaders);
		$this->server->httpRequest->setAbsoluteUrl('https://currentdomain.tld:8080/owncloud/remote.php/dav/files/user1/target/path');
		$this->server->httpRequest->setUrl('/owncloud/remote.php/dav/files/user1/target/path');

		$this->server->addPlugin($this->plugin);
		$this->server->start();

		$this->assertEquals($expectedStatus, $this->server->httpResponse->getStatus());

		foreach ($expectedHeaders as $headerKey => $headerValue) {
			if ($headerValue !== null) {
				$this->assertTrue($this->server->httpResponse->hasHeader($headerKey), "Response header \"$headerKey\" exists");
			} else {
				$this->assertFalse($this->server->httpResponse->hasHeader($headerKey), "Response header \"$headerKey\" does not exist");
			}
			$this->assertEquals($headerValue, $this->server->httpResponse->getHeader($headerKey));
		}

		// if it has DAV headers, it means we did not bypass further processing
		$this->assertEquals($expectDavHeaders, $this->server->httpResponse->hasHeader('DAV'));
	}

	/**
	 * @dataProvider providesOriginUrls
	 * @param $expectedValue
	 * @param $url
	 */
	public function testExtensionRequests($expectedValue, $url) {
		$plugin = new CorsPlugin($this->createMock(IUserSession::class));
		self::assertEquals($expectedValue, $plugin->ignoreOriginHeader($url));
	}

	public function providesOriginUrls() {
		return [
			'Firefox extension' => [true, 'moz-extension://mgmnhfbjphngabcpbpmapnnaabhnchmi/'],
			'Chrome extension' => [true, 'chrome-extension://mgmnhfbjphngabcpbpmapnnaabhnchmi/'],
			'Empty Origin' => [true, ''],
			'Null string Origin' => [true, 'null'],
			'Null Origin' => [true, null],
			'plain http' => [false, 'http://example.net/'],
		];
	}

	public function testAuthenticatedAdditionalAllowedHeaders() {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('someuser');

		$this->userSession->method('getUser')->willReturn($user);
		$this->server->httpRequest->setHeader('Origin', 'https://requesterdomain.tld');
		$this->server->httpRequest->setUrl('/owncloud/remote.php/dav/files/user1/target/path');

		$this->config->method('getSystemValue')->withConsecutive(
			['cors.allowed-domains', []],
			['cors.allowed-headers', []]
		)
			->willReturnMap([
				['cors.allowed-domains', [], []],
				['cors.allowed-headers', [], ['X-Additional-Configured-Header', 'authorization']]
			]);
		$this->config->method('getUserValue')->willReturn('["https://requesterdomain.tld"]');

		$this->server->addPlugin($this->plugin);

		$this->plugin->setCorsHeaders($this->server->httpRequest, $this->server->httpResponse);
		self::assertEquals(
			'X-Additional-Configured-Header,authorization,OC-Checksum,OC-Total-Length,OCS-APIREQUEST,X-OC-Mtime,OC-RequestAppPassword,Accept,Authorization,Brief,Content-Length,Content-Range,Content-Type,Date,Depth,Destination,Host,If,If-Match,If-Modified-Since,If-None-Match,If-Range,If-Unmodified-Since,Location,Lock-Token,Overwrite,Prefer,Range,Schedule-Reply,Timeout,User-Agent,X-Expected-Entity-Length,Accept-Language,Access-Control-Request-Method,Access-Control-Allow-Origin,Cache-Control,ETag,OC-Autorename,OC-CalDav-Import,OC-Chunked,OC-Etag,OC-FileId,OC-LazyOps,OC-Total-File-Length,Origin,X-Request-ID,X-Requested-With',
			$this->server->httpResponse->getHeader('Access-Control-Allow-Headers')
		);
	}

	public function testUnauthenticatedAdditionalAllowedHeaders() {
		$this->userSession->method('getUser')->willReturn(null);
		$this->server->httpRequest->setHeader('Origin', 'https://requesterdomain.tld');

		$this->config->method('getSystemValue')->withConsecutive(
			['cors.allowed-domains', []],
			['cors.allowed-headers', []]
		)
			->willReturnMap([
				['cors.allowed-domains', [], ['https://requesterdomain.tld']],
				['cors.allowed-headers', [], ['X-Additional-Configured-Header', 'authorization']]
			]);

		$this->server->addPlugin($this->plugin);

		$this->plugin->setCorsHeaders($this->server->httpRequest, $this->server->httpResponse);
		self::assertEquals(
			'X-Additional-Configured-Header,authorization,OC-Checksum,OC-Total-Length,OCS-APIREQUEST,X-OC-Mtime,OC-RequestAppPassword,Accept,Authorization,Brief,Content-Length,Content-Range,Content-Type,Date,Depth,Destination,Host,If,If-Match,If-Modified-Since,If-None-Match,If-Range,If-Unmodified-Since,Location,Lock-Token,Overwrite,Prefer,Range,Schedule-Reply,Timeout,User-Agent,X-Expected-Entity-Length,Accept-Language,Access-Control-Request-Method,Access-Control-Allow-Origin,Cache-Control,ETag,OC-Autorename,OC-CalDav-Import,OC-Chunked,OC-Etag,OC-FileId,OC-LazyOps,OC-Total-File-Length,Origin,X-Request-ID,X-Requested-With',
			$this->server->httpResponse->getHeader('Access-Control-Allow-Headers')
		);
	}
}
