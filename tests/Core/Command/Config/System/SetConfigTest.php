<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\System;

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

	protected function setUp(): void {
		parent::setUp();

		$systemConfig = $this->systemConfig = $this->getMockBuilder(SystemConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var \OC\SystemConfig $systemConfig */
		$this->command = new SetConfig($systemConfig);
	}


	public function setData() {
		return [
			[['name'], 'newvalue', null, 'newvalue'],
			[['a', 'b', 'c'], 'foobar', null, ['b' => ['c' => 'foobar']]],
			[['a', 'b', 'c'], 'foobar', ['b' => ['d' => 'barfoo']], ['b' => ['d' => 'barfoo', 'c' => 'foobar']]],
		];
	}

	/**
	 * @dataProvider setData
	 *
	 * @param array $configNames
	 * @param string $newValue
	 * @param mixed $existingData
	 * @param mixed $expectedValue
	 */
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

	public function setUpdateOnlyProvider() {
		return [
			[['name'], null],
			[['a', 'b', 'c'], null],
			[['a', 'b', 'c'], ['b' => 'foobar']],
			[['a', 'b', 'c'], ['b' => ['d' => 'foobar']]],
		];
	}

	/**
	 * @dataProvider setUpdateOnlyProvider
	 */
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

	public function castValueProvider() {
		return [
			[null, 'string', ['value' => '', 'readable-value' => 'empty string']],

			['abc', 'string', ['value' => 'abc', 'readable-value' => 'string abc']],

			['123', 'integer', ['value' => 123, 'readable-value' => 'integer 123']],
			['456', 'int', ['value' => 456, 'readable-value' => 'integer 456']],

			['2.25', 'double', ['value' => 2.25, 'readable-value' => 'double 2.25']],
			['0.5', 'float', ['value' => 0.5, 'readable-value' => 'double 0.5']],

			['', 'null', ['value' => null, 'readable-value' => 'null']],

			['true', 'boolean', ['value' => true, 'readable-value' => 'boolean true']],
			['false', 'bool', ['value' => false, 'readable-value' => 'boolean false']],
		];
	}

	/**
	 * @dataProvider castValueProvider
	 */
	public function testCastValue($value, $type, $expectedValue): void {
		$this->assertSame($expectedValue,
			$this->invokePrivate($this->command, 'castValue', [$value, $type])
		);
	}

	public function castValueInvalidProvider() {
		return [
			['123', 'foobar'],

			[null, 'integer'],
			['abc', 'integer'],
			['76ggg', 'double'],
			['true', 'float'],
			['foobar', 'boolean'],
		];
	}

	/**
	 * @dataProvider castValueInvalidProvider
	 */
	public function testCastValueInvalid($value, $type): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->invokePrivate($this->command, 'castValue', [$value, $type]);
	}
}
