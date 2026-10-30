<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Config\App;

use OC\AppConfig;
use OC\Core\Command\Config\App\SetConfig;
use OCP\Config\BeforeAppConfigSetEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SetConfigTest extends TestCase {
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;
	protected Command $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = $this->createInstanceWithMocks(
			SetConfig::class,
			[
				/* Mock internal class */
				'appConfig' => $this->getAutoMock(AppConfig::class),
			],
		);
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
		$this->getAutoMock(AppConfig::class)->method('hasKey')
			->with('app-name', $configName)
			->willReturn($configExists);

		if (!$configExists) {
			$this->getAutoMock(AppConfig::class)->method('getValueType')
				->willThrowException(new AppConfigUnknownKeyException());
		} else {
			$this->getAutoMock(AppConfig::class)->method('getValueType')
				->willReturn(IAppConfig::VALUE_MIXED);
		}

		if ($updated) {
			$this->getAutoMock(AppConfig::class)->expects($this->once())
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

	public function testSetRejected(): void {
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (BeforeAppConfigSetEvent $event): void {
				$this->assertSame('app-name', $event->getAppId());
				$this->assertSame('name', $event->getConfigKey());
				$this->assertSame('newvalue', $event->getConfigValue());
				$event->setValid(false);
			});
		$this->getAutoMock(AppConfig::class)->expects($this->never())
			->method('updateType');
		$this->getAutoMock(AppConfig::class)->expects($this->never())
			->method('setValueMixed');

		$this->consoleInput->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', 'name'],
			]);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', 'newvalue'],
			]);
		$this->consoleInput->method('hasParameterOption')
			->willReturnMap([
				['--update-only', false, false],
				['--type', false, false],
				['--value', false, true],
			]);
		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains('error'));

		$this->assertSame(1, self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public function testSetRejectedForced(): void {
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (BeforeAppConfigSetEvent $event): void {
				$event->setValid(false);
			});
		$this->getAutoMock(AppConfig::class)->method('getValueType')
			->willReturn(IAppConfig::VALUE_MIXED);
		$this->getAutoMock(AppConfig::class)->expects($this->once())
			->method('setValueMixed')
			->with('app-name', 'name', 'newvalue')
			->willReturn(true);
		$this->getAutoMock(AppConfig::class)->method('getDetails')
			->willReturn(['sensitive' => false, 'value' => 'newvalue', 'typeString' => 'mixed', 'lazy' => false]);

		$this->consoleInput->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', 'name'],
			]);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', 'newvalue'],
				['force', true],
			]);
		$this->consoleInput->method('hasParameterOption')
			->willReturnMap([
				['--update-only', false, false],
				['--type', false, false],
				['--value', false, true],
			]);

		$this->assertSame(0, self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}
}
