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


use OC\Core\Command\Config\System\DeleteConfig;
use Test\TestCase;

class DeleteConfigTest extends TestCase {
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
		$this->command = new DeleteConfig($systemConfig);
	}


	public function deleteData() {
		return [
			[
				'name',
				true,
				true,
				0,
				'info',
			],
			[
				'name',
				true,
				false,
				0,
				'info',
			],
			[
				'name',
				false,
				false,
				0,
				'info',
			],
			[
				'name',
				false,
				true,
				1,
				'error',
			],
		];
	}

	/**
	 * @dataProvider deleteData
	 *
	 * @param string $configName
	 * @param bool $configExists
	 * @param bool $checkIfExists
	 * @param int $expectedReturn
	 * @param string $expectedMessage
	 */
	public function testDelete($configName, $configExists, $checkIfExists, $expectedReturn, $expectedMessage) {
		$this->systemConfig->expects(($checkIfExists) ? $this->once() : $this->never())
			->method('getKeys')
			->willReturn($configExists ? [$configName] : []);

		$this->systemConfig->expects(($expectedReturn === 0) ? $this->once() : $this->never())
			->method('deleteValue')
			->with($configName);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('name')
			->willReturn($configName);
		$this->consoleInput->expects($this->any())
			->method('hasParameterOption')
			->with('--error-if-not-exists')
			->willReturn($checkIfExists);

		$this->consoleOutput->expects($this->any())
			->method('writeln')
			->with($this->stringContains($expectedMessage));

		$this->assertSame($expectedReturn, \Test_Helper::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}
}
