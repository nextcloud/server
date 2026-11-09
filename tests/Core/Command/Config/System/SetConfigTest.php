<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\System;

use OC\Core\Command\Config\System\CastHelper;
use OC\Core\Command\Config\System\SetConfig;
use OC\SystemConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SetConfigTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $systemConfig;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$systemConfig = $this->systemConfig = $this->getMockBuilder(SystemConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var SystemConfig $systemConfig */
		$this->command = new SetConfig($systemConfig, new CastHelper());
	}

	public static function dataTest() {
		return [
			[['name'], 'newvalue', null, 'newvalue'],
			[['a', 'b', 'c'], 'foobar', null, ['b' => ['c' => 'foobar']]],
			[['a', 'b', 'c'], 'foobar', ['b' => ['d' => 'barfoo']], ['b' => ['d' => 'barfoo', 'c' => 'foobar']]],
		];
	}

	/**
	 *
	 * @param array $configNames
	 * @param string $newValue
	 * @param mixed $existingData
	 * @param mixed $expectedValue
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTest')]
	public function testSet($configNames, $newValue, $existingData, $expectedValue): void {
		$this->systemConfig->expects($this->once())
			->method('setValue')
			->with($configNames[0], $expectedValue);
		$this->systemConfig->method('getValue')
			->with($configNames[0])
			->willReturn($existingData);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('name')
			->willReturn($configNames);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', $newValue],
				['type', 'string'],
			]);

		$this->invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public static function setUpdateOnlyProvider(): array {
		return [
			[['name'], null],
			[['a', 'b', 'c'], null],
			[['a', 'b', 'c'], ['b' => 'foobar']],
			[['a', 'b', 'c'], ['b' => ['d' => 'foobar']]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('setUpdateOnlyProvider')]
	public function testSetUpdateOnly($configNames, $existingData): void {
		$this->expectException(\UnexpectedValueException::class);

		$this->systemConfig->expects($this->never())
			->method('setValue');
		$this->systemConfig->method('getValue')
			->with($configNames[0])
			->willReturn($existingData);
		$this->systemConfig->method('getKeys')
			->willReturn($existingData ? $configNames[0] : []);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('name')
			->willReturn($configNames);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', 'foobar'],
				['type', 'string'],
				['update-only', true],
			]);

		$this->invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public static function dataAlreadySet(): array {
		return [
			[['name'], 'same', 'same', null],
			[['a', 'b', 'c'], 'foobar', 'foobar', ['b' => ['c' => 'foobar']]],
			[['name'], true, 'true', null, 'boolean'],
			[['name'], 42, '42', null, 'integer'],
		];
	}

	/**
	 * @param array $configNames
	 * @param mixed $storedValue For nested keys, leaf value; for single key, the stored value
	 * @param string $inputValue
	 * @param mixed|null $existingNested Root array for nested names; unused for single key
	 * @param string $type
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataAlreadySet')]
	public function testAlreadySet($configNames, $storedValue, $inputValue, $existingNested, $type = 'string'): void {
		$this->systemConfig->expects($this->never())
			->method('setValue');

		if (count($configNames) === 1) {
			$this->systemConfig->method('getKeys')
				->willReturn([$configNames[0]]);
			$this->systemConfig->method('getValue')
				->with($configNames[0])
				->willReturn($storedValue);
		} else {
			$this->systemConfig->method('getValue')
				->with($configNames[0])
				->willReturn($existingNested);
		}

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('name')
			->willReturn($configNames);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', $inputValue],
				['type', $type],
				['update-only', false],
			]);

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains('already set to'));

		$this->assertSame(0, $this->invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

}
