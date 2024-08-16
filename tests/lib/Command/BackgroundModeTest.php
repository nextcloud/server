<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 Christian Kampka <christian@kampka.net>
 * SPDX-License-Identifier: MIT
 */
namespace Test\Command;

use OC\Core\Command\Background\Mode;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class BackgroundModeTest extends TestCase {
	private IAppConfig $appConfig;

	private Mode $command;

	public function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);

		$inputDefinition = new InputDefinition([
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
		]);

		$this->command = new Mode($this->appConfig);
		$this->command->setDefinition($inputDefinition);
	}

	/**
	 * @dataProvider dataModeCommand
	 */
	public function testModeCommand(string $mode): void {
		$this->appConfig->expects($this->once())
			->method('setValueString')
			->with('core', 'backgroundjobs_mode', $mode);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute(['command' => 'background:' . $mode]);

		$commandTester->assertCommandIsSuccessful();

		$output = $commandTester->getDisplay();
		$this->assertStringContainsString($mode, $output);
	}

	public function dataModeCommand(): array {
		return [
			'ajax' => ['ajax'],
			'cron' => ['cron'],
			'webcron' => ['webcron'],
		];
	}
}
