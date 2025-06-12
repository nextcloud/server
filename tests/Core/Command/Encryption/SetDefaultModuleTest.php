<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\SetDefaultModule;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SetDefaultModuleTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject|IManager */
	protected $manager;
	/** @var \PHPUnit\Framework\MockObject\MockObject|IConfig */
	protected $config;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->getMock();

		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		$this->command = new SetDefaultModule($this->manager, $this->config);
	}


	public static function dataSetDefaultModule(): array {
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
	public function testSetDefaultModule($oldModule, $newModule, $updateModule, $updateSuccess, $expectedString): void {
		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('module')
			->willReturn($newModule);

		$this->manager->expects($this->once())
			->method('getDefaultEncryptionModuleId')
			->willReturn($oldModule);

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('maintenance', false)
			->willReturn(false);

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

	/**
	 * @dataProvider dataSetDefaultModule
	 *
	 * @param string $oldModule
	 * @param string $newModule
	 * @param string $updateModule
	 * @param bool $updateSuccess
	 * @param string $expectedString
	 */
	public function testMaintenanceMode($oldModule, $newModule, $updateModule, $updateSuccess, $expectedString): void {
		$this->consoleInput->expects($this->never())
			->method('getArgument')
			->with('module')
			->willReturn($newModule);

		$this->manager->expects($this->never())
			->method('getDefaultEncryptionModuleId')
			->willReturn($oldModule);

		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('maintenance', false)
			->willReturn(true);

		$calls = [
			'Maintenance mode must be disabled when setting default module,',
			'in order to load the relevant encryption modules correctly.',
		];
		$this->consoleOutput->expects($this->exactly(2))
			->method('writeln')
			->willReturnCallback(function ($message) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertStringContainsString($expected, $message);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
