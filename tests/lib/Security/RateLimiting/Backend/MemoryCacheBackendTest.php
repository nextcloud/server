<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\RateLimiting\Backend;

use OC\Security\RateLimiting\Backend\MemoryCacheBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use Test\TestCase;

class MemoryCacheBackendTest extends TestCase {
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cacheFactory;
	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;
	/** @var ICache|\PHPUnit\Framework\MockObject\MockObject */
	private $cache;
	/** @var MemoryCacheBackend */
	private $memoryCache;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->cache = $this->createMock(ICache::class);

		$this->cacheFactory
			->expects($this->once())
			->method('createDistributed')
			->with('OC\Security\RateLimiting\Backend\MemoryCacheBackend')
			->willReturn($this->cache);

		$this->config->method('getSystemValueBool')
			->with('ratelimit.protection.enabled')
			->willReturn(true);

		$this->memoryCache = new MemoryCacheBackend(
			$this->config,
			$this->cacheFactory,
			$this->timeFactory
		);
	}

	public function testGetAttemptsWithNoAttemptsBefore(): void {
		$this->cache
			->expects($this->once())
			->method('get')
			->with('eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b')
			->willReturn(null);

		$this->assertSame(0, $this->memoryCache->getAttempts('Method', 'User'));
	}

	public function testGetAttempts(): void {
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(210);
		$this->cache
			->expects($this->once())
			->method('get')
			->with('eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b')
			->willReturn(json_encode([
				'1',
				'2',
				'87',
				'223',
				'223',
				'224',
			]));

		$this->assertSame(3, $this->memoryCache->getAttempts('Method', 'User'));
	}

	public function testRegisterAttemptWithNoAttemptsBefore(): void {
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(123);

		$this->cache
			->expects($this->once())
			->method('get')
			->with('eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b')
			->willReturn(null);
		$this->cache
			->expects($this->once())
			->method('set')
			->with(
				'eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b',
				json_encode(['223'])
			);

		$this->memoryCache->registerAttempt('Method', 'User', 100);
	}

	public function testRegisterAttempt(): void {
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(86);

		$this->cache
			->expects($this->once())
			->method('get')
			->with('eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b')
			->willReturn(json_encode([
				'1',
				'2',
				'87',
				'123',
				'123',
				'124',
			]));
		$this->cache
			->expects($this->once())
			->method('set')
			->with(
				'eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b',
				json_encode([
					'87',
					'123',
					'123',
					'124',
					'186',
				])
			);

		$this->memoryCache->registerAttempt('Method', 'User', 100);
	}
}
