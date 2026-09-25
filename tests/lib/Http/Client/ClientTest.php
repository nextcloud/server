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
use OCP\Http\Client\LocalServerException;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use OCP\ServerVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use function parse_url;

/**
 * Class ClientTest
 */
class ClientTest extends \Test\TestCase {
	/** @var Client */
	private $client;
	/** @var array */
	private $defaultRequestOptions;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->client = $this->createInstanceWithMocks(Client::class);
	}

	public function testGetProxyUri(): void {
		$this->mocks[IConfig::class]
			->method('getSystemValueString')
			->with('proxy', '')
			->willReturn('');
		$this->assertNull(self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGetProxyUriProxyHostEmptyPassword(): void {
		$this->mocks[IConfig::class]
			->method('getSystemValue')
			->willReturnMap([
				['proxyexclude', [], []],
			]);

		$this->mocks[IConfig::class]
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
		$this->mocks[IConfig::class]
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn([]);
		$this->mocks[IConfig::class]
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
		$this->mocks[IConfig::class]
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn(['bar']);
		$this->mocks[IConfig::class]
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
		$this->mocks[IConfig::class]->expects($this->once())
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
		$this->mocks[IConfig::class]->expects($this->never())
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
		$this->mocks[IRemoteHostValidator::class]
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
		$this->mocks[IRemoteHostValidator::class]
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
		$this->mocks[IRemoteHostValidator::class]
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
		$this->mocks[IRemoteHostValidator::class]
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
		$this->mocks[IRemoteHostValidator::class]
			->method('isValid')
			->with($host)
			->willReturn(false);

		$this->client->delete($uri);
	}

	private function setUpDefaultRequestOptions(): void {
		$this->mocks[IConfig::class]
			->method('getSystemValue')
			->willReturnMap([
				['proxyexclude', [], []],
			]);
		$this->mocks[IConfig::class]
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
				['overwrite.cli.url', '', '']
			]);
		$this->mocks[IConfig::class]
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, true],
				['http_client_add_user_agent_url', false, false]
			]);

		$this->mocks[ICertificateManager::class]
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->mocks[ServerVersion::class]->method('getVersionString')
			->willReturn('123.45.6');

		$acceptEnc = (((curl_version()['features'] ?? 0) & CURL_VERSION_BROTLI) === CURL_VERSION_BROTLI) ? 'br, gzip' : 'gzip';
		$this->defaultRequestOptions = [
			'verify' => '/my/path.crt',
			'proxy' => [
				'http' => 'foo',
				'https' => 'foo'
			],
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => $acceptEnc,
			],
			'timeout' => 30,
			'nextcloud' => [
				'allow_local_address' => true,
			],
			'version' => '2.0',
			'curl' => [
				\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_2TLS,
			],
		];
	}

	public function testGet(): void {
		$this->setUpDefaultRequestOptions();

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
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

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
			->with('get', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->get('http://localhost/', $options)->getStatusCode());
	}

	public function testPost(): void {
		$this->setUpDefaultRequestOptions();

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
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

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
			->with('post', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->post('http://localhost/', $options)->getStatusCode());
	}

	public function testPut(): void {
		$this->setUpDefaultRequestOptions();

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
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

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
			->with('put', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->put('http://localhost/', $options)->getStatusCode());
	}

	public function testDelete(): void {
		$this->setUpDefaultRequestOptions();

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
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

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
			->with('delete', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->delete('http://localhost/', $options)->getStatusCode());
	}

	public function testOptions(): void {
		$this->setUpDefaultRequestOptions();

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
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

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
			->with('options', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->options('http://localhost/', $options)->getStatusCode());
	}

	public function testHead(): void {
		$this->setUpDefaultRequestOptions();

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
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

		$this->mocks[\GuzzleHttp\Client::class]->method('request')
			->with('head', 'http://localhost/', $options)
			->willReturn(new Response(418));
		$this->assertEquals(418, $this->client->head('http://localhost/', $options)->getStatusCode());
	}

	public function testSetDefaultOptionsWithNotInstalled(): void {
		$this->mocks[IConfig::class]
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, false],
				['allow_local_remote_servers', false, false],
				['http_client_add_user_agent_url', false, false],
			]);
		$this->mocks[IConfig::class]
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', ''],
				['overwrite.cli.url', '', ''],
			]);
		$this->mocks[ICertificateManager::class]
			->expects($this->never())
			->method('listCertificates');
		$this->mocks[ICertificateManager::class]
			->expects($this->once())
			->method('getDefaultCertificatesBundlePath')
			->willReturn(\OC::$SERVERROOT . '/resources/config/ca-bundle.crt');

		$this->mocks[ServerVersion::class]->method('getVersionString')
			->willReturn('123.45.6');

		$acceptEnc = (((curl_version()['features'] ?? 0) & CURL_VERSION_BROTLI) === CURL_VERSION_BROTLI) ? 'br, gzip' : 'gzip';

		$this->assertEquals([
			'verify' => \OC::$SERVERROOT . '/resources/config/ca-bundle.crt',
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => $acceptEnc,
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
			'version' => '2.0',
			'curl' => [
				\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_2TLS,
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}

	public function testSetDefaultOptionsWithProxy(): void {
		$this->mocks[IConfig::class]
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, false],
				['http_client_add_user_agent_url', false, false],
			]);
		$this->mocks[IConfig::class]
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn([]);
		$this->mocks[IConfig::class]
			->expects($this->exactly(3))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
				['overwrite.cli.url', '', ''],
			]);
		$this->mocks[ICertificateManager::class]
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->mocks[ServerVersion::class]->method('getVersionString')
			->willReturn('123.45.6');

		$acceptEnc = (((curl_version()['features'] ?? 0) & CURL_VERSION_BROTLI) === CURL_VERSION_BROTLI) ? 'br, gzip' : 'gzip';

		$this->assertEquals([
			'verify' => '/my/path.crt',
			'proxy' => [
				'http' => 'foo',
				'https' => 'foo'
			],
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => $acceptEnc,
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
			'version' => '2.0',
			'curl' => [
				\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_2TLS,
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}

	public function testSetDefaultOptionsWithProxyAndExclude(): void {
		$this->mocks[IConfig::class]
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, false],
				['http_client_add_user_agent_url', false, false],
			]);
		$this->mocks[IConfig::class]
			->expects($this->once())
			->method('getSystemValue')
			->with('proxyexclude', [])
			->willReturn(['bar']);
		$this->mocks[IConfig::class]
			->expects($this->exactly(3))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', 'foo'],
				['proxyuserpwd', '', ''],
				['overwrite.cli.url', '', ''],
			]);
		$this->mocks[ICertificateManager::class]
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->mocks[ServerVersion::class]->method('getVersionString')
			->willReturn('123.45.6');

		$acceptEnc = (((curl_version()['features'] ?? 0) & CURL_VERSION_BROTLI) === CURL_VERSION_BROTLI) ? 'br, gzip' : 'gzip';

		$this->assertEquals([
			'verify' => '/my/path.crt',
			'proxy' => [
				'http' => 'foo',
				'https' => 'foo',
				'no' => ['bar']
			],
			'headers' => [
				'User-Agent' => 'Nextcloud-Server-Crawler/123.45.6',
				'Accept-Encoding' => $acceptEnc,
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
			'version' => '2.0',
			'curl' => [
				\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_2TLS,
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
		$this->mocks[IConfig::class]
			->expects($this->exactly(3))
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, true],
				['allow_local_remote_servers', false, false],
				['http_client_add_user_agent_url', false, true],
			]);
		$this->mocks[IConfig::class]
			->expects($this->exactly(2))
			->method('getSystemValueString')
			->willReturnMap([
				['proxy', '', ''],
				['overwrite.cli.url', '', $url],
			]);
		$this->mocks[ICertificateManager::class]
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with()
			->willReturn('/my/path.crt');

		$this->mocks[ServerVersion::class]->method('getVersionString')
			->willReturn('123.45.6');

		$acceptEnc = (((curl_version()['features'] ?? 0) & CURL_VERSION_BROTLI) === CURL_VERSION_BROTLI) ? 'br, gzip' : 'gzip';
		$this->assertEquals([
			'verify' => '/my/path.crt',
			'headers' => [
				'User-Agent' => $userAgent,
				'Accept-Encoding' => $acceptEnc,
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
			'version' => '2.0',
			'curl' => [
				\CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_2TLS,
			],
		], self::invokePrivate($this->client, 'buildRequestOptions', [[]]));
	}
}
