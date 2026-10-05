<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command;

use OC\Core\Command\SetupChecks;
use OCP\Migration\IOutput;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\ISetupCheckManager;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class SetupChecksTestCheck implements ISetupCheck {
	#[\Override]
	public function getCategory(): string {
		return 'system';
	}

	#[\Override]
	public function getName(): string {
		return 'Test check';
	}

	#[\Override]
	public function run(): SetupResult {
		return SetupResult::success();
	}
}

class SetupChecksTest extends TestCase {
	private ISetupCheckManager&MockObject $setupCheckManager;
	private IRichTextFormatter&MockObject $richTextFormatter;
	private CommandTester $commandTester;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->setupCheckManager = $this->createMock(ISetupCheckManager::class);
		$this->richTextFormatter = $this->createMock(IRichTextFormatter::class);

		$this->commandTester = new CommandTester(
			new SetupChecks($this->setupCheckManager, $this->richTextFormatter)
		);
	}

	/**
	 * Report one check through the output the command passes to the manager, and return its result.
	 */
	private function runOneCheck(?IOutput $output): array {
		$this->assertInstanceOf(IOutput::class, $output);
		$check = new SetupChecksTestCheck();
		$output->debug('Starting check ' . $check->getName() . ' (' . $check::class . ')');
		$output->debug('Check ' . $check->getName() . ' (' . $check::class . ') done in 0.01 seconds, peak memory usage: 8 MB');
		$result = SetupResult::success('Everything is fine');
		$result->setName($check->getName());
		return ['system' => [$check::class => $result]];
	}

	private function expectRunAll(): void {
		$this->setupCheckManager->expects($this->once())
			->method('runAll')
			->willReturnCallback($this->runOneCheck(...));
	}

	public function testProgressIsReportedOnVerboseOutput(): void {
		$this->expectRunAll();

		$this->assertSame(Command::SUCCESS, $this->commandTester->execute([], [
			'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
			'capture_stderr_separately' => true,
		]));

		$this->assertStringContainsString('Starting check Test check (' . SetupChecksTestCheck::class . ')', $this->commandTester->getErrorOutput());
		$this->assertStringContainsString('done in 0.01 seconds, peak memory usage: 8 MB', $this->commandTester->getErrorOutput());
		$this->assertStringContainsString('Everything is fine', $this->commandTester->getDisplay());
		$this->assertStringNotContainsString('Starting check Test check', $this->commandTester->getDisplay());
	}

	public function testProgressIsNotReportedOnNormalOutput(): void {
		$this->expectRunAll();

		$this->assertSame(Command::SUCCESS, $this->commandTester->execute([], [
			'capture_stderr_separately' => true,
		]));

		$this->assertStringNotContainsString('Starting check Test check', $this->commandTester->getErrorOutput());
		$this->assertStringNotContainsString('Starting check Test check', $this->commandTester->getDisplay());
	}

	public function testProgressKeepsJsonOutputParsable(): void {
		$this->expectRunAll();

		$this->assertSame(Command::SUCCESS, $this->commandTester->execute(['--output' => 'json'], [
			'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
			'capture_stderr_separately' => true,
		]));

		$this->assertStringContainsString('Starting check Test check', $this->commandTester->getErrorOutput());
		$this->assertIsArray(json_decode($this->commandTester->getDisplay(), true, flags: JSON_THROW_ON_ERROR));
	}

	public function testFilterByCategory(): void {
		$this->setupCheckManager->expects($this->once())
			->method('runByCategory')
			->willReturnCallback(function (string $category, ?IOutput $output): array {
				$this->assertSame('system', $category);
				return $this->runOneCheck($output);
			});

		$this->assertSame(Command::SUCCESS, $this->commandTester->execute(['category' => 'system']));
	}

	public function testFilterByClass(): void {
		$this->setupCheckManager->expects($this->once())
			->method('runByClass')
			->willReturnCallback(function (string $class, ?IOutput $output): array {
				$this->assertSame(SetupChecksTestCheck::class, $class);
				return $this->runOneCheck($output);
			});

		$this->assertSame(Command::SUCCESS, $this->commandTester->execute(['class' => SetupChecksTestCheck::class]));
	}

	public function testFilterByCategoryAndClassIsRejected(): void {
		$this->setupCheckManager->expects($this->never())
			->method($this->anything());

		$this->assertSame(Command::FAILURE, $this->commandTester->execute([
			'category' => 'system',
			'class' => SetupChecksTestCheck::class,
		]));
		$this->assertStringContainsString('Please specify only one of category or class', $this->commandTester->getDisplay());
	}
}
