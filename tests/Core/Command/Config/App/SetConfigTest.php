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

namespace Tests\Core\Command\Config\App;

use OC\AppConfig;
use OC\Core\Command\Config\App\SetConfig;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SetConfigTest extends TestCase {
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

		$config = $this->config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var \OCP\IAppConfig $config */
		$this->command = new SetConfig($config);
	}


	public function setData() {
		return [
			[
				'name',
				'newvalue',
				true,
				true,
				true,
				'info',
			],
			[
				'name',
				'newvalue',
				false,
				true,
				false,
				'comment',
			],
		];
	}

	/**
	 * @dataProvider setData
	 *
	 * @param string $configName
	 * @param mixed $newValue
	 * @param bool $configExists
	 * @param bool $updateOnly
	 * @param bool $updated
	 * @param string $expectedMessage
	 */
	public function testSet($configName, $newValue, $configExists, $updateOnly, $updated, $expectedMessage) {
		$this->config->expects($this->any())
					 ->method('hasKey')
					 ->with('app-name', $configName)
					 ->willReturn($configExists);

		if (!$configExists) {
			$this->config->expects($this->any())
						 ->method('getValueType')
						 ->willThrowException(new AppConfigUnknownKeyException());
		} else {
			$this->config->expects($this->any())
						 ->method('getValueType')
						 ->willReturn(IAppConfig::VALUE_MIXED);
		}

		if ($updated) {
			$this->config->expects($this->once())
				->method('setValueMixed')
				->with('app-name', $configName, $newValue);
		}

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->expects($this->any())
			->method('getOption')
			->willReturnMap([
				['value', $newValue],
				['lazy', null],
				['sensitive', null],
				['no-interaction', true],
			]);
		$this->consoleInput->expects($this->any())
			->method('hasParameterOption')
			->willReturnMap([
				['--type', false, false],
				['--value', false, true],
				['--update-only', false, $updateOnly]
			]);
		$this->consoleOutput->expects($this->any())
			->method('writeln')
			->with($this->stringContains($expectedMessage));

		$this->invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
