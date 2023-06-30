<?php
/**
 * Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\LanguageModel;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\EventDispatcher\EventDispatcher;
use OC\LanguageModel\Db\Task;
use OC\LanguageModel\Db\TaskMapper;
use OC\LanguageModel\LanguageModelManager;
use OC\LanguageModel\RemoveOldTasksBackgroundJob;
use OC\LanguageModel\TaskBackgroundJob;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Common\Exception\NotFoundException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IServerContainer;
use OCP\LanguageModel\Events\TaskFailedEvent;
use OCP\LanguageModel\Events\TaskSuccessfulEvent;
use OCP\LanguageModel\FreePromptTask;
use OCP\LanguageModel\HeadlineTask;
use OCP\LanguageModel\IHeadlineProvider;
use OCP\LanguageModel\ILanguageModelManager;
use OCP\LanguageModel\ILanguageModelProvider;
use OCP\LanguageModel\ILanguageModelTask;
use OCP\LanguageModel\ISummaryProvider;
use OCP\LanguageModel\SummaryTask;
use OCP\LanguageModel\TopicsTask;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use Test\BackgroundJob\DummyJobList;

class TestVanillaLanguageModelProvider implements ILanguageModelProvider {
	public bool $ran = false;

	public function getName(): string {
		return 'TEST Vanilla LLM Provider';
	}

	public function prompt(string $prompt): string {
		$this->ran = true;
		return $prompt . ' Free Prompt';
	}
}

class TestFailingLanguageModelProvider implements ILanguageModelProvider {
	public bool $ran = false;

	public function getName(): string {
		return 'TEST Vanilla LLM Provider';
	}

	public function prompt(string $prompt): string {
		$this->ran = true;
		throw new \Exception('ERROR');
	}
}

class TestFullLanguageModelProvider implements ILanguageModelProvider, ISummaryProvider, IHeadlineProvider {
	public function getName(): string {
		return 'TEST Full LLM Provider';
	}

	public function prompt(string $prompt): string {
		return $prompt . ' Free Prompt';
	}

	public function findHeadline(string $text): string {
		return $text . ' Headline';
	}

	public function summarize(string $text): string {
		return $text. ' Summarize';
	}
}

class LanguageModelManagerTest extends \Test\TestCase {
	private ILanguageModelManager $languageModelManager;
	private Coordinator $coordinator;

	protected function setUp(): void {
		parent::setUp();

		$this->providers = [
			TestVanillaLanguageModelProvider::class => new TestVanillaLanguageModelProvider(),
			TestFullLanguageModelProvider::class => new TestFullLanguageModelProvider(),
			TestFailingLanguageModelProvider::class => new TestFailingLanguageModelProvider(),
		];

		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->serverContainer->expects($this->any())->method('get')->willReturnCallback(function ($class) {
			return $this->providers[$class];
		});

		$this->eventDispatcher = new EventDispatcher(
			new \Symfony\Component\EventDispatcher\EventDispatcher(),
			$this->serverContainer,
			\OC::$server->get(LoggerInterface::class),
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
			->willReturnCallback(function (Task $task) {
				$task->setId(count($this->tasksDb) ? max(array_keys($this->tasksDb)) : 1);
				$task->setLastUpdated($this->currentTime->getTimestamp());
				$this->tasksDb[$task->getId()] = $task->toRow();
				return $task;
			});
		$this->taskMapper
			->expects($this->any())
			->method('update')
			->willReturnCallback(function (Task $task) {
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
				return Task::fromRow($this->tasksDb[$id]);
			});
		$this->taskMapper
			->expects($this->any())
			->method('deleteOlderThan')
			->willReturnCallback(function (int $timeout) {
				$this->tasksDb = array_filter($this->tasksDb, function (array $task) use ($timeout) {
					return $task['last_updated'] >= $this->currentTime->getTimestamp() - $timeout;
				});
			});

		$this->jobList = $this->createPartialMock(DummyJobList::class, ['add']);
		$this->jobList->expects($this->any())->method('add')->willReturnCallback(function () {
		});

		$this->languageModelManager = new LanguageModelManager(
			$this->serverContainer,
			$this->coordinator,
			\OC::$server->get(LoggerInterface::class),
			$this->jobList,
			$this->taskMapper,
		);
	}

	public function testShouldNotHaveAnyProviders() {
		$this->registrationContext->expects($this->any())->method('getLanguageModelProviders')->willReturn([]);
		$this->assertCount(0, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(0, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertFalse($this->languageModelManager->hasProviders());
		$this->expectException(PreConditionNotMetException::class);
		$this->languageModelManager->runTask(new FreePromptTask('Hello', 'test', null));
	}

	public function testProviderShouldBeRegisteredAndRun() {
		$this->registrationContext->expects($this->any())->method('getLanguageModelProviders')->willReturn([
			new ServiceRegistration('test', TestVanillaLanguageModelProvider::class)
		]);
		$this->assertCount(1, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(1, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertTrue($this->languageModelManager->hasProviders());
		$this->assertEquals('Hello Free Prompt', $this->languageModelManager->runTask(new FreePromptTask('Hello', 'test', null)));

		// Summaries are not implemented by the vanilla provider, only free prompt
		$this->expectException(PreConditionNotMetException::class);
		$this->languageModelManager->runTask(new SummaryTask('Hello', 'test', null));
	}

	public function testProviderShouldBeRegisteredAndScheduled() {
		// register provider
		$this->registrationContext->expects($this->any())->method('getLanguageModelProviders')->willReturn([
			new ServiceRegistration('test', TestVanillaLanguageModelProvider::class)
		]);
		$this->assertCount(1, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(1, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertTrue($this->languageModelManager->hasProviders());

		// create task object
		$task = new FreePromptTask('Hello', 'test', null);
		$this->assertNull($task->getId());
		$this->assertNull($task->getOutput());

		// schedule works
		$this->assertEquals(ILanguageModelTask::STATUS_UNKNOWN, $task->getStatus());
		$this->languageModelManager->scheduleTask($task);

		// Task object is up-to-date
		$this->assertNotNull($task->getId());
		$this->assertNull($task->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_SCHEDULED, $task->getStatus());

		// Task object retrieved from db is up-to-date
		$task2 = $this->languageModelManager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task2->getId());
		$this->assertEquals('Hello', $task2->getInput());
		$this->assertNull($task2->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_SCHEDULED, $task2->getStatus());

		/** @var IEventDispatcher $this->eventDispatcher */
		$this->eventDispatcher = \OC::$server->get(IEventDispatcher::class);
		$successfulEventFired = false;
		$this->eventDispatcher->addListener(TaskSuccessfulEvent::class, function (TaskSuccessfulEvent $event) use (&$successfulEventFired, $task) {
			$successfulEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_SUCCESSFUL, $t->getStatus());
			$this->assertEquals('Hello Free Prompt', $t->getOutput());
		});
		$failedEventFired = false;
		$this->eventDispatcher->addListener(TaskFailedEvent::class, function (TaskFailedEvent $event) use (&$failedEventFired, $task) {
			$failedEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_FAILED, $t->getStatus());
			$this->assertEquals('ERROR', $event->getErrorMessage());
		});

		// run background job
		$bgJob = new TaskBackgroundJob(
			\OC::$server->get(ITimeFactory::class),
			$this->languageModelManager,
			$this->eventDispatcher,
		);
		$bgJob->setArgument(['taskId' => $task->getId()]);
		$bgJob->start($this->jobList);
		$provider = $this->providers[TestVanillaLanguageModelProvider::class];
		$this->assertTrue($provider->ran);
		$this->assertTrue($successfulEventFired);
		$this->assertFalse($failedEventFired);

		// Task object retrieved from db is up-to-date
		$task3 = $this->languageModelManager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task3->getId());
		$this->assertEquals('Hello', $task3->getInput());
		$this->assertEquals('Hello Free Prompt', $task3->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_SUCCESSFUL, $task3->getStatus());
	}

	public function testMultipleProvidersShouldBeRegisteredAndRunCorrectly() {
		$this->registrationContext->expects($this->any())->method('getLanguageModelProviders')->willReturn([
			new ServiceRegistration('test', TestVanillaLanguageModelProvider::class),
			new ServiceRegistration('test', TestFullLanguageModelProvider::class),
		]);
		$this->assertCount(3, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(3, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertTrue($this->languageModelManager->hasProviders());

		// Try free prompt again
		$this->assertEquals('Hello Free Prompt', $this->languageModelManager->runTask(new FreePromptTask('Hello', 'test', null)));

		// Try headline task
		$this->assertEquals('Hello Headline', $this->languageModelManager->runTask(new HeadlineTask('Hello', 'test', null)));

		// Try summary task
		$this->assertEquals('Hello Summarize', $this->languageModelManager->runTask(new SummaryTask('Hello', 'test', null)));

		// Topics are not implemented by both the vanilla provider and the full provider
		$this->expectException(PreConditionNotMetException::class);
		$this->languageModelManager->runTask(new TopicsTask('Hello', 'test', null));
	}

	public function testNonexistentTask() {
		$this->expectException(NotFoundException::class);
		$this->languageModelManager->getTask(98765432456);
	}

	public function testTaskFailure() {
		// register provider
		$this->registrationContext->expects($this->any())->method('getLanguageModelProviders')->willReturn([
			new ServiceRegistration('test', TestFailingLanguageModelProvider::class),
		]);
		$this->assertCount(1, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(1, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertTrue($this->languageModelManager->hasProviders());

		// create task object
		$task = new FreePromptTask('Hello', 'test', null);
		$this->assertNull($task->getId());
		$this->assertNull($task->getOutput());

		// schedule works
		$this->assertEquals(ILanguageModelTask::STATUS_UNKNOWN, $task->getStatus());
		$this->languageModelManager->scheduleTask($task);

		// Task object is up-to-date
		$this->assertNotNull($task->getId());
		$this->assertNull($task->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_SCHEDULED, $task->getStatus());

		// Task object retrieved from db is up-to-date
		$task2 = $this->languageModelManager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task2->getId());
		$this->assertEquals('Hello', $task2->getInput());
		$this->assertNull($task2->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_SCHEDULED, $task2->getStatus());

		$successfulEventFired = false;
		$this->eventDispatcher->addListener(TaskSuccessfulEvent::class, function (TaskSuccessfulEvent $event) use (&$successfulEventFired, $task) {
			$successfulEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_SUCCESSFUL, $t->getStatus());
			$this->assertEquals('Hello Free Prompt', $t->getOutput());
		});
		$failedEventFired = false;
		$this->eventDispatcher->addListener(TaskFailedEvent::class, function (TaskFailedEvent $event) use (&$failedEventFired, $task) {
			$failedEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_FAILED, $t->getStatus());
			$this->assertEquals('ERROR', $event->getErrorMessage());
		});

		// run background job
		$bgJob = new TaskBackgroundJob(
			\OC::$server->get(ITimeFactory::class),
			$this->languageModelManager,
			$this->eventDispatcher,
		);
		$bgJob->setArgument(['taskId' => $task->getId()]);
		$bgJob->start($this->jobList);
		$provider = $this->providers[TestFailingLanguageModelProvider::class];
		$this->assertTrue($provider->ran);
		$this->assertTrue($failedEventFired);
		$this->assertFalse($successfulEventFired);

		// Task object retrieved from db is up-to-date
		$task3 = $this->languageModelManager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task3->getId());
		$this->assertEquals('Hello', $task3->getInput());
		$this->assertNull($task3->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_FAILED, $task3->getStatus());
	}

	public function testOldTasksShouldBeCleanedUp() {
		$this->registrationContext->expects($this->any())->method('getLanguageModelProviders')->willReturn([
			new ServiceRegistration('test', TestVanillaLanguageModelProvider::class)
		]);
		$this->assertCount(1, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(1, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertTrue($this->languageModelManager->hasProviders());
		$task = new FreePromptTask('Hello', 'test', null);
		$this->assertEquals('Hello Free Prompt', $this->languageModelManager->runTask($task));

		$this->currentTime = $this->currentTime->add(new \DateInterval('P1Y'));
		// run background job
		$bgJob = new RemoveOldTasksBackgroundJob(
			\OC::$server->get(ITimeFactory::class),
			$this->taskMapper,
			\OC::$server->get(LoggerInterface::class),
		);
		$bgJob->setArgument([]);
		$bgJob->start($this->jobList);

		$this->expectException(NotFoundException::class);
		$this->languageModelManager->getTask($task->getId());
	}
}
