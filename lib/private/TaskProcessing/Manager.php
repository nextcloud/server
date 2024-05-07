<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\TaskProcessing;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Files\SimpleFS\SimpleFile;
use OC\TaskProcessing\Db\TaskMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\L10N\IFactory;
use OCP\Lock\LockedException;
use OCP\SpeechToText\ISpeechToTextProvider;
use OCP\SpeechToText\ISpeechToTextProviderWithId;
use OCP\SpeechToText\ISpeechToTextProviderWithUserId;
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
use OCP\TaskProcessing\TaskTypes\AudioToText;
use OCP\TaskProcessing\TaskTypes\TextToImage;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextHeadline;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use OCP\TaskProcessing\TaskTypes\TextToTextTopics;
use Psr\Log\LoggerInterface;

class Manager implements IManager {

	public const LEGACY_PREFIX_TEXTPROCESSING = 'legacy:TextProcessing:';
	public const LEGACY_PREFIX_TEXTTOIMAGE = 'legacy:TextToImage:';
	public const LEGACY_PREFIX_SPEECHTOTEXT = 'legacy:SpeechToText:';

	/** @var list<IProvider>|null  */
	private ?array $providers = null;

	/** @var array<string,array{name: string, description: string, inputShape: array<string, ShapeDescriptor>, optionalInputShape: array<string, ShapeDescriptor>, outputShape: array<string, ShapeDescriptor>, optionalOutputShape: array<string, ShapeDescriptor>}>|null  */
	private ?array $availableTaskTypes = null;

	private IAppData $appData;

	public function __construct(
		private Coordinator $coordinator,
		private IServerContainer $serverContainer,
		private LoggerInterface $logger,
		private TaskMapper $taskMapper,
		private IJobList $jobList,
		private IEventDispatcher $dispatcher,
		IAppDataFactory $appDataFactory,
		private IRootFolder $rootFolder,
		private \OCP\TextProcessing\IManager $textProcessingManager,
		private \OCP\TextToImage\IManager $textToImageManager,
		private \OCP\SpeechToText\ISpeechToTextManager $speechToTextManager,
		private \OCP\Share\IManager $shareManager,
	) {
		$this->appData = $appDataFactory->get('core');
	}

	/**
	 * @return IProvider[]
	 */
	private function _getTextProcessingProviders(): array {
		$oldProviders = $this->textProcessingManager->getProviders();
		$newProviders = [];
		foreach ($oldProviders as $oldProvider) {
			$provider = new class($oldProvider) implements IProvider, ISynchronousProvider {
				private \OCP\TextProcessing\IProvider $provider;

				public function __construct(\OCP\TextProcessing\IProvider $provider) {
					$this->provider = $provider;
				}

				public function getId(): string {
					if ($this->provider instanceof \OCP\TextProcessing\IProviderWithId) {
						return $this->provider->getId();
					}
					return Manager::LEGACY_PREFIX_TEXTPROCESSING . $this->provider::class;
				}

				public function getName(): string {
					return $this->provider->getName();
				}

				public function getTaskTypeId(): string {
					return match ($this->provider->getTaskType()) {
						\OCP\TextProcessing\FreePromptTaskType::class => TextToText::ID,
						\OCP\TextProcessing\HeadlineTaskType::class => TextToTextHeadline::ID,
						\OCP\TextProcessing\TopicsTaskType::class => TextToTextTopics::ID,
						\OCP\TextProcessing\SummaryTaskType::class => TextToTextSummary::ID,
						default => Manager::LEGACY_PREFIX_TEXTPROCESSING . $this->provider->getTaskType(),
					};
				}

				public function getExpectedRuntime(): int {
					if ($this->provider instanceof \OCP\TextProcessing\IProviderWithExpectedRuntime) {
						return $this->provider->getExpectedRuntime();
					}
					return 60;
				}

				public function getOptionalInputShape(): array {
					return [];
				}

				public function getOptionalOutputShape(): array {
					return [];
				}

				public function process(?string $userId, array $input): array {
					if ($this->provider instanceof \OCP\TextProcessing\IProviderWithUserId) {
						$this->provider->setUserId($userId);
					}
					try {
						return ['output' => $this->provider->process($input['input'])];
					} catch(\RuntimeException $e) {
						throw new ProcessingException($e->getMessage(), 0, $e);
					}
				}
			};
			$newProviders[$provider->getId()] = $provider;
		}

		return $newProviders;
	}

	/**
	 * @return ITaskType[]
	 */
	private function _getTextProcessingTaskTypes(): array {
		$oldProviders = $this->textProcessingManager->getProviders();
		$newTaskTypes = [];
		foreach ($oldProviders as $oldProvider) {
			// These are already implemented in the TaskProcessing realm
			if (in_array($oldProvider->getTaskType(), [
				\OCP\TextProcessing\FreePromptTaskType::class,
				\OCP\TextProcessing\HeadlineTaskType::class,
				\OCP\TextProcessing\TopicsTaskType::class,
				\OCP\TextProcessing\SummaryTaskType::class
			], true)) {
				continue;
			}
			$taskType = new class($oldProvider->getTaskType()) implements ITaskType {
				private string $oldTaskTypeClass;
				private \OCP\TextProcessing\ITaskType $oldTaskType;
				private IL10N $l;

				public function __construct(string $oldTaskTypeClass) {
					$this->oldTaskTypeClass = $oldTaskTypeClass;
					$this->oldTaskType = \OCP\Server::get($oldTaskTypeClass);
					$this->l = \OCP\Server::get(IFactory::class)->get('core');
				}

				public function getId(): string {
					return Manager::LEGACY_PREFIX_TEXTPROCESSING . $this->oldTaskTypeClass;
				}

				public function getName(): string {
					return $this->oldTaskType->getName();
				}

				public function getDescription(): string {
					return $this->oldTaskType->getDescription();
				}

				public function getInputShape(): array {
					return ['input' => new ShapeDescriptor($this->l->t('Input text'), $this->l->t('The input text'), EShapeType::Text)];
				}

				public function getOutputShape(): array {
					return ['output' => new ShapeDescriptor($this->l->t('Input text'), $this->l->t('The input text'), EShapeType::Text)];
				}
			};
			$newTaskTypes[$taskType->getId()] = $taskType;
		}

		return $newTaskTypes;
	}

	/**
	 * @return IProvider[]
	 */
	private function _getTextToImageProviders(): array {
		$oldProviders = $this->textToImageManager->getProviders();
		$newProviders = [];
		foreach ($oldProviders as $oldProvider) {
			$newProvider = new class($oldProvider, $this->appData) implements IProvider, ISynchronousProvider {
				private \OCP\TextToImage\IProvider $provider;
				private IAppData $appData;

				public function __construct(\OCP\TextToImage\IProvider $provider, IAppData $appData) {
					$this->provider = $provider;
					$this->appData = $appData;
				}

				public function getId(): string {
					return Manager::LEGACY_PREFIX_TEXTTOIMAGE . $this->provider->getId();
				}

				public function getName(): string {
					return $this->provider->getName();
				}

				public function getTaskTypeId(): string {
					return TextToImage::ID;
				}

				public function getExpectedRuntime(): int {
					return $this->provider->getExpectedRuntime();
				}

				public function getOptionalInputShape(): array {
					return [];
				}

				public function getOptionalOutputShape(): array {
					return [];
				}

				public function process(?string $userId, array $input): array {
					try {
						$folder = $this->appData->getFolder('text2image');
					} catch(\OCP\Files\NotFoundException) {
						$folder = $this->appData->newFolder('text2image');
					}
					$resources = [];
					$files = [];
					for ($i = 0; $i < $input['numberOfImages']; $i++) {
						$file = $folder->newFile(time() . '-' . rand(1, 100000) . '-' .  $i);
						$files[] = $file;
						$resource = $file->write();
						if ($resource !== false && $resource !== true && is_resource($resource)) {
							$resources[] = $resource;
						} else {
							throw new ProcessingException('Text2Image generation using provider "' . $this->getName() . '" failed: Couldn\'t open file to write.');
						}
					}
					if ($this->provider instanceof \OCP\TextToImage\IProviderWithUserId) {
						$this->provider->setUserId($userId);
					}
					try {
						$this->provider->generate($input['input'], $resources);
					} catch (\RuntimeException $e) {
						throw new ProcessingException($e->getMessage(), 0, $e);
					}
					return ['images' => array_map(fn (ISimpleFile $file) => $file->getContent(), $files)];
				}
			};
			$newProviders[$newProvider->getId()] = $newProvider;
		}

		return $newProviders;
	}


	/**
	 * @return IProvider[]
	 */
	private function _getSpeechToTextProviders(): array {
		$oldProviders = $this->speechToTextManager->getProviders();
		$newProviders = [];
		foreach ($oldProviders as $oldProvider) {
			$newProvider = new class($oldProvider, $this->rootFolder, $this->appData) implements IProvider, ISynchronousProvider {
				private ISpeechToTextProvider $provider;
				private IAppData $appData;

				private IRootFolder $rootFolder;

				public function __construct(ISpeechToTextProvider $provider, IRootFolder $rootFolder, IAppData $appData) {
					$this->provider = $provider;
					$this->rootFolder = $rootFolder;
					$this->appData = $appData;
				}

				public function getId(): string {
					if ($this->provider instanceof ISpeechToTextProviderWithId) {
						return Manager::LEGACY_PREFIX_SPEECHTOTEXT . $this->provider->getId();
					}
					return Manager::LEGACY_PREFIX_SPEECHTOTEXT . $this->provider::class;
				}

				public function getName(): string {
					return $this->provider->getName();
				}

				public function getTaskTypeId(): string {
					return AudioToText::ID;
				}

				public function getExpectedRuntime(): int {
					return 60;
				}

				public function getOptionalInputShape(): array {
					return [];
				}

				public function getOptionalOutputShape(): array {
					return [];
				}

				public function process(?string $userId, array $input): array {
					try {
						$folder = $this->appData->getFolder('audio2text');
					} catch(\OCP\Files\NotFoundException) {
						$folder = $this->appData->newFolder('audio2text');
					}
					/** @var SimpleFile $simpleFile */
					$simpleFile = $folder->newFile(time() . '-' . rand(0, 100000), $input['input']->getContent());
					$id = $simpleFile->getId();
					/** @var File $file */
					$file = current($this->rootFolder->getById($id));
					if ($this->provider instanceof ISpeechToTextProviderWithUserId) {
						$this->provider->setUserId($userId);
					}
					try {
						$result = $this->provider->transcribeFile($file);
					} catch (\RuntimeException $e) {
						throw new ProcessingException($e->getMessage(), 0, $e);
					}
					return ['output' => $result];
				}
			};
			$newProviders[$newProvider->getId()] = $newProvider;
		}

		return $newProviders;
	}

	/**
	 * @return IProvider[]
	 */
	private function _getProviders(): array {
		$context = $this->coordinator->getRegistrationContext();

		if ($context === null) {
			return [];
		}

		$providers = [];

		foreach ($context->getTaskProcessingProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				/** @var IProvider $provider */
				$provider = $this->serverContainer->get($class);
				if (isset($providers[$provider->getId()])) {
					$this->logger->warning('Task processing provider ' . $class . ' is using ID ' . $provider->getId() . ' which is already used by ' . $providers[$provider->getId()]::class);
				}
				$providers[$provider->getId()] = $provider;
			} catch (\Throwable $e) {
				$this->logger->error('Failed to load task processing provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		$providers += $this->_getTextProcessingProviders() + $this->_getTextToImageProviders() + $this->_getSpeechToTextProviders();

		return $providers;
	}

	/**
	 * @return ITaskType[]
	 */
	private function _getTaskTypes(): array {
		$context = $this->coordinator->getRegistrationContext();

		if ($context === null) {
			return [];
		}

		// Default task types
		$taskTypes = [
			\OCP\TaskProcessing\TaskTypes\TextToText::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToText::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextTopics::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextTopics::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextHeadline::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextHeadline::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextSummary::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextSummary::class),
			\OCP\TaskProcessing\TaskTypes\TextToImage::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToImage::class),
			\OCP\TaskProcessing\TaskTypes\AudioToText::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\AudioToText::class),
		];

		foreach ($context->getTaskProcessingTaskTypes() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				/** @var ITaskType $provider */
				$taskType = $this->serverContainer->get($class);
				if (isset($taskTypes[$taskType->getId()])) {
					$this->logger->warning('Task processing task type ' . $class . ' is using ID ' . $taskType->getId() . ' which is already used by ' . $taskTypes[$taskType->getId()]::class);
				}
				$taskTypes[$taskType->getId()] = $taskType;
			} catch (\Throwable $e) {
				$this->logger->error('Failed to load task processing task type ' . $class, [
					'exception' => $e,
				]);
			}
		}

		$taskTypes += $this->_getTextProcessingTaskTypes();

		return $taskTypes;
	}

	/**
	 * @param string $taskType
	 * @return IProvider
	 * @throws \OCP\TaskProcessing\Exception\Exception
	 */
	private function _getPreferredProvider(string $taskType) {
		$providers = $this->getProviders();
		foreach ($providers as $provider) {
			if ($provider->getTaskTypeId() === $taskType) {
				return $provider;
			}
		}
		throw new \OCP\TaskProcessing\Exception\Exception('No matching provider found');
	}

	/**
	 * @param ShapeDescriptor[] $spec
	 * @param array $io
	 * @return void
	 * @throws ValidationException
	 */
	private function validateInput(array $spec, array $io, bool $optional = false): void {
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($io[$key])) {
				if ($optional) {
					continue;
				}
				throw new ValidationException('Missing key: "' . $key . '"');
			}
			try {
				$type->validateInput($io[$key]);
			} catch (ValidationException $e) {
				throw new ValidationException('Failed to validate input key "' . $key . '": ' . $e->getMessage());
			}
		}
	}

	/**
	 * @param ShapeDescriptor[] $spec
	 * @param array $io
	 * @param bool $optional
	 * @return void
	 * @throws ValidationException
	 */
	private function validateOutput(array $spec, array $io, bool $optional = false): void {
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($io[$key])) {
				if ($optional) {
					continue;
				}
				throw new ValidationException('Missing key: "' . $key . '"');
			}
			try {
				$type->validateOutput($io[$key]);
			} catch (ValidationException $e) {
				throw new ValidationException('Failed to validate output key "' . $key . '": ' . $e->getMessage());
			}
		}
	}

	/**
	 * @param array<array-key, T> $array The array to filter
	 * @param ShapeDescriptor[] ...$specs the specs that define which keys to keep
	 * @return array<array-key, T>
	 * @psalm-template T
	 */
	private function removeSuperfluousArrayKeys(array $array, ...$specs): array {
		$keys = array_unique(array_reduce($specs, fn ($carry, $spec) => $carry + array_keys($spec), []));
		$values = array_map(fn (string $key) => $array[$key], $keys);
		return array_combine($keys, $values);
	}

	public function hasProviders(): bool {
		return count($this->getProviders()) !== 0;
	}

	public function getProviders(): array {
		if ($this->providers === null) {
			$this->providers = $this->_getProviders();
		}

		return $this->providers;
	}

	public function getAvailableTaskTypes(): array {
		if ($this->availableTaskTypes === null) {
			$taskTypes = $this->_getTaskTypes();
			$providers = $this->getProviders();

			$availableTaskTypes = [];
			foreach ($providers as $provider) {
				if (!isset($taskTypes[$provider->getTaskTypeId()])) {
					continue;
				}
				$taskType = $taskTypes[$provider->getTaskTypeId()];
				$availableTaskTypes[$provider->getTaskTypeId()] = [
					'name' => $taskType->getName(),
					'description' => $taskType->getDescription(),
					'inputShape' => $taskType->getInputShape(),
					'optionalInputShape' => $provider->getOptionalInputShape(),
					'outputShape' => $taskType->getOutputShape(),
					'optionalOutputShape' => $provider->getOptionalOutputShape(),
				];
			}

			$this->availableTaskTypes = $availableTaskTypes;
		}

		return $this->availableTaskTypes;
	}

	public function canHandleTask(Task $task): bool {
		return isset($this->getAvailableTaskTypes()[$task->getTaskTypeId()]);
	}

	public function scheduleTask(Task $task): void {
		if (!$this->canHandleTask($task)) {
			throw new \OCP\TaskProcessing\Exception\PreConditionNotMetException('No task processing provider is installed that can handle this task type: ' . $task->getTaskTypeId());
		}
		$taskTypes = $this->getAvailableTaskTypes();
		$inputShape = $taskTypes[$task->getTaskTypeId()]['inputShape'];
		$optionalInputShape = $taskTypes[$task->getTaskTypeId()]['optionalInputShape'];
		// validate input
		$this->validateInput($inputShape, $task->getInput());
		$this->validateInput($optionalInputShape, $task->getInput(), true);
		// authenticate access to mentioned files
		$ids = [];
		foreach ($inputShape + $optionalInputShape as $key => $descriptor) {
			if (in_array(EShapeType::getScalarType($descriptor->getShapeType()), [EShapeType::File, EShapeType::Image, EShapeType::Audio, EShapeType::Video], true)) {
				/** @var list<int>|int $inputSlot */
				$inputSlot = $task->getInput()[$key];
				if (is_array($inputSlot)) {
					$ids += $inputSlot;
				} else {
					$ids[] = $inputSlot;
				}
			}
		}
		foreach ($ids as $fileId) {
			$node = $this->rootFolder->getFirstNodeById($fileId);
			if ($node === null) {
				$node = $this->rootFolder->getFirstNodeByIdInPath($fileId, '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
				if ($node === null) {
					throw new ValidationException('Could not find file ' . $fileId);
				}
			}
			/** @var array{users:array<string,array{node_id:int, node_path: string}>, remote: array<string,array{node_id:int, node_path: string}>, mail: array<string,array{node_id:int, node_path: string}>} $accessList */
			$accessList = $this->shareManager->getAccessList($node, true, true);
			$userIds = array_map(fn ($id) => strval($id), array_keys($accessList['users']));
			if (!in_array($task->getUserId(), $userIds)) {
				throw new UnauthorizedException('User ' . $task->getUserId() . ' does not have access to file ' . $fileId);
			}
		}
		// remove superfluous keys and set input
		$task->setInput($this->removeSuperfluousArrayKeys($task->getInput(), $inputShape, $optionalInputShape));
		$task->setStatus(Task::STATUS_SCHEDULED);
		$provider = $this->_getPreferredProvider($task->getTaskTypeId());
		// calculate expected completion time
		$completionExpectedAt = new \DateTime('now');
		$completionExpectedAt->add(new \DateInterval('PT'.$provider->getExpectedRuntime().'S'));
		$task->setCompletionExpectedAt($completionExpectedAt);
		// create a db entity and insert into db table
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		$this->taskMapper->insert($taskEntity);
		// make sure the scheduler knows the id
		$task->setId($taskEntity->getId());
		// schedule synchronous job if the provider is synchronous
		if ($provider instanceof ISynchronousProvider) {
			$this->jobList->add(SynchronousBackgroundJob::class, null);
		}
	}

	public function deleteTask(Task $task): void {
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		$this->taskMapper->delete($taskEntity);
	}

	public function getTask(int $id): Task {
		try {
			$taskEntity = $this->taskMapper->find($id);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Couldn\'t find task with id ' . $id, 0, $e);
		} catch (MultipleObjectsReturnedException|\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the task', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding the task', 0, $e);
		}
	}

	public function cancelTask(int $id): void {
		$task = $this->getTask($id);
		$task->setStatus(Task::STATUS_CANCELLED);
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		try {
			$this->taskMapper->update($taskEntity);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the task', 0, $e);
		}
	}

	public function setTaskProgress(int $id, float $progress): bool {
		// TODO: Not sure if we should rather catch the exceptions of getTask here and fail silently
		$task = $this->getTask($id);
		if ($task->getStatus() === Task::STATUS_CANCELLED) {
			return false;
		}
		$task->setStatus(Task::STATUS_RUNNING);
		$task->setProgress($progress);
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		try {
			$this->taskMapper->update($taskEntity);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the task', 0, $e);
		}
		return true;
	}

	public function setTaskResult(int $id, ?string $error, ?array $result): void {
		// TODO: Not sure if we should rather catch the exceptions of getTask here and fail silently
		$task = $this->getTask($id);
		if ($task->getStatus() === Task::STATUS_CANCELLED) {
			$this->logger->info('A TaskProcessing ' . $task->getTaskTypeId() . ' task with id ' . $id . ' finished but was cancelled in the mean time. Moving on without storing result.');
			return;
		}
		if ($error !== null) {
			$task->setStatus(Task::STATUS_FAILED);
			$task->setErrorMessage($error);
			$this->logger->warning('A TaskProcessing ' . $task->getTaskTypeId() . ' task with id ' . $id . ' failed with the following message: ' . $error);
		} elseif ($result !== null) {
			$taskTypes = $this->getAvailableTaskTypes();
			$outputShape = $taskTypes[$task->getTaskTypeId()]['outputShape'];
			$optionalOutputShape = $taskTypes[$task->getTaskTypeId()]['optionalOutputShape'];
			try {
				// validate output
				$this->validateOutput($outputShape, $result);
				$this->validateOutput($optionalOutputShape, $result, true);
				$output = $this->removeSuperfluousArrayKeys($result, $outputShape, $optionalOutputShape);
				// extract raw data and put it in files, replace it with file ids
				$output = $this->encapsulateOutputFileData($output, $outputShape, $optionalOutputShape);
				$task->setOutput($output);
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_SUCCESSFUL);
			} catch (ValidationException $e) {
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_FAILED);
				$error = 'The task was processed successfully but the provider\'s output doesn\'t pass validation against the task type\'s outputShape spec and/or the provider\'s own optionalOutputShape spec';
				$task->setErrorMessage($error);
				$this->logger->error($error, ['exception' => $e]);
			} catch (NotPermittedException $e) {
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_FAILED);
				$error = 'The task was processed successfully but storing the output in a file failed';
				$task->setErrorMessage($error);
				$this->logger->error($error, ['exception' => $e]);

			}
		}
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		try {
			$this->taskMapper->update($taskEntity);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the task', 0, $e);
		}
		if ($task->getStatus() === Task::STATUS_SUCCESSFUL) {
			$event = new TaskSuccessfulEvent($task);
		} else {
			$event = new TaskFailedEvent($task, $error);
		}
		$this->dispatcher->dispatchTyped($event);
	}

	public function getNextScheduledTask(?string $taskTypeId = null): Task {
		try {
			$taskEntity = $this->taskMapper->findOldestScheduledByType($taskTypeId);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new \OCP\TaskProcessing\Exception\NotFoundException('Could not find the task', 0, $e);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the task', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding the task', 0, $e);
		}
	}

	/**
	 * Takes task input or output data and replaces fileIds with base64 data
	 *
	 * @param array<array-key, list<numeric|string>|numeric|string> $input
	 * @param ShapeDescriptor[] ...$specs the specs
	 * @return array<array-key, list<File|numeric|string>|numeric|string|File>
	 * @throws GenericFileException
	 * @throws LockedException
	 * @throws NotPermittedException
	 * @throws ValidationException
	 */
	public function fillInputFileData(array $input, ...$specs): array {
		$newInputOutput = [];
		$spec = array_reduce($specs, fn ($carry, $spec) => $carry + $spec, []);
		foreach($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($input[$key])) {
				continue;
			}
			if (!in_array(EShapeType::getScalarType($type), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
				$newInputOutput[$key] = $input[$key];
				continue;
			}
			if ($type->value < 10) {
				$node = $this->rootFolder->getFirstNodeById((int)$input[$key]);
				if ($node === null) {
					$node = $this->rootFolder->getFirstNodeByIdInPath((int)$input[$key], '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
					if (!$node instanceof File) {
						throw new ValidationException('File id given for key "' . $key . '" is not a file');
					}
				} elseif (!$node instanceof File) {
					throw new ValidationException('File id given for key "' . $key . '" is not a file');
				}
				// TODO: Validate if userId has access to this file
				$newInputOutput[$key] = $node;
			} else {
				$newInputOutput[$key] = [];
				foreach ($input[$key] as $item) {
					$node = $this->rootFolder->getFirstNodeById((int)$input[$key]);
					if ($node === null) {
						$node = $this->rootFolder->getFirstNodeByIdInPath((int)$input[$key], '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
						if (!$node instanceof File) {
							throw new ValidationException('File id given for key "' . $key . '" is not a file');
						}
					} elseif (!$node instanceof File) {
						throw new ValidationException('File id given for key "' . $key . '" is not a file');
					}
					// TODO: Validate if userId has access to this file
					$newInputOutput[$key][] = $node;
				}
			}
		}
		return $newInputOutput;
	}

	public function getUserTask(int $id, ?string $userId): Task {
		try {
			$taskEntity = $this->taskMapper->findByIdAndUser($id, $userId);
			return $taskEntity->toPublicTask();
		} catch (DoesNotExistException $e) {
			throw new \OCP\TaskProcessing\Exception\NotFoundException('Could not find the task', 0, $e);
		} catch (MultipleObjectsReturnedException|\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the task', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding the task', 0, $e);
		}
	}

	public function getUserTasksByApp(?string $userId, string $appId, ?string $identifier = null): array {
		try {
			$taskEntities = $this->taskMapper->findUserTasksByApp($userId, $appId, $identifier);
			return array_map(fn ($taskEntity): Task => $taskEntity->toPublicTask(), $taskEntities);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding a task', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding a task', 0, $e);
		}
	}

	/**
	 *Takes task input or output and replaces base64 data with file ids
	 *
	 * @param array $output
	 * @param ShapeDescriptor[] ...$specs the specs that define which keys to keep
	 * @return array
	 * @throws NotPermittedException
	 */
	public function encapsulateOutputFileData(array $output, ...$specs): array {
		$newOutput = [];
		try {
			$folder = $this->appData->getFolder('TaskProcessing');
		} catch (\OCP\Files\NotFoundException) {
			$folder = $this->appData->newFolder('TaskProcessing');
		}
		$spec = array_reduce($specs, fn ($carry, $spec) => $carry + $spec, []);
		foreach($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($output[$key])) {
				continue;
			}
			if (!in_array(EShapeType::getScalarType($type), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
				$newOutput[$key] = $output[$key];
				continue;
			}
			if ($type->value < 10) {
				/** @var SimpleFile $file */
				$file = $folder->newFile((string) rand(0, 10000000), $output[$key]);
				$newOutput[$key] = $file->getId(); // polymorphic call to SimpleFile
			} else {
				$newOutput = [];
				foreach ($output[$key] as $item) {
					/** @var SimpleFile $file */
					$file = $folder->newFile((string) rand(0, 10000000), $item);
					$newOutput[$key][] = $file->getId();
				}
			}
		}
		return $newOutput;
	}

	/**
	 * @param Task $task
	 * @return array<array-key, list<numeric|string|File>|numeric|string|File>
	 * @throws GenericFileException
	 * @throws LockedException
	 * @throws NotPermittedException
	 * @throws ValidationException
	 */
	public function prepareInputData(Task $task): array {
		$taskTypes = $this->getAvailableTaskTypes();
		$inputShape = $taskTypes[$task->getTaskTypeId()]['inputShape'];
		$optionalInputShape = $taskTypes[$task->getTaskTypeId()]['optionalInputShape'];
		$input = $task->getInput();
		// validate input, again for good measure (should have been validated in scheduleTask)
		$this->validateInput($inputShape, $input);
		$this->validateInput($optionalInputShape, $input, true);
		$input = $this->removeSuperfluousArrayKeys($input, $inputShape, $optionalInputShape);
		$input = $this->fillInputFileData($input, $inputShape, $optionalInputShape);
		return $input;
	}
}
