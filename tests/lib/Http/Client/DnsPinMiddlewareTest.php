<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Http\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OC\Http\Client\DnsPinMiddleware;
use OC\Http\Client\NegativeDnsCache;
use OC\Memcache\NullCache;
use OC\Net\IpAddressClassifier;
use OCP\Http\Client\LocalServerException;
use OCP\ICacheFactory;
use Psr\Http\Message\RequestInterface;
use Test\TestCase;

class DnsPinMiddlewareTest extends TestCase {
	private DnsPinMiddleware $dnsPinMiddleware;

	protected function setUp(): void {
		parent::setUp();

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory
			->method('createLocal')
			->willReturn(new NullCache());

		$ipAddressClassifier = new IpAddressClassifier();
		$negativeDnsCache = new NegativeDnsCache($cacheFactory);

		$this->dnsPinMiddleware = $this->getMockBuilder(DnsPinMiddleware::class)
			->setConstructorArgs([$negativeDnsCache, $ipAddressClassifier])
			->onlyMethods(['dnsGetRecord'])
			->getMock();
	}

	public function testPopulateDnsCacheIPv4(): void {
		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options) {
				self::arrayHasKey('curl', $options);
				self::arrayHasKey(CURLOPT_RESOLVE, $options['curl']);
				self::assertEquals([
					'www.example.com:80:1.1.1.1',
					'www.example.com:443:1.1.1.1'
				], $options['curl'][CURLOPT_RESOLVE]);
				return new Response(200);
			},
		]);

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) {
				// example.com SOA
				if ($hostname === 'example.com') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.com',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.com A, AAAA, CNAME
				if ($hostname === 'www.example.com') {
					return match ($type) {
						DNS_A => [],
						DNS_AAAA => [],
						DNS_CNAME => [
							[
								'host' => 'www.example.com',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'target' => 'www.example.net'
							]
						],
					};
				}

				// example.net SOA
				if ($hostname === 'example.net') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.net',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.net A, AAAA, CNAME
				if ($hostname === 'www.example.net') {
					return match ($type) {
						DNS_A => [
							[
								'host' => 'www.example.net',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'ip' => '1.1.1.1'
							]
						],
						DNS_AAAA => [],
						DNS_CNAME => [],
					};
				}

				return false;
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://www.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);
	}

	public function testPopulateDnsCacheIPv6(): void {
		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options) {
				self::arrayHasKey('curl', $options);
				self::arrayHasKey(CURLOPT_RESOLVE, $options['curl']);
				self::assertEquals([
					'www.example.com:80:1.1.1.1,1.0.0.1,2606:4700:4700::1111,2606:4700:4700::1001',
					'www.example.com:443:1.1.1.1,1.0.0.1,2606:4700:4700::1111,2606:4700:4700::1001'
				], $options['curl'][CURLOPT_RESOLVE]);
				return new Response(200);
			},
		]);

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) {
				// example.com SOA
				if ($hostname === 'example.com') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.com',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.com A, AAAA, CNAME
				if ($hostname === 'www.example.com') {
					return match ($type) {
						DNS_A => [],
						DNS_AAAA => [],
						DNS_CNAME => [
							[
								'host' => 'www.example.com',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'target' => 'www.example.net'
							]
						],
					};
				}

				// example.net SOA
				if ($hostname === 'example.net') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.net',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.net A, AAAA, CNAME
				if ($hostname === 'www.example.net') {
					return match ($type) {
						DNS_A => [
							[
								'host' => 'www.example.net',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'ip' => '1.1.1.1'
							],
							[
								'host' => 'www.example.net',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'ip' => '1.0.0.1'
							],
						],
						DNS_AAAA => [
							[
								'host' => 'www.example.net',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'AAAA',
								'ip' => '2606:4700:4700::1111'
							],
							[
								'host' => 'www.example.net',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'AAAA',
								'ip' => '2606:4700:4700::1001'
							],
						],
						DNS_CNAME => [],
					};
				}

				return false;
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://www.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);
	}

	public function testAllowLocalAddress(): void {
		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options) {
				self::assertArrayNotHasKey('curl', $options);
				return new Response(200);
			},
		]);

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://www.example.com'),
			['nextcloud' => ['allow_local_address' => true]]
		);
	}

	public function testRejectIPv4(): void {
		$this->expectException(LocalServerException::class);
		$this->expectExceptionMessage('violates local access rules');

		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options): void {
				// The handler should not be called
			},
		]);

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) {
				return match ($type) {
					DNS_SOA => [
						[
							'host' => 'example.com',
							'class' => 'IN',
							'ttl' => 7079,
							'type' => 'SOA',
							'minimum-ttl' => 3600,
						]
					],
					DNS_A => [
						[
							'host' => 'example.com',
							'class' => 'IN',
							'ttl' => 1800,
							'type' => 'A',
							'ip' => '192.168.0.1'
						]
					],
					DNS_AAAA => [],
					DNS_CNAME => [],
				};
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://www.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);
	}

	public function testRejectIPv6(): void {
		$this->expectException(LocalServerException::class);
		$this->expectExceptionMessage('violates local access rules');

		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options): void {
				// The handler should not be called
			},
		]);

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) {
				return match ($type) {
					DNS_SOA => [
						[
							'host' => 'example.com',
							'class' => 'IN',
							'ttl' => 7079,
							'type' => 'SOA',
							'minimum-ttl' => 3600,
						]
					],
					DNS_A => [],
					DNS_AAAA => [
						[
							'host' => 'ipv6.example.com',
							'class' => 'IN',
							'ttl' => 1800,
							'type' => 'AAAA',
							'ipv6' => 'fd12:3456:789a:1::1'
						]
					],
					DNS_CNAME => [],
				};
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://ipv6.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);
	}

	public function testRejectCanonicalName(): void {
		$this->expectException(LocalServerException::class);
		$this->expectExceptionMessage('violates local access rules');

		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options): void {
				// The handler should not be called
			},
		]);

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) {
				// example.com SOA
				if ($hostname === 'example.com') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.com',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.com A, AAAA, CNAME
				if ($hostname === 'www.example.com') {
					return match ($type) {
						DNS_A => [],
						DNS_AAAA => [],
						DNS_CNAME => [
							[
								'host' => 'www.example.com',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'target' => 'www.example.net'
							]
						],
					};
				}

				// example.net SOA
				if ($hostname === 'example.net') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.net',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.net A, AAAA, CNAME
				if ($hostname === 'www.example.net') {
					return match ($type) {
						DNS_A => [
							[
								'host' => 'www.example.net',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'ip' => '192.168.0.2'
							]
						],
						DNS_AAAA => [],
						DNS_CNAME => [],
					};
				}

				return false;
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://www.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);
	}

	public function testRejectFaultyResponse(): void {
		$this->expectException(LocalServerException::class);
		$this->expectExceptionMessage('No DNS record found for www.example.com');

		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options): void {
				// The handler should not be called
			},
		]);

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) {
				return false;
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://www.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);
	}

	public function testIgnoreSubdomainForSoaQuery(): void {
		$mockHandler = new MockHandler([
			static function (RequestInterface $request, array $options): void {
				// The handler should not be called
			},
		]);

		$dnsQueries = [];

		$this->dnsPinMiddleware
			->method('dnsGetRecord')
			->willReturnCallback(function (string $hostname, int $type) use (&$dnsQueries) {
				// log query
				$dnsQueries[] = $hostname . $type;

				// example.com SOA
				if ($hostname === 'example.com') {
					return match ($type) {
						DNS_SOA => [
							[
								'host' => 'example.com',
								'class' => 'IN',
								'ttl' => 7079,
								'type' => 'SOA',
								'minimum-ttl' => 3600,
							]
						],
					};
				}

				// example.net A, AAAA, CNAME
				if ($hostname === 'subsubdomain.subdomain.example.com') {
					return match ($type) {
						DNS_A => [
							[
								'host' => 'subsubdomain.subdomain.example.com',
								'class' => 'IN',
								'ttl' => 1800,
								'type' => 'A',
								'ip' => '1.1.1.1'
							]
						],
						DNS_AAAA => [],
						DNS_CNAME => [],
					};
				}

				return false;
			});

		$stack = new HandlerStack($mockHandler);
		$stack->push($this->dnsPinMiddleware->addDnsPinning());
		$handler = $stack->resolve();

		$handler(
			new Request('GET', 'https://subsubdomain.subdomain.example.com'),
			['nextcloud' => ['allow_local_address' => false]]
		);

		$this->assertCount(3, $dnsQueries);
		$this->assertContains('example.com' . DNS_SOA, $dnsQueries);
		$this->assertContains('subsubdomain.subdomain.example.com' . DNS_A, $dnsQueries);
		$this->assertContains('subsubdomain.subdomain.example.com' . DNS_AAAA, $dnsQueries);
		// CNAME should not be queried if A or AAAA succeeded already
		$this->assertNotContains('subsubdomain.subdomain.example.com' . DNS_CNAME, $dnsQueries);
	}
}
