<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Bruteforce\Backend;

use OC\Security\Bruteforce\Backend\IBackend;
use OC\Security\Bruteforce\Backend\MemoryCacheBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MemoryCacheBackendTest extends TestCase {
	/** @var ICacheFactory|MockObject */
	private $cacheFactory;
	/** @var ITimeFactory|MockObject */
	private $timeFactory;
	/** @var ICache|MockObject */
	private $cache;
	private IBackend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->cache = $this->createMock(ICache::class);

		$this->cacheFactory
			->expects($this->once())
			->method('createDistributed')
			->with('OC\Security\Bruteforce\Backend\MemoryCacheBackend')
			->willReturn($this->cache);

		$this->backend = new MemoryCacheBackend(
			$this->cacheFactory,
			$this->timeFactory
		);
	}

	public function testGetAttemptsWithNoAttemptsBefore(): void {
		$this->cache
			->expects($this->once())
			->method('get')
			->with('8b9da631d1f7b022bb2c3c489e16092f82b42fd4')
			->willReturn(null);

		$this->assertSame(0, $this->backend->getAttempts('10.10.10.10/32', 0));
	}

	public function dataGetAttempts(): array {
		return [
			[0, null, null, 4],
			[100, null, null, 2],
			[0, 'action1', null, 2],
			[100, 'action1', null, 1],
			[0, 'action1', ['metadata2'], 1],
			[100, 'action1', ['metadata2'], 1],
			[100, 'action1', ['metadata1'], 0],
		];
	}

	/**
	 * @dataProvider dataGetAttempts
	 */
	public function testGetAttempts(int $maxAge, ?string $action, ?array $metadata, int $expected): void {
		$this->cache
			->expects($this->once())
			->method('get')
			->with('8b9da631d1f7b022bb2c3c489e16092f82b42fd4')
			->willReturn(json_encode([
				'1' . '#' . hash('sha1', 'action1') . '#' . hash('sha1', json_encode(['metadata1'])),
				'300' . '#' . hash('sha1', 'action1') . '#' . hash('sha1', json_encode(['metadata2'])),
				'1' . '#' . hash('sha1', 'action2') . '#' . hash('sha1', json_encode(['metadata1'])),
				'300' . '#' . hash('sha1', 'action2') . '#' . hash('sha1', json_encode(['metadata2'])),
			]));

		$this->assertSame($expected, $this->backend->getAttempts('10.10.10.10/32', $maxAge, $action, $metadata));
	}

	public function testRegisterAttemptWithNoAttemptsBefore(): void {
		$this->cache
			->expects($this->once())
			->method('get')
			->with('8b9da631d1f7b022bb2c3c489e16092f82b42fd4')
			->willReturn(null);
		$this->cache
			->expects($this->once())
			->method('set')
			->with(
				'8b9da631d1f7b022bb2c3c489e16092f82b42fd4',
				json_encode(['223#' . hash('sha1', 'action1') . '#' . hash('sha1', json_encode(['metadata1']))])
			);

		$this->backend->registerAttempt('10.10.10.10', '10.10.10.10/32', 223, 'action1', ['metadata1']);
	}

	public function testRegisterAttempt(): void {
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(12 * 3600 + 86);

		$this->cache
			->expects($this->once())
			->method('get')
			->with('8b9da631d1f7b022bb2c3c489e16092f82b42fd4')
			->willReturn(json_encode([
				'1#' . hash('sha1', 'action1') . '#' . hash('sha1', json_encode(['metadata1'])),
				'2#' . hash('sha1', 'action2') . '#' . hash('sha1', json_encode(['metadata1'])),
				'87#' . hash('sha1', 'action3') . '#' . hash('sha1', json_encode(['metadata1'])),
				'123#' . hash('sha1', 'action4') . '#' . hash('sha1', json_encode(['metadata1'])),
				'123#' . hash('sha1', 'action5') . '#' . hash('sha1', json_encode(['metadata1'])),
				'124#' . hash('sha1', 'action6') . '#' . hash('sha1', json_encode(['metadata1'])),
			]));
		$this->cache
			->expects($this->once())
			->method('set')
			->with(
				'8b9da631d1f7b022bb2c3c489e16092f82b42fd4',
				json_encode([
					'87#' . hash('sha1', 'action3') . '#' . hash('sha1', json_encode(['metadata1'])),
					'123#' . hash('sha1', 'action4') . '#' . hash('sha1', json_encode(['metadata1'])),
					'123#' . hash('sha1', 'action5') . '#' . hash('sha1', json_encode(['metadata1'])),
					'124#' . hash('sha1', 'action6') . '#' . hash('sha1', json_encode(['metadata1'])),
					'186#' . hash('sha1', 'action7') . '#' . hash('sha1', json_encode(['metadata2'])),
				])
			);

		$this->backend->registerAttempt('10.10.10.10', '10.10.10.10/32', 186, 'action7', ['metadata2']);
	}
}
