<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\TextProcessing;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\EventDispatcher\EventDispatcher;
use OC\TextProcessing\Db\Task as DbTask;
use OC\TextProcessing\Db\TaskMapper;
use OC\TextProcessing\Manager;
use OC\TextProcessing\RemoveOldTasksBackgroundJob;
use OC\TextProcessing\TaskBackgroundJob;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Common\Exception\NotFoundException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\PreConditionNotMetException;
use OCP\Server;
use OCP\TextProcessing\Events\TaskFailedEvent;
use OCP\TextProcessing\Events\TaskSuccessfulEvent;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\IProvider;
use OCP\TextProcessing\SummaryTaskType;
use OCP\TextProcessing\Task;
use OCP\TextProcessing\TopicsTaskType;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Psr\Log\LoggerInterface;
use Test\BackgroundJob\DummyJobList;

class SuccessfulSummaryProvider implements IProvider {
	public bool $ran = false;

	public function getName(): string {
		return 'TEST Vanilla LLM Provider';
	}

	public function process(string $prompt): string {
		$this->ran = true;
		return $prompt . ' Summarize';
	}

	public function getTaskType(): string {
		return SummaryTaskType::class;
	}
}

class FailingSummaryProvider implements IProvider {
	public bool $ran = false;

	public function getName(): string {
		return 'TEST Vanilla LLM Provider';
	}

	public function process(string $prompt): string {
		$this->ran = true;
		throw new \Exception('ERROR');
	}

	public function getTaskType(): string {
		return SummaryTaskType::class;
	}
}

class FreePromptProvider implements IProvider {
	public bool $ran = false;

	public function getName(): string {
		return 'TEST Free Prompt Provider';
	}

	public function process(string $prompt): string {
		$this->ran = true;
		return $prompt . ' Free Prompt';
	}

	public function getTaskType(): string {
		return FreePromptTaskType::class;
	}
}

/**
 * @group DB
 */
class TextProcessingTest extends \Test\TestCase {
	private IManager $manager;
	private Coordinator $coordinator;
	private array $providers;
	private IServerContainer $serverContainer;
	private IEventDispatcher $eventDispatcher;
	private RegistrationContext $registrationContext;
	private \DateTimeImmutable $currentTime;
	private TaskMapper $taskMapper;
	private array $tasksDb;
	private IJobList $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->providers = [
			SuccessfulSummaryProvider::class => new SuccessfulSummaryProvider(),
			FailingSummaryProvider::class => new FailingSummaryProvider(),
			FreePromptProvider::class => new FreePromptProvider(),
		];

		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->serverContainer->expects($this->any())->method('get')->willReturnCallback(function ($class) {
			return $this->providers[$class];
		});

		$this->eventDispatcher = new EventDispatcher(
			new \Symfony\Component\EventDispatcher\EventDispatcher(),
			$this->serverContainer,
			Server::get(LoggerInterface::class),
		);

		$this->registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator = $this->createMock(Coordinator::class);
		$this->coordinator->expects($this->any())->method('getRegistrationContext')->willReturn($this->registrationContext);

		$this->currentTime = new \DateTimeImmutable('now');

		$this->taskMapper = $this->createMock(TaskMapper::class);
		$this->tasksDb = [];
		$this->taskMapper
			->expects($this->any())
			->method('insert')
			->willReturnCallback(function (DbTask $task) {
				$task->setId(count($this->tasksDb) ? max(array_keys($this->tasksDb)) : 1);
				$task->setLastUpdated($this->currentTime->getTimestamp());
				$this->tasksDb[$task->getId()] = $task->toRow();
				return $task;
			});
		$this->taskMapper
			->expects($this->any())
			->method('update')
			->willReturnCallback(function (DbTask $task) {
				$task->setLastUpdated($this->currentTime->getTimestamp());
				$this->tasksDb[$task->getId()] = $task->toRow();
				return $task;
			});
		$this->taskMapper
			->expects($this->any())
			->method('find')
			->willReturnCallback(function (int $id) {
				if (!isset($this->tasksDb[$id])) {
					throw new DoesNotExistException('Could not find it');
				}
				return DbTask::fromRow($this->tasksDb[$id]);
			});
		$this->taskMapper
			->expects($this->any())
			->method('deleteOlderThan')
			->willReturnCallback(function (int $timeout): void {
				$this->tasksDb = array_filter($this->tasksDb, function (array $task) use ($timeout) {
					return $task['last_updated'] >= $this->currentTime->getTimestamp() - $timeout;
				});
			});

		$this->jobList = $this->createPartialMock(DummyJobList::class, ['add']);
		$this->jobList->expects($this->any())->method('add')->willReturnCallback(function (): void {
		});

		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')
			->with('core', 'ai.textprocessing_provider_preferences', '')
			->willReturn('');

		$this->manager = new Manager(
			$this->serverContainer,
			$this->coordinator,
			Server::get(LoggerInterface::class),
			$this->jobList,
			$this->taskMapper,
			$config,
			$this->createMock(\OCP\TaskProcessing\IManager::class),
		);
	}

	public function testShouldNotHaveAnyProviders(): void {
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([]);
		$this->assertCount(0, $this->manager->getAvailableTaskTypes());
		$this->assertFalse($this->manager->hasProviders());
		$this->expectException(PreConditionNotMetException::class);
		$this->manager->runTask(new \OCP\TextProcessing\Task(FreePromptTaskType::class, 'Hello', 'test', null));
	}

	public function testProviderShouldBeRegisteredAndRun(): void {
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSummaryProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());
		$this->assertEquals('Hello Summarize', $this->manager->runTask(new Task(SummaryTaskType::class, 'Hello', 'test', null)));

		// Summaries are not implemented by the vanilla provider, only free prompt
		$this->expectException(PreConditionNotMetException::class);
		$this->manager->runTask(new Task(FreePromptTaskType::class, 'Hello', 'test', null));
	}

	public function testProviderShouldBeRegisteredAndScheduled(): void {
		// register provider
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSummaryProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());

		// create task object
		$task = new Task(SummaryTaskType::class, 'Hello', 'test', null);
		$this->assertNull($task->getId());
		$this->assertNull($task->getOutput());

		// schedule works
		$this->assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);

		// Task object is up-to-date
		$this->assertNotNull($task->getId());
		$this->assertNull($task->getOutput());
		$this->assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		// Task object retrieved from db is up-to-date
		$task2 = $this->manager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task2->getId());
		$this->assertEquals('Hello', $task2->getInput());
		$this->assertNull($task2->getOutput());
		$this->assertEquals(Task::STATUS_SCHEDULED, $task2->getStatus());

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		// run background job
		$bgJob = new TaskBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->eventDispatcher,
		);
		$bgJob->setArgument(['taskId' => $task->getId()]);
		$bgJob->start($this->jobList);
		$provider = $this->providers[SuccessfulSummaryProvider::class];
		$this->assertTrue($provider->ran);

		// Task object retrieved from db is up-to-date
		$task3 = $this->manager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task3->getId());
		$this->assertEquals('Hello', $task3->getInput());
		$this->assertEquals('Hello Summarize', $task3->getOutput());
		$this->assertEquals(Task::STATUS_SUCCESSFUL, $task3->getStatus());
	}

	public function testMultipleProvidersShouldBeRegisteredAndRunCorrectly(): void {
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSummaryProvider::class),
			new ServiceRegistration('test', FreePromptProvider::class),
		]);
		$this->assertCount(2, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());

		// Try free prompt again
		$this->assertEquals('Hello Free Prompt', $this->manager->runTask(new Task(FreePromptTaskType::class, 'Hello', 'test', null)));

		// Try summary task
		$this->assertEquals('Hello Summarize', $this->manager->runTask(new Task(SummaryTaskType::class, 'Hello', 'test', null)));

		// Topics are not implemented by both the vanilla provider and the full provider
		$this->expectException(PreConditionNotMetException::class);
		$this->manager->runTask(new Task(TopicsTaskType::class, 'Hello', 'test', null));
	}

	public function testNonexistentTask(): void {
		$this->expectException(NotFoundException::class);
		$this->manager->getTask(2147483646);
	}

	public function testTaskFailure(): void {
		// register provider
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', FailingSummaryProvider::class),
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());

		// create task object
		$task = new Task(SummaryTaskType::class, 'Hello', 'test', null);
		$this->assertNull($task->getId());
		$this->assertNull($task->getOutput());

		// schedule works
		$this->assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);

		// Task object is up-to-date
		$this->assertNotNull($task->getId());
		$this->assertNull($task->getOutput());
		$this->assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		// Task object retrieved from db is up-to-date
		$task2 = $this->manager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task2->getId());
		$this->assertEquals('Hello', $task2->getInput());
		$this->assertNull($task2->getOutput());
		$this->assertEquals(Task::STATUS_SCHEDULED, $task2->getStatus());

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskFailedEvent::class));

		// run background job
		$bgJob = new TaskBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->eventDispatcher,
		);
		$bgJob->setArgument(['taskId' => $task->getId()]);
		$bgJob->start($this->jobList);
		$provider = $this->providers[FailingSummaryProvider::class];
		$this->assertTrue($provider->ran);

		// Task object retrieved from db is up-to-date
		$task3 = $this->manager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task3->getId());
		$this->assertEquals('Hello', $task3->getInput());
		$this->assertNull($task3->getOutput());
		$this->assertEquals(Task::STATUS_FAILED, $task3->getStatus());
	}

	public function testOldTasksShouldBeCleanedUp(): void {
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSummaryProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());
		$task = new Task(SummaryTaskType::class, 'Hello', 'test', null);
		$this->assertEquals('Hello Summarize', $this->manager->runTask($task));

		$this->currentTime = $this->currentTime->add(new \DateInterval('P1Y'));
		// run background job
		$bgJob = new RemoveOldTasksBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->taskMapper,
			Server::get(LoggerInterface::class),
		);
		$bgJob->setArgument([]);
		$bgJob->start($this->jobList);

		$this->expectException(NotFoundException::class);
		$this->manager->getTask($task->getId());
	}
}
