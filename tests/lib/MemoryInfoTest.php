<?php

namespace Test;

use OC\MemoryInfo;

/**
 * This class provides tests for the MemoryInfo class.
 */
class MemoryInfoTest extends TestCase {
	/**
	 * @var MemoryInfo
	 */
	private $memoryInfo;

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
	 * Setups a MemoryInfo instance for tests.
	 *
	 * @before
	 */
	public function setupMemoryInfo() {
		$this->memoryInfo = new MemoryInfo();
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
	public function testMemoryLimit($iniValue, $expected) {
		ini_set('memory_limit', $iniValue);
		self::assertEquals($expected, $this->memoryInfo->getMemoryLimit());
	}
}
