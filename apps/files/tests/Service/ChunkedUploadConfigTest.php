<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Tests\Service;

use OCA\Files\Service\ChunkedUploadConfig;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ChunkedUploadConfigTest extends TestCase {
	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->overwriteService(IConfig::class, $this->config);
	}

	protected function tearDown(): void {
		$this->restoreAllServices();
		parent::tearDown();
	}

	public static function dataGetMaxParallelCount(): array {
		return [
			'configured positive value' => [3, 3],
			'boundary minimum' => [1, 1],
			'zero becomes one' => [0, 1],
			'negative becomes one' => [-2, 1],
			'large value passes through' => [100, 100],
		];
	}

	#[DataProvider('dataGetMaxParallelCount')]
	public function testGetMaxParallelCount(int $configuredValue, int $expectedValue): void {
		$this->config->expects($this->once())
			->method('getSystemValueInt')
			->with('files.chunked_upload.max_parallel_count', 5)
			->willReturn($configuredValue);

		$this->assertSame($expectedValue, ChunkedUploadConfig::getMaxParallelCount());
	}
}
