<?php

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
			'0' => ['0', 0,],
			'134217728 bytes' => ['134217728', 134217728,],
			'128M' => ['128M', 134217728,],
			'131072K' => ['131072K', 134217728,],
			'2G' => ['2G', 2147483648,],
		];
	}

	/**
	 * Tests that getMemoryLimit works as expected.
	 *
	 * @param string $iniValue The "memory_limit" ini data.
	 * @param int $expected The expected detected memory limit.
	 * @dataProvider getMemoryLimitTestData
	 */
	public function testMemoryLimit($iniValue, int $expected) {
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
	public function testIsMemoryLimitSufficient(int $memoryLimit, bool $expected) {
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
