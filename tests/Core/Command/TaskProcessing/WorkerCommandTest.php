<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\TaskProcessing;

use OC\Core\Command\TaskProcessing\WorkerCommand;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Test\TestCase;

class WorkerCommandTest extends TestCase {
	private IManager&MockObject $manager;
	private LoggerInterface&MockObject $logger;
	private WorkerCommand $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(IManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->command = new WorkerCommand($this->manager, $this->logger);
	}

	/**
	 * Helper to create a minimal ISynchronousProvider mock.
	 */
	private function createProvider(string $id, string $taskTypeId): ISynchronousProvider&MockObject {
		$provider = $this->createMock(ISynchronousProvider::class);
		$provider->method('getId')->willReturn($id);
		$provider->method('getTaskTypeId')->willReturn($taskTypeId);
		return $provider;
	}

	/**
	 * Helper to create a real Task with a given id and optional task type.
	 */
	private function createTask(int $id, string $type = 'test_task_type'): Task {
		$task = new Task($type, [], 'testapp', null);
		$task->setId($id);
		return $task;
	}

	public function testOnceExitsAfterNoTask(): void {
		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([]);

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testOnceProcessesOneTask(): void {
		$taskTypeId = 'test_task_type';
		$provider = $this->createProvider('test_provider', $taskTypeId);
		$task = $this->createTask(42);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);

		$this->manager->expects($this->once())
			->method('getPreferredProvider')
			->with($taskTypeId)
			->willReturn($provider);

		$this->manager->expects($this->once())
			->method('getNextScheduledTask')
			->with([$taskTypeId])
			->willReturn($task);

		$this->manager->expects($this->once())
			->method('processTask')
			->with($task, $provider)
			->willReturn(true);

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testSkipsNonSynchronousProviders(): void {
		// A provider that is NOT an ISynchronousProvider
		$nonSyncProvider = $this->createMock(\OCP\TaskProcessing\IProvider::class);
		$nonSyncProvider->method('getId')->willReturn('non_sync_provider');
		$nonSyncProvider->method('getTaskTypeId')->willReturn('some_type');

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$nonSyncProvider]);

		$this->manager->expects($this->never())
			->method('getPreferredProvider');

		$this->manager->expects($this->never())
			->method('getNextScheduledTask');

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testSkipsNonPreferredProviders(): void {
		$taskTypeId = 'test_task_type';
		$provider = $this->createProvider('provider_a', $taskTypeId);
		$preferredProvider = $this->createProvider('provider_b', $taskTypeId);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);

		$this->manager->expects($this->once())
			->method('getPreferredProvider')
			->with($taskTypeId)
			->willReturn($preferredProvider);

		// provider_a is not preferred (provider_b is), so getNextScheduledTask is never called
		$this->manager->expects($this->never())
			->method('getNextScheduledTask');

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testContinuesWhenNoTaskFound(): void {
		$taskTypeId = 'test_task_type';
		$provider = $this->createProvider('test_provider', $taskTypeId);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);

		$this->manager->expects($this->once())
			->method('getPreferredProvider')
			->with($taskTypeId)
			->willReturn($provider);

		$this->manager->expects($this->once())
			->method('getNextScheduledTask')
			->with([$taskTypeId])
			->willThrowException(new NotFoundException());

		$this->manager->expects($this->never())
			->method('processTask');

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testLogsErrorAndContinuesOnException(): void {
		$taskTypeId = 'test_task_type';
		$provider = $this->createProvider('test_provider', $taskTypeId);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);

		$this->manager->expects($this->once())
			->method('getPreferredProvider')
			->with($taskTypeId)
			->willReturn($provider);

		$exception = new Exception('DB error');
		$this->manager->expects($this->once())
			->method('getNextScheduledTask')
			->with([$taskTypeId])
			->willThrowException($exception);

		$this->logger->expects($this->once())
			->method('error')
			->with('Unknown error while retrieving scheduled TaskProcessing tasks', ['exception' => $exception]);

		$this->manager->expects($this->never())
			->method('processTask');

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testTimeoutExitsLoop(): void {
		// Arrange: no providers so each iteration does nothing, but timeout=1 should exit quickly
		$this->manager->method('getProviders')->willReturn([]);

		$input = new ArrayInput(['--timeout' => '1', '--interval' => '0'], $this->command->getDefinition());
		$output = new NullOutput();

		$start = time();
		$result = $this->command->run($input, $output);
		$elapsed = time() - $start;

		$this->assertSame(0, $result);
		// Should have exited within a few seconds
		$this->assertLessThanOrEqual(5, $elapsed);
	}

	public function testProcessesCorrectProviderForReturnedTaskType(): void {
		$taskTypeId1 = 'type_a';
		$taskTypeId2 = 'type_b';

		$provider1 = $this->createProvider('provider_a', $taskTypeId1);
		$provider2 = $this->createProvider('provider_b', $taskTypeId2);
		// Task has type_a, so provider1 must be chosen to process it
		$task = $this->createTask(7, $taskTypeId1);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider1, $provider2]);

		// Both providers are eligible, so getPreferredProvider is called for each
		$this->manager->expects($this->exactly(2))
			->method('getPreferredProvider')
			->willReturnMap([
				[$taskTypeId1, $provider1],
				[$taskTypeId2, $provider2],
			]);

		// All eligible task types are passed in a single query
		$this->manager->expects($this->once())
			->method('getNextScheduledTask')
			->with($this->equalTo([$taskTypeId1, $taskTypeId2]))
			->willReturn($task);

		$this->manager->expects($this->once())
			->method('processTask')
			->with($task, $provider1)
			->willReturn(true);

		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testTaskTypesWhitelistFiltersProviders(): void {
		$taskTypeId1 = 'type_a';
		$taskTypeId2 = 'type_b';

		$provider1 = $this->createProvider('provider_a', $taskTypeId1);
		$provider2 = $this->createProvider('provider_b', $taskTypeId2);
		$task = $this->createTask(99, $taskTypeId2);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider1, $provider2]);

		// Only type_b is whitelisted, so provider_a (type_a) must be skipped entirely
		$this->manager->expects($this->once())
			->method('getPreferredProvider')
			->with($taskTypeId2)
			->willReturn($provider2);

		$this->manager->expects($this->once())
			->method('getNextScheduledTask')
			->with([$taskTypeId2])
			->willReturn($task);

		$this->manager->expects($this->once())
			->method('processTask')
			->with($task, $provider2)
			->willReturn(true);

		$input = new ArrayInput(['--once' => true, '--taskTypes' => [$taskTypeId2]], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testTaskTypesWhitelistWithNoMatchingProviders(): void {
		$provider = $this->createProvider('provider_a', 'type_a');

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);

		// Whitelist does not include type_a so nothing should be processed
		$this->manager->expects($this->never())
			->method('getPreferredProvider');

		$this->manager->expects($this->never())
			->method('getNextScheduledTask');

		$input = new ArrayInput(['--once' => true, '--taskTypes' => ['type_b']], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}

	public function testEmptyTaskTypesAllowsAllProviders(): void {
		$taskTypeId = 'type_a';
		$provider = $this->createProvider('provider_a', $taskTypeId);
		$task = $this->createTask(5, $taskTypeId);

		$this->manager->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);

		$this->manager->expects($this->once())
			->method('getPreferredProvider')
			->with($taskTypeId)
			->willReturn($provider);

		$this->manager->expects($this->once())
			->method('getNextScheduledTask')
			->with([$taskTypeId])
			->willReturn($task);

		$this->manager->expects($this->once())
			->method('processTask')
			->with($task, $provider)
			->willReturn(true);

		// No --taskTypes option provided
		$input = new ArrayInput(['--once' => true], $this->command->getDefinition());
		$output = new NullOutput();

		$result = $this->command->run($input, $output);

		$this->assertSame(0, $result);
	}
}
