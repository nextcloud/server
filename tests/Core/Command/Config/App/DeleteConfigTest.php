<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\App;

use OC\Core\Command\Config\App\DeleteConfig;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DeleteConfigTest extends TestCase {
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		$this->command = new DeleteConfig($this->config);
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
	public function testDelete($configName, $configExists, $checkIfExists, $expectedReturn, $expectedMessage): void {
		$this->config->expects(($checkIfExists) ? $this->once() : $this->never())
			->method('getAppKeys')
			->with('app-name')
			->willReturn($configExists ? [$configName] : []);

		$this->config->expects(($expectedReturn === 0) ? $this->once() : $this->never())
			->method('deleteAppValue')
			->with('app-name', $configName);

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->expects($this->any())
			->method('hasParameterOption')
			->with('--error-if-not-exists')
			->willReturn($checkIfExists);

		$this->consoleOutput->expects($this->any())
			->method('writeln')
			->with($this->stringContains($expectedMessage));

		$this->assertSame($expectedReturn, $this->invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}
}
