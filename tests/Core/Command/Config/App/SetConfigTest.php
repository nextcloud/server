<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	public function testSet($configName, $newValue, $configExists, $updateOnly, $updated, $expectedMessage): void {
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
