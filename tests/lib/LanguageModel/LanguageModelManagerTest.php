<?php
/**
 * Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\LanguageModel;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\LanguageModel\Db\TaskMapper;
use OC\LanguageModel\LanguageModelManager;
use OC\LanguageModel\TaskBackgroundJob;
use OCP\BackgroundJob\IJobList;
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

		$this->languageModelManager = new LanguageModelManager(
			\OC::$server->get(IServerContainer::class),
			$this->coordinator = \OC::$server->get(Coordinator::class),
			\OC::$server->get(LoggerInterface::class),
			\OC::$server->get(IJobList::class),
			\OC::$server->get(TaskMapper::class),
		);
	}

	public function testShouldNotHaveAnyProviders() {
		$this->assertCount(0, $this->languageModelManager->getAvailableTasks());
		$this->assertCount(0, $this->languageModelManager->getAvailableTaskTypes());
		$this->assertFalse($this->languageModelManager->hasProviders());
		$this->expectException(PreConditionNotMetException::class);
		$this->languageModelManager->runTask(new FreePromptTask('Hello', 'test', null));
	}

	public function testProviderShouldBeRegisteredAndRun() {
		$this->coordinator->getRegistrationContext()->registerLanguageModelProvider('test', TestVanillaLanguageModelProvider::class);
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
		$this->coordinator->getRegistrationContext()->registerLanguageModelProvider('test', TestVanillaLanguageModelProvider::class);
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

		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = \OC::$server->get(IEventDispatcher::class);
		$successfulEventFired = false;
		$eventDispatcher->addListener(TaskSuccessfulEvent::class, function (TaskSuccessfulEvent $event) use (&$successfulEventFired, $task) {
			$successfulEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_SUCCESSFUL, $t->getStatus());
			$this->assertEquals('Hello Free Prompt', $t->getOutput());
		});
		$failedEventFired = false;
		$eventDispatcher->addListener(TaskFailedEvent::class, function (TaskFailedEvent $event) use (&$failedEventFired, $task) {
			$failedEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_FAILED, $t->getStatus());
			$this->assertEquals('ERROR', $event->getErrorMessage());
		});

		// run background job
		/** @var TaskBackgroundJob $bgJob */
		$bgJob = \OC::$server->get(TaskBackgroundJob::class);
		$bgJob->setArgument(['taskId' => $task->getId()]);
		$bgJob->start(new DummyJobList());
		$provider = \OC::$server->get(TestVanillaLanguageModelProvider::class);
		$this->assertTrue($provider->ran);
		$this->assertTrue($successfulEventFired);
		$this->assertFalse($failedEventFired);

		// Task object retrieved from db is up-to-date
		$task3 = $this->languageModelManager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task3->getId());
		$this->assertEquals('Hello', $task3->getInput());
		$this->assertEquals('Hello Free Prompt', $task3->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_SUCCESSFUL, $task2->getStatus());
	}

	public function testMultipleProvidersShouldBeRegisteredAndRunCorrectly() {
		$this->coordinator->getRegistrationContext()->registerLanguageModelProvider('test', TestVanillaLanguageModelProvider::class);
		$this->coordinator->getRegistrationContext()->registerLanguageModelProvider('test', TestFullLanguageModelProvider::class);
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
		$this->coordinator->getRegistrationContext()->registerLanguageModelProvider('test', TestFailingLanguageModelProvider::class);
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

		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = \OC::$server->get(IEventDispatcher::class);
		$successfulEventFired = false;
		$eventDispatcher->addListener(TaskSuccessfulEvent::class, function (TaskSuccessfulEvent $event) use (&$successfulEventFired, $task) {
			$successfulEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_SUCCESSFUL, $t->getStatus());
			$this->assertEquals('Hello Free Prompt', $t->getOutput());
		});
		$failedEventFired = false;
		$eventDispatcher->addListener(TaskFailedEvent::class, function (TaskFailedEvent $event) use (&$failedEventFired, $task) {
			$failedEventFired = true;
			$t = $event->getTask();
			$this->assertEquals($task->getId(), $t->getId());
			$this->assertEquals(ILanguageModelTask::STATUS_FAILED, $t->getStatus());
			$this->assertEquals('ERROR', $event->getErrorMessage());
		});

		// run background job
		/** @var TaskBackgroundJob $bgJob */
		$bgJob = \OC::$server->get(TaskBackgroundJob::class);
		$bgJob->setArgument(['taskId' => $task->getId()]);
		$bgJob->start(new DummyJobList());
		$provider = \OC::$server->get(TestFailingLanguageModelProvider::class);
		$this->assertTrue($provider->ran);
		$this->assertTrue($failedEventFired);
		$this->assertFalse($successfulEventFired);

		// Task object retrieved from db is up-to-date
		$task3 = $this->languageModelManager->getTask($task->getId());
		$this->assertEquals($task->getId(), $task3->getId());
		$this->assertEquals('Hello', $task3->getInput());
		$this->assertNull($task3->getOutput());
		$this->assertEquals(ILanguageModelTask::STATUS_FAILED, $task2->getStatus());
	}
}
