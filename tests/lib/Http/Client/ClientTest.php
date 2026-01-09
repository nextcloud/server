<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Psr7\Response;
use OC\Http\Client\Client;
use OC\Security\CertificateManager;
use OCP\Http\Client\LocalServerException;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use OCP\ServerVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function parse_url;

/**
 * Class ClientTest
 */
class ClientTest extends \Test\TestCase {
	/** @var \GuzzleHttp\Client|MockObject */
	private $guzzleClient;
	/** @var CertificateManager|MockObject */
	private $certificateManager;
	/** @var Client */
	private $client;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IRemoteHostValidator|MockObject */
	private IRemoteHostValidator $remoteHostValidator;
	private LoggerInterface $logger;
	private ServerVersion $serverVersion;
	/** @var array */
	private $defaultRequestOptions;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->guzzleClient = $this->createMock(\GuzzleHttp\Client::class);
		$this->certificateManager = $this->createMock(ICertificateManager::class);
		$this->remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);

		$this->client = new Client(
			$this->config,
			$this->certificateManager,
			$this->guzzleClient,
			$this->remoteHostValidator,
			$this->logger,
			$this->serverVersion,
		);
	}

	public function testGetProxyUri(): void {
		$this->config
			->method('getSystemValueString')
			->with('proxy', '')
			->willReturn('');
		$this->assertNull(self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGetProxyUriProxyHostEmptyPassword(): void {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['proxyexclude', [], []],
			]);

		$this->config
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
			]);

		$this->assertEquals([
			'http' => 'foo',
			'https' => 'foo'
		], self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGetProxyUriProxyHostWithPassword(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn([]);
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', 'username:password'],
			]);
		$this->assertEquals([
			'http' => 'username:password@foo',
			'https' => 'username:password@foo'
		], self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGetProxyUriProxyHostWithPasswordAndExclude(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn(['bar']);
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', 'username:password'],
			]);
		$this->assertEquals([
			'http' => 'username:password@foo',
			'https' => 'username:password@foo',
			'no' => ['bar']
		], self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testPreventLocalAddressThrowOnInvalidUri(): void {
		$this->expectException(LocalServerException::class);
		$this->expectExceptionMessage('Could not detect any host');

		self::invokePrivate($this->client, 'preventLocalAddress', ['!@#$', []]);
	}

	public static function dataPreventLocalAddress(): array {
		return [
			['https://localhost/foo.bar'],
			['https://localHost/foo.bar'],
			['https://random-host/foo.bar'],
			['https://[::1]/bla.blub'],
			['https://[::]/bla.blub'],
			['https://192.168.0.1'],
			['https://172.16.42.1'],
			['https://[fdf8:f53b:82e4::53]/secret.ics'],
			['https://[fe80::200:5aee:feaa:20a2]/secret.ics'],
			['https://[0:0:0:0:0:0:10.0.0.1]/secret.ics'],
			['https://[0:0:0:0:0:ffff:127.0.0.0]/secret.ics'],
			['https://10.0.0.1'],
			['https://another-host.local'],
			['https://service.localhost'],
			['https://normal.host.com'],
			['https://com.one-.nextcloud-one.com'],
		];
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressDisabledByGlobalConfig(string $uri): void {
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('allow_local_remote_servers', false)
			->willReturn(true);

		self::invokePrivate($this->client, 'preventLocalAddress', [$uri, []]);
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressDisabledByOption(string $uri): void {
		$this->config->expects($this->never())
			->method('getSystemValueBool');

		self::invokePrivate($this->client, 'preventLocalAddress', [$uri, [
			'nextcloud' => ['allow_local_address' => true],
		]]);
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressOnGet(string $uri): void {
		$host = parse_url($uri, PHP_URL_HOST);
		$this->expectException(LocalServerException::class);
		$this->remoteHostValidator
			->method('isValid')
			->with($host)
			->willReturn(false);

		$this->client->get($uri);
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressOnHead(string $uri): void {
		$host = parse_url($uri, PHP_URL_HOST);
		$this->expectException(LocalServerException::class);
		$this->remoteHostValidator
			->method('isValid')
			->with($host)
			->willReturn(false);

		$this->client->head($uri);
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressOnPost(string $uri): void {
		$host = parse_url($uri, PHP_URL_HOST);
		$this->expectException(LocalServerException::class);
		$this->remoteHostValidator
			->method('isValid')
			->with($host)
			->willReturn(false);

		$this->client->post($uri);
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressOnPut(string $uri): void {
		$host = parse_url($uri, PHP_URL_HOST);
		$this->expectException(LocalServerException::class);
		$this->remoteHostValidator
			->method('isValid')
			->with($host)
			->willReturn(false);

		$this->client->put($uri);
	}

	/**
	 * @param string $uri
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPreventLocalAddress')]
	public function testPreventLocalAddressOnDelete(string $uri): void {
		$host = parse_url($uri, PHP_URL_HOST);
		$this->expectException(LocalServerException::class);
		$this->remoteHostValidator
			->method('isValid')
			->with($host)
			->willReturn(false);

		$this->client->delete($uri);
	}

	private function setUpDefaultRequestOptions(): void {
		$this->config
			->method('getSystemValue')
			->willReturnMap([
				['proxyexclude', [], []],
			]);
		$this->config
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
			]);
		$this->config
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, true],
			]);

		$this->certificateManager
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->serverVersion->method('getVersionString')
			->willReturn('123.45.6');

		$this->defaultRequestOptions = [
			'verify' => '/my/path.crt',
			'proxy' => [
				'http' => 'foo',
				'https' => 'foo'
			],
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => 'gzip',
			],
			'timeout' => 30,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		];
	}

	public function testGet(): void {
		$this->setUpDefaultRequestOptions();

		$this->guzzleClient->method('request')
			->with('get', 'http://localhost/', $this->defaultRequestOptions)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->get('http://localhost/', [])->getStatusCode());
	}

	public function testGetWithOptions(): void {
		$this->setUpDefaultRequestOptions();

		$options = array_merge($this->defaultRequestOptions, [
			'verify' => false,
			'proxy' => [
				'http' => 'bar',
				'https' => 'bar'
			],
		]);

		$this->guzzleClient->method('request')
			->with('get', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->get('http://localhost/', $options)->getStatusCode());
	}

	public function testPost(): void {
		$this->setUpDefaultRequestOptions();

		$this->guzzleClient->method('request')
			->with('post', 'http://localhost/', $this->defaultRequestOptions)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->post('http://localhost/', [])->getStatusCode());
	}

	public function testPostWithOptions(): void {
		$this->setUpDefaultRequestOptions();

		$options = array_merge($this->defaultRequestOptions, [
			'verify' => false,
			'proxy' => [
				'http' => 'bar',
				'https' => 'bar'
			],
		]);

		$this->guzzleClient->method('request')
			->with('post', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->post('http://localhost/', $options)->getStatusCode());
	}

	public function testPut(): void {
		$this->setUpDefaultRequestOptions();

		$this->guzzleClient->method('request')
			->with('put', 'http://localhost/', $this->defaultRequestOptions)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->put('http://localhost/', [])->getStatusCode());
	}

	public function testPutWithOptions(): void {
		$this->setUpDefaultRequestOptions();

		$options = array_merge($this->defaultRequestOptions, [
			'verify' => false,
			'proxy' => [
				'http' => 'bar',
				'https' => 'bar'
			],
		]);

		$this->guzzleClient->method('request')
			->with('put', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->put('http://localhost/', $options)->getStatusCode());
	}

	public function testDelete(): void {
		$this->setUpDefaultRequestOptions();

		$this->guzzleClient->method('request')
			->with('delete', 'http://localhost/', $this->defaultRequestOptions)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->delete('http://localhost/', [])->getStatusCode());
	}

	public function testDeleteWithOptions(): void {
		$this->setUpDefaultRequestOptions();

		$options = array_merge($this->defaultRequestOptions, [
			'verify' => false,
			'proxy' => [
				'http' => 'bar',
				'https' => 'bar'
			],
		]);

		$this->guzzleClient->method('request')
			->with('delete', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->delete('http://localhost/', $options)->getStatusCode());
	}

	public function testOptions(): void {
		$this->setUpDefaultRequestOptions();

		$this->guzzleClient->method('request')
			->with('options', 'http://localhost/', $this->defaultRequestOptions)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->options('http://localhost/', [])->getStatusCode());
	}

	public function testOptionsWithOptions(): void {
		$this->setUpDefaultRequestOptions();

		$options = array_merge($this->defaultRequestOptions, [
			'verify' => false,
			'proxy' => [
				'http' => 'bar',
				'https' => 'bar'
			],
		]);

		$this->guzzleClient->method('request')
			->with('options', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->options('http://localhost/', $options)->getStatusCode());
	}

	public function testHead(): void {
		$this->setUpDefaultRequestOptions();

		$this->guzzleClient->method('request')
			->with('head', 'http://localhost/', $this->defaultRequestOptions)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->head('http://localhost/', [])->getStatusCode());
	}

	public function testHeadWithOptions(): void {
		$this->setUpDefaultRequestOptions();

		$options = array_merge($this->defaultRequestOptions, [
			'verify' => false,
			'proxy' => [
				'http' => 'bar',
				'https' => 'bar'
			],
		]);

		$this->guzzleClient->method('request')
			->with('head', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->head('http://localhost/', $options)->getStatusCode());
	}

	public function testSetDefaultOptionsWithNotInstalled(): void {
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, false],
				['allow_local_remote_servers', false, false],
				['add_url_in_user_agent', false, false],
			]);
		$this->config
			->expects($this->once())
			->method('getSystemValueString')
			->with('proxy', '')
			->willReturn('');
		$this->certificateManager
			->expects($this->never())
			->method('listCertificates');
		$this->certificateManager
			->expects($this->once())
			->method('getDefaultCertificatesBundlePath')
			->willReturn(\OC::$SERVERROOT . '/resources/config/ca-bundle.crt');

		$this->serverVersion->method('getVersionString')
			->willReturn('123.45.6');

		$this->assertEquals([
			'verify' => \OC::$SERVERROOT . '/resources/config/ca-bundle.crt',
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => 'gzip',
			],
			'timeout' => 30,
			'nextcloud' => [
				'allow_local_address' => false,
			],
			'allow_redirects' => [
				'on_redirect' => function (
					\Psr\Http\Message\RequestInterface $request,
					\Psr\Http\Message\ResponseInterface $response,
					\Psr\Http\Message\UriInterface $uri,
				): void {
				},
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}

	public function testSetDefaultOptionsWithProxy(): void {
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, false],
				['add_url_in_user_agent', false, false],
			]);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn([]);
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
			]);
		$this->certificateManager
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->serverVersion->method('getVersionString')
			->willReturn('123.45.6');

		$this->assertEquals([
			'verify' => '/my/path.crt',
			'proxy' => [
				'http' => 'foo',
				'https' => 'foo'
			],
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => 'gzip',
			],
			'timeout' => 30,
			'nextcloud' => [
				'allow_local_address' => false,
			],
			'allow_redirects' => [
				'on_redirect' => function (
					\Psr\Http\Message\RequestInterface $request,
					\Psr\Http\Message\ResponseInterface $response,
					\Psr\Http\Message\UriInterface $uri,
				): void {
				},
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}

	public function testSetDefaultOptionsWithProxyAndExclude(): void {
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, false],
				['add_url_in_user_agent', false, false],
			]);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn(['bar']);
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
			]);
		$this->certificateManager
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->serverVersion->method('getVersionString')
			->willReturn('123.45.6');

		$this->assertEquals([
			'verify' => '/my/path.crt',
			'proxy' => [
				'http' => 'foo',
				'https' => 'foo',
				'no' => ['bar']
			],
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => 'gzip',
			],
			'timeout' => 30,
			'nextcloud' => [
				'allow_local_address' => false,
			],
			'allow_redirects' => [
				'on_redirect' => function (
					\Psr\Http\Message\RequestInterface $request,
					\Psr\Http\Message\ResponseInterface $response,
					\Psr\Http\Message\UriInterface $uri,
				): void {
				},
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}

	public static function dataForTestSetServerUrlInUserAgent(): array {
		return [
			['https://example.com/', 'Nextcloud-Server-Crawler/123.45.6; +https://example.com'],
			['', 'Nextcloud-Server-Crawler/123.45.6'],
		];
	}

	#[DataProvider('dataForTestSetServerUrlInUserAgent')]
	public function testSetServerUrlInUserAgent(string $url, string $userAgent): void {
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, false],
				['add_url_in_user_agent', false, true],
			]);
		$this->config
			->expects($this->exactly(3))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', ''],
				['proxyuserpwd', '', ''],
				['overwrite.cli.url', null, $url],
			]);
		$this->certificateManager
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->serverVersion->method('getVersionString')
			->willReturn('123.45.6');

		$this->assertEquals([
			'verify' => '/my/path.crt',
			'headers' => [
				'User-Agent' => $userAgent,
				'Accept-Encoding' => 'gzip',
			],
			'timeout' => 30,
			'nextcloud' => [
				'allow_local_address' => false,
			],
			'allow_redirects' => [
				'on_redirect' => function (
					\Psr\Http\Message\RequestInterface $request,
					\Psr\Http\Message\ResponseInterface $response,
					\Psr\Http\Message\UriInterface $uri,
				): void {
				},
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}
}
