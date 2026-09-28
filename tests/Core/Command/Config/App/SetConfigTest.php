<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\App;

use OC\AppConfig;
use OC\Config\ConfigManager;
use OC\Core\Command\Config\App\SetConfig;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SetConfigTest extends TestCase {
	protected IAppConfig&MockObject $appConfig;
	protected ConfigManager&MockObject $configManager;
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;
	protected Command $command;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(AppConfig::class);
		$this->configManager = $this->createMock(ConfigManager::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = new SetConfig($this->appConfig, $this->configManager);
	}


	public static function dataSet(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSet')]
	public function testSet(string $configName, mixed $newValue, bool $configExists, bool $updateOnly, bool $updated, string $expectedMessage): void {
		$this->appConfig->method('hasKey')
			->with('app-name', $configName)
			->willReturn($configExists);

		if (!$configExists) {
			$this->appConfig->method('getValueType')
				->willThrowException(new AppConfigUnknownKeyException());
		} else {
			$this->appConfig->method('getValueType')
				->willReturn(IAppConfig::VALUE_MIXED);
		}

		if ($updated) {
			$this->appConfig->expects($this->once())
				->method('setValueMixed')
				->with('app-name', $configName, $newValue);
		}

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', $newValue],
				['lazy', null],
				['sensitive', null],
				['no-interaction', true],
			]);
		$this->consoleInput->method('hasParameterOption')
			->willReturnMap([
				['--type', false, false],
				['--value', false, true],
				['--update-only', false, $updateOnly]
			]);
		$this->consoleOutput->method('writeln')
			->with($this->stringContains($expectedMessage));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
