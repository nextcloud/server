<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OC\Memcache\KeyValueCacheFactory;
use OC\SystemConfig;
use OCP\Diagnostics\IEventLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Replication\SentinelReplication;
use Test\TestCase;

/**
 * Verifies that the `memcache.kvstore` configuration is translated into the
 * expected predis connection parameters and client options.
 *
 * The tests do not connect to a cache server: predis connects lazily and the
 * mapping in {@see KeyValueCacheFactory::buildConnectionConfig()} is pure.
 */
class KeyValueCacheFactoryTest extends TestCase {
	private SystemConfig&MockObject $config;
	private IEventLogger&MockObject $eventLogger;
	private KeyValueCacheFactory $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);
		$this->eventLogger = $this->createMock(IEventLogger::class);
		$this->factory = new KeyValueCacheFactory($this->config, $this->eventLogger);
	}

	public function testSingleServerTcp(): void {
		[$parameters, $options] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example', 'port' => 6380],
		]);

		$this->assertSame([
			'scheme' => 'tcp',
			'host' => 'cache.example',
			'port' => 6380,
		], $parameters);
		$this->assertSame([], $options);
	}

	public function testSingleServerDefaultPort(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
		]);

		$this->assertSame(6379, $parameters['port']);
	}

	public function testSingleServerUnixSocketFromProtocol(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => '/tmp/cache.sock', 'protocol' => 'unix'],
		]);

		$this->assertSame('unix', $parameters['scheme']);
		$this->assertSame('/tmp/cache.sock', $parameters['path']);
		$this->assertArrayNotHasKey('port', $parameters);
		$this->assertArrayNotHasKey('host', $parameters);
	}

	public function testSingleServerUnixSocketInferredFromHost(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => '/tmp/cache.sock'],
		]);

		$this->assertSame('unix', $parameters['scheme']);
		$this->assertSame('/tmp/cache.sock', $parameters['path']);
	}

	public function testSingleServerTls(): void {
		$sslContext = ['cafile' => '/certs/ca.crt'];
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example', 'protocol' => 'tls'],
			'ssl_context' => $sslContext,
		]);

		$this->assertSame('tls', $parameters['scheme']);
		$this->assertSame($sslContext, $parameters['ssl']);
	}

	public function testSslContextIgnoredWithoutTls(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
			'ssl_context' => ['cafile' => '/certs/ca.crt'],
		]);

		$this->assertArrayNotHasKey('ssl', $parameters);
	}

	public function testSingleServerWithDbIndex(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
			'dbindex' => 5,
		]);

		$this->assertSame(5, $parameters['database']);
	}

	public function testAuthWithUserAndPassword(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
			'user' => 'nextcloud',
			'password' => 's3cret',
		]);

		$this->assertSame('nextcloud', $parameters['username']);
		$this->assertSame('s3cret', $parameters['password']);
	}

	public function testAuthWithPasswordOnly(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
			'password' => 's3cret',
		]);

		$this->assertSame('s3cret', $parameters['password']);
		$this->assertArrayNotHasKey('username', $parameters);
	}

	public function testEmptyPasswordIsIgnored(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
			'user' => 'nextcloud',
			'password' => '',
		]);

		$this->assertArrayNotHasKey('password', $parameters);
		$this->assertArrayNotHasKey('username', $parameters);
	}

	public function testTimeoutAndPersistence(): void {
		[$parameters] = $this->factory->buildConnectionConfig([
			'server' => ['host' => 'cache.example'],
			'timeout' => 1.5,
			'read_timeout' => 2.5,
			'persistent' => true,
		]);

		$this->assertSame(1.5, $parameters['timeout']);
		$this->assertSame(2.5, $parameters['read_write_timeout']);
		$this->assertTrue($parameters['persistent']);
	}

	public function testMissingServerThrows(): void {
		$this->expectException(\RuntimeException::class);
		$this->factory->buildConnectionConfig([]);
	}

	public function testCluster(): void {
		[$parameters, $options] = $this->factory->buildConnectionConfig([
			'seeds' => [
				['host' => 'node1', 'port' => 7000],
				['host' => 'node2', 'port' => 7001],
			],
			'password' => 's3cret',
		]);

		$this->assertCount(2, $parameters);
		$this->assertSame('node1', $parameters[0]['host']);
		$this->assertSame(7000, $parameters[0]['port']);
		$this->assertSame('node2', $parameters[1]['host']);
		$this->assertSame('redis', $options['cluster']);
		// Authentication is propagated to the nodes discovered in the cluster
		$this->assertSame('s3cret', $options['parameters']['password']);
	}

	public function testClusterEmptySeedsThrows(): void {
		$this->expectException(\RuntimeException::class);
		$this->factory->buildConnectionConfig(['seeds' => []]);
	}

	public function testSentinel(): void {
		[$parameters, $options] = $this->factory->buildConnectionConfig([
			'sentinel' => [
				'service' => 'mymaster',
				'seeds' => [
					['host' => 'sentinel1', 'port' => 26379],
					['host' => 'sentinel2', 'port' => 26380],
				],
			],
			'password' => 's3cret',
			'dbindex' => 3,
		]);

		$this->assertCount(2, $parameters);
		$this->assertSame('sentinel1', $parameters[0]['host']);
		$this->assertSame(26379, $parameters[0]['port']);
		$this->assertSame('sentinel', $options['replication']);
		$this->assertSame('mymaster', $options['service']);
		// Auth and database are applied to the resolved primary / replica
		$this->assertSame('s3cret', $options['parameters']['password']);
		$this->assertSame(3, $options['parameters']['database']);
	}

	public function testSentinelMissingServiceThrows(): void {
		$this->expectException(\RuntimeException::class);
		$this->factory->buildConnectionConfig([
			'sentinel' => ['seeds' => [['host' => 'sentinel1', 'port' => 26379]]],
		]);
	}

	public function testSentinelMissingSeedsThrows(): void {
		$this->expectException(\RuntimeException::class);
		$this->factory->buildConnectionConfig([
			'sentinel' => ['service' => 'mymaster'],
		]);
	}

	public function testIsAvailableWithoutConfig(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])->willReturn([]);
		$this->assertFalse($this->factory->isAvailable());
	}

	public function testIsAvailableWithConfig(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])
			->willReturn(['server' => ['host' => 'localhost']]);
		$this->assertTrue($this->factory->isAvailable());
	}

	public function testGetInstanceThrowsWhenUnavailable(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])->willReturn([]);
		$this->expectException(\RuntimeException::class);
		$this->factory->getInstance();
	}

	public function testGetInstanceSingleServer(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])
			->willReturn(['server' => ['host' => 'localhost', 'port' => 6379]]);

		$client = $this->factory->getInstance();
		$this->assertInstanceOf(Client::class, $client);
		$this->assertInstanceOf(NodeConnectionInterface::class, $client->getConnection());
	}

	public function testGetInstanceCluster(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])
			->willReturn(['seeds' => [['host' => 'localhost', 'port' => 7000]]]);

		$client = $this->factory->getInstance();
		$this->assertInstanceOf(ClusterInterface::class, $client->getConnection());
	}

	public function testGetInstanceSentinel(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])
			->willReturn([
				'sentinel' => [
					'service' => 'mymaster',
					'seeds' => [['host' => 'localhost', 'port' => 26379]],
				],
			]);

		$client = $this->factory->getInstance();
		$this->assertInstanceOf(SentinelReplication::class, $client->getConnection());
	}

	public function testGetInstanceIsMemoized(): void {
		$this->config->method('getValue')->with('memcache.kvstore', [])
			->willReturn(['server' => ['host' => 'localhost']]);

		$this->assertSame($this->factory->getInstance(), $this->factory->getInstance());
	}
}
