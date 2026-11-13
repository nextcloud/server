<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Route;

use OC\Route\CachingRouter;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CachingRouterTest extends TestCase {
	private ICache&MockObject $cache;
	private ICacheFactory&MockObject $cacheFactory;

	/** @var array<string, mixed> */
	private array $cacheEntries = [];

	/** @var list<string> */
	private array $lookedUpKeys = [];

	/** @var list<string> */
	private array $storedKeys = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock(ICache::class);
		$this->cache->method('get')
			->willReturnCallback(function (string $key) {
				$this->lookedUpKeys[] = $key;
				return $this->cacheEntries[$key] ?? null;
			});
		$this->cache->method('set')
			->willReturnCallback(function (string $key, $value, int $ttl = 0): bool {
				$this->storedKeys[] = $key;
				$this->cacheEntries[$key] = $value;
				return true;
			});

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheFactory->method('createLocal')
			->with('route')
			->willReturn($this->cache);
	}

	public function testEquivalentRouteNamesAndParameterOrdersReuseCacheEntry(): void {
		$router = $this->createRouter();

		$firstUrl = $router->generate('HEARTBEAT', ['b' => 'two', 'a' => 'one']);
		$secondUrl = $router->generate('heartbeat', ['a' => 'one', 'b' => 'two']);

		self::assertSame($firstUrl, $secondUrl);
		self::assertCount(2, $this->lookedUpKeys);
		self::assertSame($this->lookedUpKeys[0], $this->lookedUpKeys[1]);
		self::assertCount(1, $this->storedKeys);
	}

	public function testSchemeAndAbsoluteFlagProduceDistinctCacheEntries(): void {
		$this->createRouter('http')->generate('heartbeat');
		$this->createRouter('https')->generate('heartbeat');
		$this->createRouter('http')->generate('heartbeat', [], true);
		$this->createRouter('https')->generate('heartbeat', [], true);

		self::assertCount(4, $this->lookedUpKeys);
		self::assertCount(4, array_unique($this->lookedUpKeys));
	}

	public function testJsonEncodingFailureBypassesCache(): void {
		$router = $this->createRouter();

		$url = $router->generate('heartbeat', ['invalid' => "\xB1"]);

		self::assertNotSame('', $url);
		self::assertSame([], $this->lookedUpKeys);
		self::assertSame([], $this->storedKeys);
	}

	private function createRouter(string $scheme = 'http'): CachingRouter {
		$request = $this->createMock(IRequest::class);
		$request->method('getServerHost')->willReturn('localhost');
		$request->method('getServerProtocol')->willReturn($scheme);

		return new CachingRouter(
			$this->cacheFactory,
			$this->createMock(LoggerInterface::class),
			$request,
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
			$this->createMock(IAppManager::class),
		);
	}
}
