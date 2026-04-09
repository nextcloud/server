<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\App;

use OC\Config\ConfigManager;
use OC\Core\Command\Config\App\DeleteConfig;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DeleteConfigTest extends TestCase {
	protected IAppConfig&MockObject $appConfig;
	protected ConfigManager&MockObject $configManager;
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;
	protected Command $command;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->configManager = $this->createMock(ConfigManager::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = new DeleteConfig($this->appConfig, $this->configManager);
	}


	public static function dataDelete(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataDelete')]
	public function testDelete(string $configName, bool $configExists, bool $checkIfExists, int $expectedReturn, string $expectedMessage): void {
		$this->appConfig->expects(($checkIfExists) ? $this->once() : $this->never())
			->method('getKeys')
			->with('app-name')
			->willReturn($configExists ? [$configName] : []);

		$this->appConfig->expects(($expectedReturn === 0) ? $this->once() : $this->never())
			->method('deleteKey')
			->with('app-name', $configName);

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->method('hasParameterOption')
			->with('--error-if-not-exists')
			->willReturn($checkIfExists);

		$this->consoleOutput->method('writeln')
			->with($this->stringContains($expectedMessage));

		$this->assertSame($expectedReturn, self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}
}
