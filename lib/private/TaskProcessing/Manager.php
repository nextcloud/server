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
use OCP\IServerContainer;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\ISpeechToTextProvider;
use OCP\SpeechToText\ISpeechToTextProviderWithId;
use OCP\SpeechToText\ISpeechToTextProviderWithUserId;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Events\TaskFailedEvent;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\Exception\NotFoundException;
use OCP\TaskProcessing\Exception\ProcessingException;
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

				public function getTaskType(): string {
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
	 * @return IProvider[]
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

				public function __construct(string $oldTaskTypeClass) {
					$this->oldTaskTypeClass = $oldTaskTypeClass;
					$this->oldTaskType = \OCP\Server::get($oldTaskTypeClass);
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
					return ['input' => EShapeType::Text];
				}

				public function getOutputShape(): array {
					return ['output' => EShapeType::Text];
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

				public function getTaskType(): string {
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
					try {
						$folder = $folder->getFolder((string) rand(1, 100000));
					} catch(\OCP\Files\NotFoundException) {
						$folder = $folder->newFolder((string) rand(1, 100000));
					}
					$resources = [];
					$files = [];
					for ($i = 0; $i < $input['numberOfImages']; $i++) {
						$file = $folder->newFile((string) $i);
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
					return ['images' => array_map(fn (File $file) => base64_encode($file->getContent()), $files)];
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

				public function getTaskType(): string {
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
					try {
						$folder = $folder->getFolder((string) rand(1, 100000));
					} catch(\OCP\Files\NotFoundException) {
						$folder = $folder->newFolder((string) rand(1, 100000));
					}
					$simpleFile = $folder->newFile((string) rand(0, 100000), base64_decode($input['input']));
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
			if ($provider->getTaskType() === $taskType) {
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
				throw new \OCP\TaskProcessing\Exception\ValidationException('Missing key: "' . $key . '"');
			}
			if ($type === EShapeType::Text && !is_string($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('Non-text item provided for Text key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfTexts && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_string($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-text list item provided for ListOfTexts key: "' . $key . '"');
			}
			if ($type === EShapeType::Number && !is_numeric($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-numeric item provided for Number key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfNumbers && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_numeric($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-numeric list item provided for ListOfNumbers key: "' . $key . '"');
			}
			if ($type === EShapeType::Image && !is_numeric($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-image item provided for Image key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfImages && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_numeric($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-image list item provided for ListOfImages key: "' . $key . '"');
			}
			if ($type === EShapeType::Audio && !is_numeric($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-audio item provided for Audio key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfAudio && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_numeric($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-audio list item provided for ListOfAudio key: "' . $key . '"');
			}
			if ($type === EShapeType::Video && !is_numeric($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-video item provided for Video key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfVideo && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_numeric($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-video list item provided for ListOfTexts key: "' . $key . '"');
			}
			if ($type === EShapeType::File && !is_numeric($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-file item provided for File key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfFiles && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_numeric($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-audio list item provided for ListOfFiles key: "' . $key . '"');
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
				throw new \OCP\TaskProcessing\Exception\ValidationException('Missing key: "' . $key . '"');
			}
			if ($type === EShapeType::Text && !is_string($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('Non-text item provided for Text key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfTexts && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_string($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-text list item provided for ListOfTexts key: "' . $key . '"');
			}
			if ($type === EShapeType::Number && !is_numeric($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-numeric item provided for Number key: "' . $key . '"');
			}
			if ($type === EShapeType::ListOfNumbers && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_numeric($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-numeric list item provided for ListOfNumbers key: "' . $key . '"');
			}
			if ($type === EShapeType::Image && !is_string($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-image item provided for Image key: "' . $key . '". Expecting base64 encoded image data.');
			}
			if ($type === EShapeType::ListOfImages && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_string($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-image list item provided for ListOfImages key: "' . $key . '". Expecting base64 encoded image data.');
			}
			if ($type === EShapeType::Audio && !is_string($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-audio item provided for Audio key: "' . $key . '". Expecting base64 encoded audio data.');
			}
			if ($type === EShapeType::ListOfAudio && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_string($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-audio list item provided for ListOfAudio key: "' . $key . '". Expecting base64 encoded audio data.');
			}
			if ($type === EShapeType::Video && !is_string($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-video item provided for Video key: "' . $key . '". Expecting base64 encoded video data.');
			}
			if ($type === EShapeType::ListOfVideo && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_string($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-video list item provided for ListOfTexts key: "' . $key . '". Expecting base64 encoded video data.');
			}
			if ($type === EShapeType::File && !is_string($io[$key])) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-file item provided for File key: "' . $key . '". Expecting base64 encoded file data.');
			}
			if ($type === EShapeType::ListOfFiles && (!is_array($io[$key]) || count(array_filter($io[$key], fn ($item) => !is_string($item))) > 0)) {
				throw new \OCP\TaskProcessing\Exception\ValidationException('None-audio list item provided for ListOfFiles key: "' . $key . '". Expecting base64 encoded image data.');
			}
		}
	}

	/**
	 * @param array<string,mixed> $array The array to filter
	 * @param array<string, mixed> ...$specs the specs that define which keys to keep
	 * @return array<string, mixed>
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
				if (!isset($taskTypes[$provider->getTaskType()])) {
					continue;
				}
				$taskType = $taskTypes[$provider->getTaskType()];
				$availableTaskTypes[$provider->getTaskType()] = [
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
		return isset($this->getAvailableTaskTypes()[$task->getTaskType()]);
	}

	public function scheduleTask(Task $task): void {
		if (!$this->canHandleTask($task)) {
			throw new PreConditionNotMetException('No task processing provider is installed that can handle this task type: ' . $task->getTaskType());
		}
		$taskTypes = $this->getAvailableTaskTypes();
		$inputShape = $taskTypes[$task->getTaskType()]['inputShape'];
		$optionalInputShape = $taskTypes[$task->getTaskType()]['optionalInputShape'];
		// validate input
		$this->validateInput($inputShape, $task->getInput());
		$this->validateInput($optionalInputShape, $task->getInput(), true);
		// remove superfluous keys and set input
		$task->setInput($this->removeSuperfluousArrayKeys($task->getInput(), $inputShape, $optionalInputShape));
		$task->setStatus(Task::STATUS_SCHEDULED);
		$provider = $this->_getPreferredProvider($task->getTaskType());
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
			$this->logger->info('A TaskProcessing ' . $task->getTaskType() . ' task with id ' . $id . ' finished but was cancelled in the mean time. Moving on without storing result.');
			return;
		}
		if ($error !== null) {
			$task->setStatus(Task::STATUS_FAILED);
			$task->setErrorMessage($error);
			$this->logger->warning('A TaskProcessing ' . $task->getTaskType() . ' task with id ' . $id . ' failed with the following message: ' . $error);
		} elseif ($result !== null) {
			$taskTypes = $this->getAvailableTaskTypes();
			$outputShape = $taskTypes[$task->getTaskType()]['outputShape'];
			$optionalOutputShape = $taskTypes[$task->getTaskType()]['optionalOutputShape'];
			try {
				// validate output
				$this->validateOutput($outputShape, $result);
				$this->validateOutput($optionalOutputShape, $result, true);
				$output = $this->removeSuperfluousArrayKeys($result, $outputShape, $optionalOutputShape);
				// extract base64 data and put it in files, replace it with file ids
				$output = $this->encapsulateInputOutputFileData($output, $outputShape, $optionalOutputShape);
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
			return array_map(fn ($taskEntity) => $taskEntity->toPublicTask(), $taskEntities);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding a task', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding a task', 0, $e);
		}
	}

	/**
	 * Takes task input or output data and replaces fileIds with base64 data
	 *
	 * @param ShapeDescriptor[] ...$specs the specs
	 * @param array $input
	 * @return array
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
			if (!in_array(EShapeType::from($type->value % 10), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
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
			if (!in_array(EShapeType::from($type->value % 10), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
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

	public function prepareInputData(Task $task): array {
		$taskTypes = $this->getAvailableTaskTypes();
		$inputShape = $taskTypes[$task->getTaskType()]['inputShape'];
		$optionalInputShape = $taskTypes[$task->getTaskType()]['optionalInputShape'];
		$input = $task->getInput();
		// validate input, again for good measure (should have been validated in scheduleTask)
		$this->validateInput($inputShape, $input);
		$this->validateInput($optionalInputShape, $input, true);
		$input = $this->removeSuperfluousArrayKeys($input, $inputShape, $optionalInputShape);
		$input = $this->fillInputFileData($input, $inputShape, $optionalInputShape);
		return $input;
	}
}
