<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\TaskProcessing;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\EventDispatcher\EventDispatcher;
use OC\TaskProcessing\Db\TaskMapper;
use OC\TaskProcessing\Manager;
use OC\TaskProcessing\RemoveOldTasksBackgroundJob;
use OC\TaskProcessing\SynchronousBackgroundJob;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IServerContainer;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Events\GetTaskProcessingProvidersEvent;
use OCP\TaskProcessing\Events\TaskFailedEvent;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\Exception\PreConditionNotMetException;
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

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}
}

class SuccessfulSyncProvider implements IProvider, ISynchronousProvider {
	public const ID = 'test:sync:success';

	public function getId(): string {
		return self::ID;
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

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
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

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
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

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
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
		foreach ($resources as $resource) {
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

class ExternalProvider implements IProvider {
	public const ID = 'event:external:provider';
	public const TASK_TYPE_ID = 'event:external:tasktype';

	public function getId(): string {
		return self::ID;
	}
	public function getName(): string {
		return 'External Provider via Event';
	}
	public function getTaskTypeId(): string {
		return self::TASK_TYPE_ID;
	}
	public function getExpectedRuntime(): int {
		return 5;
	}
	public function getOptionalInputShape(): array {
		return [];
	}
	public function getOptionalOutputShape(): array {
		return [];
	}
	public function getInputShapeEnumValues(): array {
		return [];
	}
	public function getInputShapeDefaults(): array {
		return [];
	}
	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}
	public function getOptionalInputShapeDefaults(): array {
		return [];
	}
	public function getOutputShapeEnumValues(): array {
		return [];
	}
	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}
}

class ConflictingExternalProvider implements IProvider {
	// Same ID as SuccessfulSyncProvider
	public const ID = 'test:sync:success';
	public const TASK_TYPE_ID = 'event:external:tasktype'; // Can be different task type

	public function getId(): string {
		return self::ID;
	}
	public function getName(): string {
		return 'Conflicting External Provider';
	}
	public function getTaskTypeId(): string {
		return self::TASK_TYPE_ID;
	}
	public function getExpectedRuntime(): int {
		return 50;
	}
	public function getOptionalInputShape(): array {
		return [];
	}
	public function getOptionalOutputShape(): array {
		return [];
	}
	public function getInputShapeEnumValues(): array {
		return [];
	}
	public function getInputShapeDefaults(): array {
		return [];
	}
	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}
	public function getOptionalInputShapeDefaults(): array {
		return [];
	}
	public function getOutputShapeEnumValues(): array {
		return [];
	}
	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}
}

class ExternalTaskType implements ITaskType {
	public const ID = 'event:external:tasktype';

	public function getId(): string {
		return self::ID;
	}
	public function getName(): string {
		return 'External Task Type via Event';
	}
	public function getDescription(): string {
		return 'A task type added via event';
	}
	public function getInputShape(): array {
		return ['external_input' => new ShapeDescriptor('Ext In', '', EShapeType::Text)];
	}
	public function getOutputShape(): array {
		return ['external_output' => new ShapeDescriptor('Ext Out', '', EShapeType::Text)];
	}
}

class ConflictingExternalTaskType implements ITaskType {
	// Same ID as built-in TextToText
	public const ID = TextToText::ID;

	public function getId(): string {
		return self::ID;
	}
	public function getName(): string {
		return 'Conflicting External Task Type';
	}
	public function getDescription(): string {
		return 'Overrides built-in TextToText';
	}
	public function getInputShape(): array {
		return ['override_input' => new ShapeDescriptor('Override In', '', EShapeType::Number)];
	}
	public function getOutputShape(): array {
		return ['override_output' => new ShapeDescriptor('Override Out', '', EShapeType::Number)];
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
	private IUserMountCache $userMountCache;
	private IRootFolder $rootFolder;
	private IConfig $config;

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
			ExternalProvider::class => new ExternalProvider(),
			ConflictingExternalProvider::class => new ConflictingExternalProvider(),
			ExternalTaskType::class => new ExternalTaskType(),
			ConflictingExternalTaskType::class => new ConflictingExternalTaskType(),
		];

		$userManager = Server::get(IUserManager::class);
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
			Server::get(LoggerInterface::class),
		);

		$this->registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator = $this->createMock(Coordinator::class);
		$this->coordinator->expects($this->any())->method('getRegistrationContext')->willReturn($this->registrationContext);

		$this->rootFolder = Server::get(IRootFolder::class);

		$this->taskMapper = Server::get(TaskMapper::class);

		$this->jobList = $this->createPartialMock(DummyJobList::class, ['add']);
		$this->jobList->expects($this->any())->method('add')->willReturnCallback(function (): void {
		});

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->configureEventDispatcherMock();

		$text2imageManager = new \OC\TextToImage\Manager(
			$this->serverContainer,
			$this->coordinator,
			Server::get(LoggerInterface::class),
			$this->jobList,
			Server::get(\OC\TextToImage\Db\TaskMapper::class),
			Server::get(IConfig::class),
			Server::get(IAppDataFactory::class),
		);

		$this->userMountCache = $this->createMock(IUserMountCache::class);
		$this->config = Server::get(IConfig::class);
		$this->manager = new Manager(
			$this->config,
			$this->coordinator,
			$this->serverContainer,
			Server::get(LoggerInterface::class),
			$this->taskMapper,
			$this->jobList,
			$this->eventDispatcher,
			Server::get(IAppDataFactory::class),
			Server::get(IRootFolder::class),
			$text2imageManager,
			$this->userMountCache,
			Server::get(IClientService::class),
			Server::get(IAppManager::class),
			Server::get(ICacheFactory::class),
		);
	}

	private function getFile(string $name, string $content): File {
		$folder = $this->rootFolder->getUserFolder(self::TEST_USER);
		$file = $folder->newFile($name, $content);
		return $file;
	}

	public function testShouldNotHaveAnyProviders(): void {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([]);
		self::assertCount(0, $this->manager->getAvailableTaskTypes());
		self::assertFalse($this->manager->hasProviders());
		self::expectException(PreConditionNotMetException::class);
		$this->manager->scheduleTask(new Task(TextToText::ID, ['input' => 'Hello'], 'test', null));
	}

	public function testProviderShouldBeRegisteredAndTaskTypeDisabled(): void {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSyncProvider::class)
		]);
		$taskProcessingTypeSettings = [
			TextToText::ID => false,
		];
		$this->config->setAppValue('core', 'ai.taskprocessing_type_preferences', json_encode($taskProcessingTypeSettings));
		self::assertCount(0, $this->manager->getAvailableTaskTypes());
		self::assertCount(1, $this->manager->getAvailableTaskTypes(true));
		self::assertTrue($this->manager->hasProviders());
		self::expectException(PreConditionNotMetException::class);
		$this->manager->scheduleTask(new Task(TextToText::ID, ['input' => 'Hello'], 'test', null));
	}


	public function testProviderShouldBeRegisteredAndTaskFailValidation(): void {
		$this->config->setAppValue('core', 'ai.taskprocessing_type_preferences', '');
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

	public function testProviderShouldBeRegisteredAndTaskWithFilesFailValidation(): void {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([
			new ServiceRegistration('test', AudioToImage::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', AsyncProvider::class)
		]);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn(null);
		$mount = $this->createMock(ICachedMountInfo::class);
		$mount->expects($this->any())->method('getUser')->willReturn($user);
		$this->userMountCache->expects($this->any())->method('getMountsForFileId')->willReturn([$mount]);

		self::assertCount(1, $this->manager->getAvailableTaskTypes());
		self::assertTrue($this->manager->hasProviders());

		$audioId = $this->getFile('audioInput', 'Hello')->getId();
		$task = new Task(AudioToImage::ID, ['audio' => $audioId], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		self::expectException(UnauthorizedException::class);
		$this->manager->scheduleTask($task);
	}

	public function testProviderShouldBeRegisteredAndFail(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_FAILED, $task->getStatus());
		self::assertEquals(FailingSyncProvider::ERROR_MESSAGE, $task->getErrorMessage());
	}

	public function testProviderShouldBeRegisteredAndFailOutputValidation(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_FAILED, $task->getStatus());
		self::assertEquals('The task was processed successfully but the provider\'s output doesn\'t pass validation against the task type\'s outputShape spec and/or the provider\'s own optionalOutputShape spec', $task->getErrorMessage());
	}

	public function testProviderShouldBeRegisteredAndRun(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus(), 'Status is ' . $task->getStatus() . ' with error message: ' . $task->getErrorMessage());
		self::assertEquals(['output' => 'Hello'], $task->getOutput());
		self::assertEquals(1, $task->getProgress());
	}

	public function testTaskTypeExplicitlyEnabled(): void {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSyncProvider::class)
		]);

		$taskProcessingTypeSettings = [
			TextToText::ID => true,
		];
		$this->config->setAppValue('core', 'ai.taskprocessing_type_preferences', json_encode($taskProcessingTypeSettings));

		self::assertCount(1, $this->manager->getAvailableTaskTypes());

		self::assertTrue($this->manager->hasProviders());
		$task = new Task(TextToText::ID, ['input' => 'Hello'], 'test', null);
		self::assertNull($task->getId());
		self::assertEquals(Task::STATUS_UNKNOWN, $task->getStatus());
		$this->manager->scheduleTask($task);
		self::assertNotNull($task->getId());
		self::assertEquals(Task::STATUS_SCHEDULED, $task->getStatus());

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new IsInstanceOf(TaskSuccessfulEvent::class));

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus(), 'Status is ' . $task->getStatus() . ' with error message: ' . $task->getErrorMessage());
		self::assertEquals(['output' => 'Hello'], $task->getOutput());
		self::assertEquals(1, $task->getProgress());
	}

	public function testAsyncProviderWithFilesShouldBeRegisteredAndRunReturningRawFileData(): void {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([
			new ServiceRegistration('test', AudioToImage::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', AsyncProvider::class)
		]);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn('testuser');
		$mount = $this->createMock(ICachedMountInfo::class);
		$mount->expects($this->any())->method('getUser')->willReturn($user);
		$this->userMountCache->expects($this->any())->method('getMountsForFileId')->willReturn([$mount]);

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
		self::assertInstanceOf(File::class, $input['audio']);
		self::assertEquals($audioId, $input['audio']->getId());

		$this->manager->setTaskResult($task2->getId(), null, ['spectrogram' => 'World']);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertEquals(1, $task->getProgress());
		self::assertTrue(isset($task->getOutput()['spectrogram']));
		$node = $this->rootFolder->getFirstNodeByIdInPath($task->getOutput()['spectrogram'], '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
		self::assertNotNull($node);
		self::assertInstanceOf(File::class, $node);
		self::assertEquals('World', $node->getContent());
	}

	public function testAsyncProviderWithFilesShouldBeRegisteredAndRunReturningFileIds(): void {
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([
			new ServiceRegistration('test', AudioToImage::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', AsyncProvider::class)
		]);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn('testuser');
		$mount = $this->createMock(ICachedMountInfo::class);
		$mount->expects($this->any())->method('getUser')->willReturn($user);
		$this->userMountCache->expects($this->any())->method('getMountsForFileId')->willReturn([$mount]);
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
		self::assertInstanceOf(File::class, $input['audio']);
		self::assertEquals($audioId, $input['audio']->getId());

		$outputFileId = $this->getFile('audioOutput', 'World')->getId();

		$this->manager->setTaskResult($task2->getId(), null, ['spectrogram' => $outputFileId], true);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertEquals(1, $task->getProgress());
		self::assertTrue(isset($task->getOutput()['spectrogram']));
		$node = $this->rootFolder->getFirstNodeById($task->getOutput()['spectrogram']);
		self::assertNotNull($node, 'fileId:' . $task->getOutput()['spectrogram']);
		self::assertInstanceOf(File::class, $node);
		self::assertEquals('World', $node->getContent());
	}

	public function testNonexistentTask(): void {
		$this->expectException(NotFoundException::class);
		$this->manager->getTask(2147483646);
	}

	public function testOldTasksShouldBeCleanedUp(): void {
		$currentTime = new \DateTime('now');
		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->expects($this->any())->method('getDateTime')->willReturnCallback(fn () => $currentTime);
		$timeFactory->expects($this->any())->method('getTime')->willReturnCallback(fn () => $currentTime->getTimestamp());

		$this->taskMapper = new TaskMapper(
			Server::get(IDBConnection::class),
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());

		$currentTime = $currentTime->add(new \DateInterval('P1Y'));
		// run background job
		$bgJob = new RemoveOldTasksBackgroundJob(
			$timeFactory,
			$this->taskMapper,
			Server::get(LoggerInterface::class),
			Server::get(IAppDataFactory::class),
		);
		$bgJob->setArgument([]);
		$bgJob->start($this->jobList);

		$this->expectException(NotFoundException::class);
		$this->manager->getTask($task->getId());
	}

	public function testShouldTransparentlyHandleTextProcessingProviders(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_SUCCESSFUL, $task->getStatus());
		self::assertIsArray($task->getOutput());
		self::assertTrue(isset($task->getOutput()['output']));
		self::assertEquals('Hello Summarize', $task->getOutput()['output']);
		self::assertTrue($this->providers[SuccessfulTextProcessingSummaryProvider::class]->ran);
	}

	public function testShouldTransparentlyHandleFailingTextProcessingProviders(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_FAILED, $task->getStatus());
		self::assertTrue($task->getOutput() === null);
		self::assertEquals('ERROR', $task->getErrorMessage());
		self::assertTrue($this->providers[FailingTextProcessingSummaryProvider::class]->ran);
	}

	public function testShouldTransparentlyHandleText2ImageProviders(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
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
		self::assertInstanceOf(File::class, $node);
		self::assertEquals('test', $node->getContent());
	}

	public function testShouldTransparentlyHandleFailingText2ImageProviders(): void {
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

		$backgroundJob = new SynchronousBackgroundJob(
			Server::get(ITimeFactory::class),
			$this->manager,
			$this->jobList,
			Server::get(LoggerInterface::class),
		);
		$backgroundJob->start($this->jobList);

		$task = $this->manager->getTask($task->getId());
		self::assertEquals(Task::STATUS_FAILED, $task->getStatus());
		self::assertTrue($task->getOutput() === null);
		self::assertEquals('ERROR', $task->getErrorMessage());
		self::assertTrue($this->providers[FailingTextToImageProvider::class]->ran);
	}

	public function testMergeProvidersLocalAndEvent() {
		// Arrange: Local provider registered, DIFFERENT external provider via event
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSyncProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getSpeechToTextProviders')->willReturn([]);

		$externalProvider = new ExternalProvider(); // ID = 'event:external:provider'
		$this->configureEventDispatcherMock(providersToAdd: [$externalProvider]);
		$this->manager = $this->createManagerInstance();

		// Act
		$providers = $this->manager->getProviders();

		// Assert: Both providers should be present
		self::assertArrayHasKey(SuccessfulSyncProvider::ID, $providers);
		self::assertInstanceOf(SuccessfulSyncProvider::class, $providers[SuccessfulSyncProvider::ID]);
		self::assertArrayHasKey(ExternalProvider::ID, $providers);
		self::assertInstanceOf(ExternalProvider::class, $providers[ExternalProvider::ID]);
		self::assertCount(2, $providers);
	}

	public function testGetProvidersIncludesExternalViaEvent() {
		// Arrange: No local providers, one external provider via event
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getSpeechToTextProviders')->willReturn([]);


		$externalProvider = new ExternalProvider();
		$this->configureEventDispatcherMock(providersToAdd: [$externalProvider]);
		$this->manager = $this->createManagerInstance(); // Create manager with configured mocks

		// Act
		$providers = $this->manager->getProviders(); // Returns ID-indexed array

		// Assert
		self::assertArrayHasKey(ExternalProvider::ID, $providers);
		self::assertInstanceOf(ExternalProvider::class, $providers[ExternalProvider::ID]);
		self::assertCount(1, $providers);
		self::assertTrue($this->manager->hasProviders());
	}

	public function testGetAvailableTaskTypesIncludesExternalViaEvent() {
		// Arrange: No local types/providers, one external type and provider via event
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getSpeechToTextProviders')->willReturn([]);

		$externalProvider = new ExternalProvider(); // Provides ExternalTaskType
		$externalTaskType = new ExternalTaskType();
		$this->configureEventDispatcherMock(
			providersToAdd: [$externalProvider],
			taskTypesToAdd: [$externalTaskType]
		);
		$this->manager = $this->createManagerInstance();

		// Act
		$availableTypes = $this->manager->getAvailableTaskTypes();

		// Assert
		self::assertArrayHasKey(ExternalTaskType::ID, $availableTypes);
		self::assertEquals(ExternalTaskType::ID, $externalProvider->getTaskTypeId(), 'Test Sanity: Provider must handle the Task Type');
		self::assertEquals('External Task Type via Event', $availableTypes[ExternalTaskType::ID]['name']);
		// Check if shapes match the external type/provider
		self::assertArrayHasKey('external_input', $availableTypes[ExternalTaskType::ID]['inputShape']);
		self::assertArrayHasKey('external_output', $availableTypes[ExternalTaskType::ID]['outputShape']);
		self::assertEmpty($availableTypes[ExternalTaskType::ID]['optionalInputShape']); // From ExternalProvider
	}

	public function testLocalProviderWinsConflictWithEvent() {
		// Arrange: Local provider registered, conflicting external provider via event
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', SuccessfulSyncProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getSpeechToTextProviders')->willReturn([]);

		$conflictingExternalProvider = new ConflictingExternalProvider(); // ID = 'test:sync:success'
		$this->configureEventDispatcherMock(providersToAdd: [$conflictingExternalProvider]);
		$this->manager = $this->createManagerInstance();

		// Act
		$providers = $this->manager->getProviders();

		// Assert: Only the local provider should be present for the conflicting ID
		self::assertArrayHasKey(SuccessfulSyncProvider::ID, $providers);
		self::assertInstanceOf(SuccessfulSyncProvider::class, $providers[SuccessfulSyncProvider::ID]);
		self::assertCount(1, $providers); // Ensure no extra provider was added
	}

	public function testMergeTaskTypesLocalAndEvent() {
		// Arrange: Local type registered, DIFFERENT external type via event
		$this->registrationContext->expects($this->any())->method('getTaskProcessingProviders')->willReturn([
			new ServiceRegistration('test', AsyncProvider::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTaskProcessingTaskTypes')->willReturn([
			new ServiceRegistration('test', AudioToImage::class)
		]);
		$this->registrationContext->expects($this->any())->method('getTextProcessingProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getTextToImageProviders')->willReturn([]);
		$this->registrationContext->expects($this->any())->method('getSpeechToTextProviders')->willReturn([]);

		$externalTaskType = new ExternalTaskType(); // ID = 'event:external:tasktype'
		$externalProvider = new ExternalProvider(); // Handles 'event:external:tasktype'
		$this->configureEventDispatcherMock(
			providersToAdd: [$externalProvider],
			taskTypesToAdd: [$externalTaskType]
		);
		$this->manager = $this->createManagerInstance();

		// Act
		$availableTypes = $this->manager->getAvailableTaskTypes();

		// Assert: Both task types should be available
		self::assertArrayHasKey(AudioToImage::ID, $availableTypes);
		self::assertEquals(AudioToImage::class, $availableTypes[AudioToImage::ID]['name']);

		self::assertArrayHasKey(ExternalTaskType::ID, $availableTypes);
		self::assertEquals('External Task Type via Event', $availableTypes[ExternalTaskType::ID]['name']);

		self::assertCount(2, $availableTypes);
	}

	private function createManagerInstance(): Manager {
		// Clear potentially cached config values if needed
		$this->config->deleteAppValue('core', 'ai.taskprocessing_type_preferences');

		// Re-create Text2ImageManager if its state matters or mocks change
		$text2imageManager = new \OC\TextToImage\Manager(
			$this->serverContainer,
			$this->coordinator,
			Server::get(LoggerInterface::class),
			$this->jobList,
			Server::get(\OC\TextToImage\Db\TaskMapper::class),
			$this->config, // Use the shared config mock
			Server::get(IAppDataFactory::class),
		);

		return new Manager(
			$this->config,
			$this->coordinator,
			$this->serverContainer,
			Server::get(LoggerInterface::class),
			$this->taskMapper,
			$this->jobList,
			$this->eventDispatcher, // Use the potentially reconfigured mock
			Server::get(IAppDataFactory::class),
			$this->rootFolder,
			$text2imageManager,
			$this->userMountCache,
			Server::get(IClientService::class),
			Server::get(IAppManager::class),
			Server::get(ICacheFactory::class),
		);
	}

	private function configureEventDispatcherMock(
		array $providersToAdd = [],
		array $taskTypesToAdd = [],
		?int $expectedCalls = null,
	): void {
		$dispatchExpectation = $expectedCalls === null ? $this->any() : $this->exactly($expectedCalls);

		$this->eventDispatcher->expects($dispatchExpectation)
			->method('dispatchTyped')
			->willReturnCallback(function (object $event) use ($providersToAdd, $taskTypesToAdd): void {
				if ($event instanceof GetTaskProcessingProvidersEvent) {
					foreach ($providersToAdd as $providerInstance) {
						$event->addProvider($providerInstance);
					}
					foreach ($taskTypesToAdd as $taskTypeInstance) {
						$event->addTaskType($taskTypeInstance);
					}
				}
			});
	}
}
