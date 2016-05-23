<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace Tests\Core\Command\Maintenance;


use OC\Core\Command\Maintenance\SingleUser;
use Test\TestCase;

class SingleUserTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleInput;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp() {
		parent::setUp();

		$config = $this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->consoleOutput = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

		/** @var \OCP\IConfig $config */
		$this->command = new SingleUser($config);
	}

	public function testChangeStateToOn() {

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('on')
			->willReturn(true);

		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('singleuser', true);

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with('Single user mode enabled');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testChangeStateToOff() {

		$this->consoleInput->expects($this->at(0))
			->method('getOption')
			->with('on')
			->willReturn(false);

		$this->consoleInput->expects($this->at(1))
			->method('getOption')
			->with('off')
			->willReturn(true);

		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('singleuser', false);

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with('Single user mode disabled');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function stateData() {
		return [
			[ true, 'Single user mode is currently enabled' ],
			[ false, 'Single user mode is currently disabled' ],
		];
	}

	/**
	 * @dataProvider stateData
	 *
	 * @param $state
	 * @param $expectedOutput
	 */
	public function testState($state, $expectedOutput) {

		$this->consoleInput->expects($this->at(0))
			->method('getOption')
			->with('on')
			->willReturn(false);

		$this->consoleInput->expects($this->at(1))
			->method('getOption')
			->with('off')
			->willReturn(false);

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('singleuser', false)
			->willReturn($state);

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($expectedOutput);

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
