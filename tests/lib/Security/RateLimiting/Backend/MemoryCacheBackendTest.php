<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Security\RateLimiting\Backend;

use OC\Security\RateLimiting\Backend\MemoryCacheBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use Test\TestCase;

class MemoryCacheBackendTest extends TestCase {
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

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->cache = $this->createMock(ICache::class);

		$this->cacheFactory
			->expects($this->once())
			->method('createDistributed')
			->with('OC\Security\RateLimiting\Backend\MemoryCacheBackend')
			->willReturn($this->cache);

		$this->memoryCache = new MemoryCacheBackend(
			$this->cacheFactory,
			$this->timeFactory
		);
	}

	public function testGetAttemptsWithNoAttemptsBefore() {
		$this->cache
			->expects($this->once())
			->method('get')
			->with('eea460b8d756885099c7f0a4c083bf6a745069ee4a301984e726df58fd4510bffa2dac4b7fd5d835726a6753ffa8343ba31c7e902bbef78fc68c2e743667cb4b')
			->willReturn(null);

		$this->assertSame(0, $this->memoryCache->getAttempts('Method', 'User'));
	}

	public function testGetAttempts() {
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

	public function testRegisterAttemptWithNoAttemptsBefore() {
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

	public function testRegisterAttempt() {
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
