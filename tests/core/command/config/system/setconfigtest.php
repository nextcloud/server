<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Tests\Core\Command\Config\System;


use OC\Core\Command\Config\System\SetConfig;
use Test\TestCase;

class SetConfigTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $systemConfig;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleInput;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp() {
		parent::setUp();

		$systemConfig = $this->systemConfig = $this->getMockBuilder('OC\SystemConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->consoleOutput = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

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
	public function testSet($configNames, $newValue, $existingData, $expectedValue) {
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
			->will($this->returnValueMap([
				['value', $newValue],
				['type', 'string'],
			]));

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
	 * @expectedException \UnexpectedValueException
	 */
	public function testSetUpdateOnly($configNames, $existingData) {
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
			->will($this->returnValueMap([
				['value', 'foobar'],
				['type', 'string'],
				['update-only', true],
			]));

		$this->invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function castValueProvider() {
		return [
			[null, 'integer', null],
			[null, 'string', null],

			['abc', 'string', 'abc'],
			['dEF', 'str', 'dEF'],
			['123', 's', '123'],

			['123', 'integer', 123],
			['456', 'int', 456],
			['-666', 'i', -666],

			// only use powers of 2 to avoid precision errors
			['2', 'double', 2.0],
			['0.25', 'd', 0.25],
			['0.5', 'float', 0.5],
			['0.125', 'f', 0.125],

			['true', 'boolean', true],
			['false', 'bool', false],
			['yes', 'b', true],
			['no', 'b', false],
			['y', 'b', true],
			['n', 'b', false],
			['1', 'b', true],
			['0', 'b', false],
		];
	}

	/**
	 * @dataProvider castValueProvider
	 */
	public function testCastValue($value, $type, $expectedValue) {
		$this->assertSame($expectedValue,
			$this->invokePrivate($this->command, 'castValue', [$value, $type])
		);
	}

	public function castValueInvalidProvider() {
		return [
			['123', 'foobar'],

			['abc', 'integer'],
			['76ggg', 'double'],
			['true', 'float'],
			['foobar', 'boolean'],
		];
	}

	/**
	 * @dataProvider castValueInvalidProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testCastValueInvalid($value, $type) {
		$this->invokePrivate($this->command, 'castValue', [$value, $type]);
	}

}
