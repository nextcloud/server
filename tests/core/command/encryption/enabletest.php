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

namespace Tests\Core\Command\Encryption;


use OC\Core\Command\Encryption\Enable;
use Test\TestCase;

class EnableTest extends TestCase {
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
		$this->command = new Enable($config);
	}


	public function dataEnable() {
		return [
			['no', true, 'Encryption enabled'],
			['yes', false, 'Encryption is already enabled'],
		];
	}

	/**
	 * @dataProvider dataEnable
	 *
	 * @param string $oldStatus
	 * @param bool $isUpdating
	 * @param string $expectedString
	 */
	public function testEnable($oldStatus, $isUpdating, $expectedString) {
		$invoceCount = 0;
		$this->config->expects($this->at($invoceCount))
			->method('getAppValue')
			->with('core', 'encryption_enabled', $this->anything())
			->willReturn($oldStatus);
		$invoceCount++;

		if ($isUpdating) {
			$this->config->expects($this->once())
				->method('setAppValue')
				->with('core', 'encryption_enabled', 'yes');
			$invoceCount++;
		}

		$this->config->expects($this->at($invoceCount))
			->method('getAppValue')
			->with('core', 'default_encryption_module', $this->anything())
			->willReturnArgument(2);

		$this->consoleOutput->expects($this->at(0))
			->method('writeln')
			->with($expectedString);

		$this->consoleOutput->expects($this->at(1))
			->method('writeln')
			->with($this->stringContains('Default module'));

		\Test_Helper::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
