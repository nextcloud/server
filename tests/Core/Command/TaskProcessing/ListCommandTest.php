<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\TaskProcessing;

use OC\Core\Command\TaskProcessing\ListCommand;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ListCommandTest extends TestCase {
	private IManager&MockObject $manager;
	private ListCommand&MockObject $command;
	private BufferedOutput $output;

	/** @var list<array>|null */
	private ?array $writtenTasks = null;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(IManager::class);
		$this->command = $this->getMockBuilder(ListCommand::class)
			->setConstructorArgs([$this->manager])
			->onlyMethods(['writeArrayInOutputFormat'])
			->getMock();
		$this->output = new BufferedOutput();
	}

	/**
	 * @return list<Task>
	 */
	private function createTasks(int $count): array {
		$tasks = [];
		for ($i = 0; $i < $count; $i++) {
			$tasks[] = new Task('test:tasktype', ['input' => 'Hello'], 'testapp', null);
		}
		return $tasks;
	}

	private function runCommand(array $arguments = []): int {
		$this->writtenTasks = null;
		$this->command->method('writeArrayInOutputFormat')
			->willReturnCallback(function (InputInterface $input, OutputInterface $output, iterable $items): void {
				$this->writtenTasks = is_array($items) ? $items : iterator_to_array($items);
			});

		$input = new ArrayInput($arguments, $this->command->getDefinition());
		return $this->command->run($input, $this->output);
	}

	public function testLimitOptionDefaultsToTen(): void {
		$option = $this->command->getDefinition()->getOption('limit');

		self::assertSame('10', $option->getDefault());
		self::assertSame('l', $option->getShortcut());
	}

	public function testDefaultLimitIsPassedToManager(): void {
		$tasks = $this->createTasks(10);

		$this->manager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, 10)
			->willReturn($tasks);

		$exitCode = $this->runCommand();

		self::assertSame(0, $exitCode);
		self::assertNotNull($this->writtenTasks);
		self::assertCount(10, $this->writtenTasks);
	}

	public function testCustomLimitIsPassedToManager(): void {
		$tasks = $this->createTasks(5);

		$this->manager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, 5)
			->willReturn($tasks);

		$exitCode = $this->runCommand(['--limit' => '5']);

		self::assertSame(0, $exitCode);
		self::assertNotNull($this->writtenTasks);
		self::assertCount(5, $this->writtenTasks);
	}

	public function testFewerTasksThanLimitAreAllReturned(): void {
		$tasks = $this->createTasks(3);

		$this->manager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, 10)
			->willReturn($tasks);

		$exitCode = $this->runCommand();

		self::assertSame(0, $exitCode);
		self::assertNotNull($this->writtenTasks);
		self::assertCount(3, $this->writtenTasks);
	}

	public function testLimitAppliesToFilteredResults(): void {
		$tasks = $this->createTasks(2);

		$this->manager->expects($this->once())
			->method('getTasks')
			->with('alice', 'core:TextToText', 'testapp', 'custom1', 3, 100, 200, 2)
			->willReturn($tasks);

		$exitCode = $this->runCommand([
			'--userIdFilter' => 'alice',
			'--type' => 'core:TextToText',
			'--appId' => 'testapp',
			'--customId' => 'custom1',
			'--status' => '3',
			'--scheduledAfter' => '100',
			'--endedBefore' => '200',
			'--limit' => '2',
		]);

		self::assertSame(0, $exitCode);
		self::assertNotNull($this->writtenTasks);
		self::assertCount(2, $this->writtenTasks);
	}

	public function testZeroLimitMeansUnlimited(): void {
		$tasks = $this->createTasks(15);

		$this->manager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, null)
			->willReturn($tasks);

		$exitCode = $this->runCommand(['--limit' => '0']);

		self::assertSame(0, $exitCode);
		self::assertNotNull($this->writtenTasks);
		self::assertCount(15, $this->writtenTasks);
	}

	public function testNegativeLimitFailsWithoutQuerying(): void {
		$this->manager->expects($this->never())
			->method('getTasks');

		$exitCode = $this->runCommand(['--limit' => '-1']);

		self::assertSame(1, $exitCode);
		self::assertNull($this->writtenTasks);
		self::assertStringContainsString('The limit must not be negative', $this->output->fetch());
	}

	public function testNonNumericLimitIsCastToUnlimited(): void {
		$this->manager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, null)
			->willReturn($this->createTasks(2));

		$exitCode = $this->runCommand(['--limit' => 'abc']);

		self::assertSame(0, $exitCode);
		self::assertNotNull($this->writtenTasks);
		self::assertCount(2, $this->writtenTasks);
	}
}
