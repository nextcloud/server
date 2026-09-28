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

		/** @var IConfig $config */
		$this->command = new Import($config);
	}

	public static function validateAppsArrayData(): array {
		return [
			[0],
			[1],
			[null],
			['new \Exception()'],
			[json_encode([])],
		];
	}

	/**
	 * @param mixed $configValue
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('validateAppsArrayData')]
	public function testValidateAppsArray($configValue): void {
		$this->invokePrivate($this->command, 'validateAppsArray', [['app' => ['name' => $configValue]]]);
		$this->assertTrue(true, 'Asserting that no exception is thrown');
	}

	public static function validateAppsArrayThrowsData(): array {
		return [
			[false],
			[true],
			[[]],
			[new \Exception()],
		];
	}

	/**
	 * @param mixed $configValue
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('validateAppsArrayThrowsData')]
	public function testValidateAppsArrayThrows($configValue): void {
		try {
			$this->invokePrivate($this->command, 'validateAppsArray', [['app' => ['name' => $configValue]]]);
			$this->fail('Did not throw expected UnexpectedValueException');
		} catch (\UnexpectedValueException $e) {
			$this->assertStringStartsWith('Invalid app config value for "app":"name".', $e->getMessage());
		}
	}

	public static function checkTypeRecursivelyData(): array {
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
	 * @param mixed $configValue
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('checkTypeRecursivelyData')]
	public function testCheckTypeRecursively($configValue): void {
		$this->invokePrivate($this->command, 'checkTypeRecursively', [$configValue, 'name']);
		$this->assertTrue(true, 'Asserting that no exception is thrown');
	}

	public static function checkTypeRecursivelyThrowsData(): array {
		return [
			[new \Exception()],
			[[new \Exception()]],
			[['test' => new \Exception()]],
			[['test' => ['sub' => new \Exception()]]],
		];
	}

	/**
	 * @param mixed $configValue
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('checkTypeRecursivelyThrowsData')]
	public function testCheckTypeRecursivelyThrows($configValue): void {
		try {
			$this->invokePrivate($this->command, 'checkTypeRecursively', [$configValue, 'name']);
			$this->fail('Did not throw expected UnexpectedValueException');
		} catch (\UnexpectedValueException $e) {
			$this->assertStringStartsWith('Invalid system config value for "name"', $e->getMessage());
		}
	}

	public static function validateArrayData(): array {
		return [
			[['system' => []]],
			[['apps' => []]],
			[['system' => [], 'apps' => []]],
		];
	}

	/**
	 * @param array $configArray
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('validateArrayData')]
	public function testValidateArray($configArray): void {
		$this->invokePrivate($this->command, 'validateArray', [$configArray]);
		$this->assertTrue(true, 'Asserting that no exception is thrown');
	}

	public static function validateArrayThrowsData(): array {
		return [
			[[], 'At least one key of the following is expected:'],
			[[0 => []], 'Found invalid entries in root'],
			[['string' => []], 'Found invalid entries in root'],
		];
	}

	/**
	 *
	 * @param mixed $configArray
	 * @param string $expectedException
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('validateArrayThrowsData')]
	public function testValidateArrayThrows($configArray, $expectedException): void {
		try {
			$this->invokePrivate($this->command, 'validateArray', [$configArray]);
			$this->fail('Did not throw expected UnexpectedValueException');
		} catch (\UnexpectedValueException $e) {
			$this->assertStringStartsWith($expectedException, $e->getMessage());
		}
	}
}
