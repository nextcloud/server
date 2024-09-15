<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config;

use OC\Core\Command\Config\Import;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ImportTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$config = $this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var \OCP\IConfig $config */
		$this->command = new Import($config);
	}

	public function validateAppsArrayData() {
		return [
			[0],
			[1],
			[null],
			['new \Exception()'],
			[json_encode([])],
		];
	}

	/**
	 * @dataProvider validateAppsArrayData
	 *
	 * @param mixed $configValue
	 */
	public function testValidateAppsArray($configValue): void {
		$this->invokePrivate($this->command, 'validateAppsArray', [['app' => ['name' => $configValue]]]);
		$this->assertTrue(true, 'Asserting that no exception is thrown');
	}

	public function validateAppsArrayThrowsData() {
		return [
			[false],
			[true],
			[[]],
			[new \Exception()],
		];
	}

	/**
	 * @dataProvider validateAppsArrayThrowsData
	 *
	 * @param mixed $configValue
	 */
	public function testValidateAppsArrayThrows($configValue): void {
		try {
			$this->invokePrivate($this->command, 'validateAppsArray', [['app' => ['name' => $configValue]]]);
			$this->fail('Did not throw expected UnexpectedValueException');
		} catch (\UnexpectedValueException $e) {
			$this->assertStringStartsWith('Invalid app config value for "app":"name".', $e->getMessage());
		}
	}

	public function checkTypeRecursivelyData() {
		return [
			[0],
			[1],
			[null],
			['new \Exception()'],
			[json_encode([])],
			[false],
			[true],
			[[]],
			[['string']],
			[['test' => 'string']],
			[['test' => ['sub' => 'string']]],
		];
	}

	/**
	 * @dataProvider checkTypeRecursivelyData
	 *
	 * @param mixed $configValue
	 */
	public function testCheckTypeRecursively($configValue): void {
		$this->invokePrivate($this->command, 'checkTypeRecursively', [$configValue, 'name']);
		$this->assertTrue(true, 'Asserting that no exception is thrown');
	}

	public function checkTypeRecursivelyThrowsData() {
		return [
			[new \Exception()],
			[[new \Exception()]],
			[['test' => new \Exception()]],
			[['test' => ['sub' => new \Exception()]]],
		];
	}

	/**
	 * @dataProvider checkTypeRecursivelyThrowsData
	 *
	 * @param mixed $configValue
	 */
	public function testCheckTypeRecursivelyThrows($configValue): void {
		try {
			$this->invokePrivate($this->command, 'checkTypeRecursively', [$configValue, 'name']);
			$this->fail('Did not throw expected UnexpectedValueException');
		} catch (\UnexpectedValueException $e) {
			$this->assertStringStartsWith('Invalid system config value for "name"', $e->getMessage());
		}
	}

	public function validateArrayData() {
		return [
			[['system' => []]],
			[['apps' => []]],
			[['system' => [], 'apps' => []]],
		];
	}

	/**
	 * @dataProvider validateArrayData
	 *
	 * @param array $configArray
	 */
	public function testValidateArray($configArray): void {
		$this->invokePrivate($this->command, 'validateArray', [$configArray]);
		$this->assertTrue(true, 'Asserting that no exception is thrown');
	}

	public function validateArrayThrowsData() {
		return [
			[[], 'At least one key of the following is expected:'],
			[[0 => []], 'Found invalid entries in root'],
			[['string' => []], 'Found invalid entries in root'],
		];
	}

	/**
	 * @dataProvider validateArrayThrowsData
	 *
	 * @param mixed $configArray
	 * @param string $expectedException
	 */
	public function testValidateArrayThrows($configArray, $expectedException): void {
		try {
			$this->invokePrivate($this->command, 'validateArray', [$configArray]);
			$this->fail('Did not throw expected UnexpectedValueException');
		} catch (\UnexpectedValueException $e) {
			$this->assertStringStartsWith($expectedException, $e->getMessage());
		}
	}
}
