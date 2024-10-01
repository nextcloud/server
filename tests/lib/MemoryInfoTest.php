<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\MemoryInfo;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * This class provides tests for the MemoryInfo class.
 */
class MemoryInfoTest extends TestCase {
	/**
	 * The "memory_limit" value before tests.
	 *
	 * @var string
	 */
	private $iniSettingBeforeTest;

	/**
	 * @beforeClass
	 */
	public function backupMemoryInfoIniSetting() {
		$this->iniSettingBeforeTest = ini_get('memory_limit');
	}

	/**
	 * @afterClass
	 */
	public function restoreMemoryInfoIniSetting() {
		ini_set('memory_limit', $this->iniSettingBeforeTest);
	}

	/**
	 * Provides test data.
	 *
	 * @return array
	 */
	public function getMemoryLimitTestData(): array {
		return [
			'unlimited' => ['-1', -1,],
			'524288000 bytes' => ['524288000', 524288000,],
			'500M' => ['500M', 524288000,],
			'512000K' => ['512000K', 524288000,],
			'2G' => ['2G', 2147483648,],
		];
	}

	/**
	 * Tests that getMemoryLimit works as expected.
	 *
	 * @param string $iniValue The "memory_limit" ini data.
	 * @param int|float $expected The expected detected memory limit.
	 * @dataProvider getMemoryLimitTestData
	 */
	public function testMemoryLimit(string $iniValue, int|float $expected): void {
		ini_set('memory_limit', $iniValue);
		$memoryInfo = new MemoryInfo();
		self::assertEquals($expected, $memoryInfo->getMemoryLimit());
	}

	/**
	 * Provides sufficient memory limit test data.
	 *
	 * @return array
	 */
	public function getSufficientMemoryTestData(): array {
		return [
			'unlimited' => [-1, true,],
			'512M' => [512 * 1024 * 1024, true,],
			'1G' => [1024 * 1024 * 1024, true,],
			'256M' => [256 * 1024 * 1024, false,],
		];
	}

	/**
	 * Tests that isMemoryLimitSufficient returns the correct values.
	 *
	 * @param int $memoryLimit The memory limit
	 * @param bool $expected If the memory limit is sufficient.
	 * @dataProvider getSufficientMemoryTestData
	 * @return void
	 */
	public function testIsMemoryLimitSufficient(int $memoryLimit, bool $expected): void {
		/* @var MemoryInfo|MockObject $memoryInfo */
		$memoryInfo = $this->getMockBuilder(MemoryInfo::class)
			->setMethods(['getMemoryLimit',])
			->getMock();

		$memoryInfo
			->method('getMemoryLimit')
			->willReturn($memoryLimit);

		$isMemoryLimitSufficient = $memoryInfo->isMemoryLimitSufficient();
		self::assertEquals($expected, $isMemoryLimitSufficient);
	}
}
