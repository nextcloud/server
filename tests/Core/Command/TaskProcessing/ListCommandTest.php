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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ListCommandTest extends TestCase {
	private IManager|MockObject $taskProcessingManager;
	private ListCommand|MockObject $command;
	private InputInterface|MockObject $input;
	private OutputInterface|MockObject $output;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->taskProcessingManager = $this->createMock(IManager::class);
		$this->command = $this->getMockBuilder(ListCommand::class)
			->setConstructorArgs([$this->taskProcessingManager])
			->onlyMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	/**
	 * @param list<Task> $tasks
	 * @return list<array<string, mixed>>
	 */
	private function serializedTasks(array $tasks): array {
		return array_map(static function (Task $task) {
			$jsonTask = $task->jsonSerialize();
			$jsonTask['error_message'] = $task->getErrorMessage();
			return $jsonTask;
		}, $tasks);
	}

	public function testExecuteUsesDefaultLimitOfTen(): void {
		$tasks = [new Task('type1', [], 'app', 'user')];

		$this->taskProcessingManager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, 10)
			->willReturn($tasks);

		$this->input->method('getOption')
			->willReturnCallback(static fn (string $option) => $option === 'limit' ? '10' : null);

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with($this->input, $this->output, $this->serializedTasks($tasks));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testExecutePassesExplicitLimit(): void {
		$tasks = [new Task('type1', [], 'app', 'user'), new Task('type2', [], 'app', 'user')];

		$this->taskProcessingManager->expects($this->once())
			->method('getTasks')
			->with('', null, null, null, null, null, null, 25)
			->willReturn($tasks);

		$this->input->method('getOption')
			->willReturnCallback(static fn (string $option) => $option === 'limit' ? '25' : null);

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with($this->input, $this->output, $this->serializedTasks($tasks));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
