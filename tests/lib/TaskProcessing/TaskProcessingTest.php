<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\TextProcessing;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\EventDispatcher\EventDispatcher;
use OC\TaskProcessing\Db\TaskMapper;
use OC\TaskProcessing\Manager;
use OC\TaskProcessing\RemoveOldTasksBackgroundJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IServerContainer;
use OCP\IUserManager;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Events\TaskFailedEvent;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\Exception\UnauthorizedException;
use OCP\TaskProcessing\Exception\ValidationException;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\IProvider;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\Task;
use OCP\TaskProcessing\TaskTypes\TextToImage;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use OCP\TextProcessing\SummaryTaskType;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Psr\Log\LoggerInterface;
use Test\BackgroundJob\DummyJobList;

class AudioToImage implements ITaskType {
	public const ID = 'test:audiotoimage';

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

	public function getTaskTypeId(): string {
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

	public function getTaskTypeId(): string {
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

	public function process(?string $userId, array $input, callable $reportProgress): array {
		return ['output' => $input['input']];
	}
}

class FailingSyncProvider implements IProvider, ISynchronousProvider {
	public const ERROR_MESSAGE = 'Failure';
	public function getId(): string {
		return 'test:sync:fail';
	}

	public function getName(): string {
		return self::class;
	}

	public function getTaskTypeId(): string {
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

	public function process(?string $userId, array $input, callable $reportProgress): array {
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

	public function getTaskTypeId(): string {
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

	public function process(?string $userId, array $input, callable $reportProgress): array {
		return [];
	}
}

class SuccessfulTextProcessingSummaryProvider implements \OCP\TextProcessing\IProvider {
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

class FailingTextProcessingSummaryProvider implements \OCP\TextProcessing\IProvider {
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

class SuccessfulTextToImageProvider implements \OCP\TextToImage\IProvider {
	public bool $ran = false;

	public function getId(): string {
		return 'test:successful';
	}

	public function getName(): string {
		return 'TEST Provider';
	}

	public function generate(string $prompt, array $resources): void {
		$this->ran = true;
		foreach($resources as $resource) {
			fwrite($resource, 'test');
		}
	}

	public function getExpectedRuntime(): int {
		return 1;
	}
}

class FailingTextToImageProvider implements \OCP\TextToImage\IProvider {
	public bool $ran = false;

	public function getId(): string {
		return 'test:failing';
	}

	public function getName(): string {
		return 'TEST Provider';
	}

	public function generate(string $prompt, array $resources): void {
		$this->ran = true;
		throw new \RuntimeException('ERROR');
	}

	public function getExpectedRuntime(): int {
		return 1;
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
	private TaskMapper $taskMapper;
	private IJobList $jobList;
	private IAppData $appData;
	private \OCP\Share\IManager $shareManager;
	private IRootFolder $rootFolder;

	public const TEST_USER = 'testuser';

	protected function setUp(): void {
		parent::setUp();

		$this->providers = [
			SuccessfulSyncProvider::class => new SuccessfulSyncProvider(),
			FailingSyncProvider::class => new FailingSyncProvider(),
			BrokenSyncProvider::class => new BrokenSyncProvider(),
			AsyncProvider::class => new AsyncProvider(),
			AudioToImage::class => new AudioToImage(),
			SuccessfulTextProcessingSummaryProvider::class => new SuccessfulTextProcessingSummaryProvider(),
			FailingTextProcessingSummaryProvider::class => new FailingTextProcessingSummaryProvider(),
			SuccessfulTextToImageProvider::class => new SuccessfulTextToImageProvider(),
			FailingTextToImageProvider::class => new FailingTextToImageProvider(),
		];

		$userManager = \OCP\Server::get(IUserManager::class);
		if (!$userManager->userExists(self::TEST_USER)) {
			$userManager->createUser(self::TEST_USER, 'test');
		}

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

		$this->rootFolder = \OCP\Server::get(IRootFolder::class);

		$this->taskMapper = \OCP\Server::get(TaskMapper::class);

		$this->jobList = $this->createPartialMock(DummyJobList::class, ['add']);
		$this->jobList->expects($this->any())->method('add')->willReturnCallback(function () {
		});

		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')
			->with('core', 'ai.textprocessing_provider_preferences', '')
			->willReturn('');

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$textProcessingManager = new \OC\TextProcessing\Manager(
			$this->serverContainer,
			$this->coordinator,
			\OC::$server->get(LoggerInterface::class),
			$this->jobList,
			\OC::$server->get(\OC\TextProcessing\Db\TaskMapper::class),
			\OC::$server->get(IConfig::class),
		);

		$text2imageManager = new \OC\TextToImage\Manager(
			$this->serverContainer,
			$this->coordinator,
			\OC::$server->get(LoggerInterface::class),
			$this->jobList,
			\OC::$server->get(\OC\TextToImage\Db\TaskMapper::class),
			\OC::$server->get(IConfig::class),
			\OC::$server->get(IAppDataFactory::class),
		);

		$this->shareManager = $this->createMock(\OCP\Share\IManager::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->serverContainer,
			\OC::$server->get(LoggerInterface::class),
			$this->taskMapper,
			$this->jobList,
			$this->eventDispatcher,
			\OC::$server->get(IAppDataFactory::class),
			\OC::$server->get(IRootFolder::class),
			$textProcessingManager,
			$text2imageManager,
			\OC::$server->get(ISpeechToTextManager::class),
			$this->shareManager,
		);
	}

	private function getFile(string $name, string $content): \OCP\Files\File {
		$folder = $this->rootFolder->getUserFolder(self::TEST_USER);
		$file = $folder->newFile($name, $content);
		return $file;
	}

	public function testShouldNotHaveAnyProviders() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([]);
		self::assertCount(0, $this->manager->getAvailableTaskTypes());
		self::assertFalse($this->manager->hasProviders());
		self::expectException(\OCP\TaskProcessing\Exception\PreConditionNotMetException::class);
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

	public function testProviderShouldBeRegisteredAndTaskWithFilesFailValidation() {
		$this->shareManager->expects($this->any())->method('getAccessList')->willReturn(['users' => []]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([
			new ServiceRegistration('test', AudioToImage::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', AsyncProvider::class)
		]);
		$this->shareManager->expects($this->any())->method('getAccessList')->willReturn(['users' => [null]]);
		self::assertCount(1, $this->manager->getAvailableTaskTypes());

		self::assertTrue($this->manager->hasProviders());
		$audioId = $this->getFile('audioInput', 'Hello')->getId();
		$task = new Task(AudioToImage::ID, ['audio' => $audioId], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		self::expectException(UnauthorizedException::class);
		$this->manager->scheduleTask($task);
	}

	public function testProviderShouldBeRegisteredAndFail() {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', FailingSyncProvider::class)
		]);
		self::assertCount(1, $this->manager->getAvailableTaskTypes());
		self::assertTrue($this->manager->hasProviders());
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
		self::assertCount(1, $this->manager->getAvailableTaskTypes());
		self::assertTrue($this->manager->hasProviders());
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
		self::assertCount(1, $this->manager->getAvailableTaskTypes());
		$taskTypeStruct = $this->manager->getAvailableTaskTypes()[array_keys($this->manager->getAvailableTaskTypes())[0]];
		self::assertTrue(isset($taskTypeStruct['inputShape']['input']));
		self::assertEquals(EShapeType::Text, $taskTypeStruct['inputShape']['input']->getShapeType());
		self::assertTrue(isset($taskTypeStruct['optionalInputShape']['optionalKey']));
		self::assertEquals(EShapeType::Text, $taskTypeStruct['optionalInputShape']['optionalKey']->getShapeType());
		self::assertTrue(isset($taskTypeStruct['outputShape']['output']));
		self::assertEquals(EShapeType::Text, $taskTypeStruct['outputShape']['output']->getShapeType());
		self::assertTrue(isset($taskTypeStruct['optionalOutputShape']['optionalKey']));
		self::assertEquals(EShapeType::Text, $taskTypeStruct['optionalOutputShape']['optionalKey']->getShapeType());

		self::assertTrue($this->manager->hasProviders());
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
		$this->shareManager->expects($this->any())->method('getAccessList')->willReturn(['users' => ['testuser' => 1]]);
		self::assertCount(1, $this->manager->getAvailableTaskTypes());

		self::assertTrue($this->manager->hasProviders());
		$audioId = $this->getFile('audioInput', 'Hello')->getId();
		$task = new Task(AudioToImage::ID, ['audio' => $audioId], 'test', 'testuser');
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
		self::assertInstanceOf(\OCP\Files\File::class, $input['audio']);
		self::assertEquals($audioId, $input['audio']->getId());

		$this->manager->setTaskResult($task2->getId(), null, ['spectrogram' => 'World']);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertEquals(1, $task->getProgress());
		self::assertTrue(isset($task->getOutput()['spectrogram']));
		$node = $this->rootFolder->getFirstNodeByIdInPath($task->getOutput()['spectrogram'], '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
		self::assertNotNull($node);
		self::assertInstanceOf(\OCP\Files\File::class, $node);
		self::assertEquals('World', $node->getContent());
	}

	public function testNonexistentTask() {
		$this->expectException(\OCP\TaskProcessing\Exception\NotFoundException::class);
		$this->manager->getTask(2147483646);
	}

	public function testOldTasksShouldBeCleanedUp() {
		$currentTime = new \DateTime('now');
		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->expects($this->any())->method('getDateTime')->willReturnCallback(fn () => $currentTime);
		$timeFactory->expects($this->any())->method('getTime')->willReturnCallback(fn () => $currentTime->getTimestamp());

		$this->taskMapper = new TaskMapper(
			\OCP\Server::get(IDBConnection::class),
			$timeFactory,
		);

		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSyncProvider::class)
		]);
		self::assertCount(1, $this->manager->getAvailableTaskTypes());
		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToText::ID, ['input' => 'Hello'], 'test', null);
		$this->manager->scheduleTask($task);

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		$backgroundJob = new \OC\TaskProcessing\SynchronousBackgroundJob(
			\OCP\Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			\OCP\Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());

		$currentTime = $currentTime->add(new \DateInterval('P1Y'));
		// run background job
		$bgJob = new RemoveOldTasksBackgroundJob(
			$timeFactory,
			$this->taskMapper,
			\OC::$server->get(LoggerInterface::class),
			\OCP\Server::get(IAppDataFactory::class),
		);
		$bgJob->setArgument([]);
		$bgJob->start($this->jobList);

		$this->expectException(NotFoundException::class);
		$this->manager->getTask($task->getId());
	}

	public function testShouldTransparentlyHandleTextProcessingProviders() {
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulTextProcessingSummaryProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
		]);
		$taskTypes = $this->manager->getAvailableTaskTypes();
		self::assertCount(1, $taskTypes);
		self::assertTrue(isset($taskTypes[TextToTextSummary::ID]));
		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToTextSummary::ID, ['input' => 'Hello'], 'test', null);
		$this->manager->scheduleTask($task);

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		$backgroundJob = new \OC\TaskProcessing\SynchronousBackgroundJob(
			\OCP\Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			\OCP\Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertIsArray($task->getOutput());
		self::assertTrue(isset($task->getOutput()['output']));
		self::assertEquals('Hello Summarize', $task->getOutput()['output']);
		self::assertTrue($this->providers[SuccessfulTextProcessingSummaryProvider::class]->ran);
	}

	public function testShouldTransparentlyHandleFailingTextProcessingProviders() {
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([
			new ServiceRegistration('test', FailingTextProcessingSummaryProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
		]);
		$taskTypes = $this->manager->getAvailableTaskTypes();
		self::assertCount(1, $taskTypes);
		self::assertTrue(isset($taskTypes[TextToTextSummary::ID]));
		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToTextSummary::ID, ['input' => 'Hello'], 'test', null);
		$this->manager->scheduleTask($task);

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
		self::assertTrue($task->getOutput() === null);
		self::assertEquals('ERROR', $task->getErrorMessage());
		self::assertTrue($this->providers[FailingTextProcessingSummaryProvider::class]->ran);
	}

	public function testShouldTransparentlyHandleText2ImageProviders() {
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulTextToImageProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
		]);
		$taskTypes = $this->manager->getAvailableTaskTypes();
		self::assertCount(1, $taskTypes);
		self::assertTrue(isset($taskTypes[TextToImage::ID]));
		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToImage::ID, ['input' => 'Hello', 'numberOfImages' => 3], 'test', null);
		$this->manager->scheduleTask($task);

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		$backgroundJob = new \OC\TaskProcessing\SynchronousBackgroundJob(
			\OCP\Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			\OCP\Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertIsArray($task->getOutput());
		self::assertTrue(isset($task->getOutput()['images']));
		self::assertIsArray($task->getOutput()['images']);
		self::assertCount(3, $task->getOutput()['images']);
		self::assertTrue($this->providers[SuccessfulTextToImageProvider::class]->ran);
		$node = $this->rootFolder->getFirstNodeByIdInPath($task->getOutput()['images'][0], '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
		self::assertNotNull($node);
		self::assertInstanceOf(\OCP\Files\File::class, $node);
		self::assertEquals('test', $node->getContent());
	}

	public function testShouldTransparentlyHandleFailingText2ImageProviders() {
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([
			new ServiceRegistration('test', FailingTextToImageProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
		]);
		$taskTypes = $this->manager->getAvailableTaskTypes();
		self::assertCount(1, $taskTypes);
		self::assertTrue(isset($taskTypes[TextToImage::ID]));
		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToImage::ID, ['input' => 'Hello', 'numberOfImages' => 3], 'test', null);
		$this->manager->scheduleTask($task);

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
		self::assertTrue($task->getOutput() === null);
		self::assertEquals('ERROR', $task->getErrorMessage());
		self::assertTrue($this->providers[FailingTextToImageProvider::class]->ran);
	}
}
