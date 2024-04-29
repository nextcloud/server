<?php
/**
 * Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\TextProcessing;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\EventDispatcher\EventDispatcher;
use OC\TaskProcessing\Db\TaskMapper;
use OC\TaskProcessing\Db\Task as DbTask;
use OC\TaskProcessing\Manager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Events\TaskFailedEvent;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\Exception\ValidationException;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\IProvider;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\Task;
use OCP\TaskProcessing\TaskTypes\TextToText;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Psr\Log\LoggerInterface;
use Test\BackgroundJob\DummyJobList;

class AudioToImage implements ITaskType {
	const ID = 'test:audiotoimage';

	public function getId(): string {
		return self::ID;
	}

	public function getName(): string {
		return self::class;
	}

	public function getDescription(): string {
		return self::class;
	}

	public function getInputShape(): array {
		return [
			'audio' => new ShapeDescriptor('Audio', 'The audio', EShapeType::Audio),
		];
	}

	public function getOutputShape(): array {
		return [
			'spectrogram' => new ShapeDescriptor('Spectrogram', 'The audio spectrogram', EShapeType::Image),
		];
	}
}

class AsyncProvider implements IProvider {
	public function getId(): string {
		return 'test:sync:success';
	}

	public function getName(): string {
		return self::class;
	}

	public function getTaskType(): string {
		return AudioToImage::ID;
	}

	public function getExpectedRuntime(): int {
		return 10;
	}

	public function getOptionalInputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function getOptionalOutputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}
}

class SuccessfulSyncProvider implements IProvider, ISynchronousProvider {
	public function getId(): string {
		return 'test:sync:success';
	}

	public function getName(): string {
		return self::class;
	}

	public function getTaskType(): string {
		return TextToText::ID;
	}

	public function getExpectedRuntime(): int {
		return 10;
	}

	public function getOptionalInputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function getOptionalOutputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function process(?string $userId, array $input): array {
		return ['output' => $input['input']];
	}
}

class FailingSyncProvider implements IProvider, ISynchronousProvider {
	const ERROR_MESSAGE = 'Failure';
	public function getId(): string {
		return 'test:sync:fail';
	}

	public function getName(): string {
		return self::class;
	}

	public function getTaskType(): string {
		return TextToText::ID;
	}

	public function getExpectedRuntime(): int {
		return 10;
	}

	public function getOptionalInputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function getOptionalOutputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function process(?string $userId, array $input): array {
		throw new ProcessingException(self::ERROR_MESSAGE);
	}
}

class BrokenSyncProvider implements IProvider, ISynchronousProvider {
	public function getId(): string {
		return 'test:sync:broken-output';
	}

	public function getName(): string {
		return self::class;
	}

	public function getTaskType(): string {
		return TextToText::ID;
	}

	public function getExpectedRuntime(): int {
		return 10;
	}

	public function getOptionalInputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function getOptionalOutputShape(): array {
		return [
			'optionalKey' => new ShapeDescriptor('optional Key', 'AN optional key', EShapeType::Text),
		];
	}

	public function process(?string $userId, array $input): array {
		return [];
	}
}

/**
 * @group DB
 */
class TaskProcessingTest extends \Test\TestCase {
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
	private IAppData $appData;

	protected function setUp(): void {
		parent::setUp();

		$this->providers = [
			SuccessfulSyncProvider::class => new SuccessfulSyncProvider(),
			FailingSyncProvider::class => new FailingSyncProvider(),
			BrokenSyncProvider::class => new BrokenSyncProvider(),
			AsyncProvider::class => new AsyncProvider(),
			AudioToImage::class => new AudioToImage(),
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

		$this->taskMapper = \OCP\Server::get(TaskMapper::class);

		$this->jobList = $this->createPartialMock(DummyJobList::class, ['add']);
		$this->jobList->expects($this->any())->method('add')->willReturnCallback(function () {
		});

		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')
			->with('core', 'ai.textprocessing_provider_preferences', '')
			->willReturn('');

		$this->eventDispatcher =  $this->createMock(IEventDispatcher::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->serverContainer,
			\OC::$server->get(LoggerInterface::class),
			$this->taskMapper,
			$this->jobList,
			$this->eventDispatcher,
			\OC::$server->get(IAppDataFactory::class),
			\OC::$server->get(IRootFolder::class),
			\OC::$server->get(\OCP\TextProcessing\IManager::class),
			\OC::$server->get(\OCP\TextToImage\IManager::class),
			\OC::$server->get(ISpeechToTextManager::class),
		);
	}

	private function getFile(string $name, string $content): \OCP\Files\File {
		/** @var IRootFolder $rootFolder */
		$rootFolder = \OC::$server->get(IRootFolder::class);
		$this->appData = \OC::$server->get(IAppDataFactory::class)->get('core');
		try {
			$folder = $this->appData->getFolder('test');
		} catch (\OCP\Files\NotFoundException $e) {
			$folder = $this->appData->newFolder('test');
		}
		$file = $folder->newFile($name, $content);
		$inputFile = current($rootFolder->getByIdInPath($file->getId(), '/' . $rootFolder->getAppDataDirectoryName() . '/'));
		if (!$inputFile instanceof \OCP\Files\File) {
			throw new \Exception('PEBCAK');
		}
		return $inputFile;
	}

	public function testShouldNotHaveAnyProviders() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([]);
		self::assertCount(0, $this->manager->getAvailableTaskTypes());
		self::assertFalse($this->manager->hasProviders());
		self::expectException(PreConditionNotMetException::class);
		$this->manager->scheduleTask(new Task(TextToText::ID, ['input' => 'Hello'], 'test', null));
	}

	public function testProviderShouldBeRegisteredAndTaskFailValidation() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', BrokenSyncProvider::class)
		]);
		self::assertCount(1, $this->manager->getAvailableTaskTypes());
		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToText::ID, ['wrongInputKey' => 'Hello'], 'test', null);
		self::assertNull($task->getId());
		self::expectException(ValidationException::class);
		$this->manager->scheduleTask($task);
	}

	public function testProviderShouldBeRegisteredAndFail() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', FailingSyncProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());
		$task = new Task(TextToText::ID, ['input' => 'Hello'], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);
		self::assertNotNull($task->getId());
		self::assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskFailedEvent::class));

		$backgroundJob = new \OC\TaskProcessing\SynchronousBackgroundJob(
			\OCP\Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			\OCP\Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_FAILED, $task->getStatus());
		self::assertEquals(FailingSyncProvider::ERROR_MESSAGE, $task->getErrorMessage());
	}

	public function testProviderShouldBeRegisteredAndFailOutputValidation() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', BrokenSyncProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$this->assertTrue($this->manager->hasProviders());
		$task = new Task(TextToText::ID, ['input' => 'Hello'], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);
		self::assertNotNull($task->getId());
		self::assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskFailedEvent::class));

		$backgroundJob = new \OC\TaskProcessing\SynchronousBackgroundJob(
			\OCP\Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			\OCP\Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_FAILED, $task->getStatus());
		self::assertEquals('The task was processed successfully but the provider\'s output doesn\'t pass validation against the task type\'s outputShape spec and/or the provider\'s own optionalOutputShape spec', $task->getErrorMessage());
	}

	public function testProviderShouldBeRegisteredAndRun() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSyncProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());
		$taskTypeStruct = $this->manager->getAvailableTaskTypes()[array_keys($this->manager->getAvailableTaskTypes())[0]];
		$this->assertTrue(isset($taskTypeStruct['inputShape']['input']));
		$this->assertEquals(EShapeType::Text, $taskTypeStruct['inputShape']['input']->getShapeType());
		$this->assertTrue(isset($taskTypeStruct['optionalInputShape']['optionalKey']));
		$this->assertEquals(EShapeType::Text, $taskTypeStruct['optionalInputShape']['optionalKey']->getShapeType());
		$this->assertTrue(isset($taskTypeStruct['outputShape']['output']));
		$this->assertEquals(EShapeType::Text, $taskTypeStruct['outputShape']['output']->getShapeType());
		$this->assertTrue(isset($taskTypeStruct['optionalOutputShape']['optionalKey']));
		$this->assertEquals(EShapeType::Text, $taskTypeStruct['optionalOutputShape']['optionalKey']->getShapeType());

		$this->assertTrue($this->manager->hasProviders());
		$task = new Task(TextToText::ID, ['input' => 'Hello'], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);
		self::assertNotNull($task->getId());
		self::assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		// Task object retrieved from db is up-to-date
		$task2 = $this->manager->getTask($task->getId());
		self::assertEquals($task->getId(), $task2->getId());
		self::assertEquals(['input' => 'Hello'], $task2->getInput());
		self::assertNull($task2->getOutput());
		self::assertEquals(Task::STATUS_SCHEDULED, $task2->getStatus());

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		$backgroundJob = new \OC\TaskProcessing\SynchronousBackgroundJob(
			\OCP\Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			\OCP\Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus(), 'Status is '. $task->getStatus() . ' with error message: ' . $task->getErrorMessage());
		self::assertEquals(['output' => 'Hello'], $task->getOutput());
		self::assertEquals(1, $task->getProgress());
	}

	public function testAsyncProviderWithFilesShouldBeRegisteredAndRun() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([
			new ServiceRegistration('test', AudioToImage::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', AsyncProvider::class)
		]);
		$this->assertCount(1, $this->manager->getAvailableTaskTypes());

		$this->assertTrue($this->manager->hasProviders());
		$audioId = $this->getFile('audioInput', 'Hello')->getId();
		$task = new Task(AudioToImage::ID, ['audio' => $audioId], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);
		self::assertNotNull($task->getId());
		self::assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		// Task object retrieved from db is up-to-date
		$task2 = $this->manager->getTask($task->getId());
		self::assertEquals($task->getId(), $task2->getId());
		self::assertEquals(['audio' => $audioId], $task2->getInput());
		self::assertNull($task2->getOutput());
		self::assertEquals(Task::STATUS_SCHEDULED, $task2->getStatus());

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		$this->manager->setTaskProgress($task2->getId(), 0.1);
		$input = $this->manager->prepareInputData($task2);
		self::assertTrue(isset($input['audio']));
		self::assertEquals(base64_encode('Hello'), $input['audio']);

		$this->manager->setTaskResult($task2->getId(), null, ['spectrogram' => base64_encode('World')]);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertEquals(1, $task->getProgress());
		self::assertTrue(isset($task->getOutput()['spectrogram']));
		$root = \OCP\Server::get(IRootFolder::class);
		$node = $root->getFirstNodeByIdInPath($task->getOutput()['spectrogram'], '/' . $root->getAppDataDirectoryName() . '/');
		self::assertNotNull($node);
		self::assertInstanceOf(\OCP\Files\File::class, $node);
		self::assertEquals('World', $node->getContent());

	}

	public function testNonexistentTask() {
		$this->expectException(\OCP\TaskProcessing\Exception\NotFoundException::class);
		$this->manager->getTask(2147483646);
	}
}
