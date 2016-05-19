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


use OC\Core\Command\Encryption\SetDefaultModule;
use Test\TestCase;

class SetDefaultModuleTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $manager;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleInput;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp() {
		parent::setUp();

		$manager = $this->manager = $this->getMockBuilder('OCP\Encryption\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->consoleOutput = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

		/** @var \OCP\Encryption\IManager $manager */
		$this->command = new SetDefaultModule($manager);
	}


	public function dataSetDefaultModule() {
		return [
			['ID0', 'ID0', null, null, 'already'],
			['ID0', 'ID1', 'ID1', true, 'info'],
			['ID0', 'ID1', 'ID1', false, 'error'],
		];
	}

	/**
	 * @dataProvider dataSetDefaultModule
	 *
	 * @param string $oldModule
	 * @param string $newModule
	 * @param string $updateModule
	 * @param bool $updateSuccess
	 * @param string $expectedString
	 */
	public function testSetDefaultModule($oldModule, $newModule, $updateModule, $updateSuccess, $expectedString) {
		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('module')
			->willReturn($newModule);

		$this->manager->expects($this->once())
			->method('getDefaultEncryptionModuleId')
			->willReturn($oldModule);
		if ($updateModule) {
			$this->manager->expects($this->once())
				->method('setDefaultEncryptionModule')
				->with($updateModule)
				->willReturn($updateSuccess);
		}

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains($expectedString));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
