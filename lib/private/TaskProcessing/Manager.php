<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TaskProcessing;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Files\SimpleFS\SimpleFile;
use OC\TaskProcessing\Db\TaskMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IAppData;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\L10N\IFactory;
use OCP\Lock\LockedException;
use OCP\SpeechToText\ISpeechToTextProvider;
use OCP\SpeechToText\ISpeechToTextProviderWithId;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Events\GetTaskProcessingProvidersEvent;
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
use OCP\TaskProcessing\ShapeEnumValue;
use OCP\TaskProcessing\Task;
use OCP\TaskProcessing\TaskTypes\AudioToText;
use OCP\TaskProcessing\TaskTypes\TextToImage;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextHeadline;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use OCP\TaskProcessing\TaskTypes\TextToTextTopics;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class Manager implements IManager {

	public const LEGACY_PREFIX_TEXTPROCESSING = 'legacy:TextProcessing:';
	public const LEGACY_PREFIX_TEXTTOIMAGE = 'legacy:TextToImage:';
	public const LEGACY_PREFIX_SPEECHTOTEXT = 'legacy:SpeechToText:';

	/** @var list<IProvider>|null */
	private ?array $providers = null;

	/**
	 * @var array<array-key,array{name: string, description: string, inputShape: ShapeDescriptor[], inputShapeEnumValues: ShapeEnumValue[][], inputShapeDefaults: array<array-key, numeric|string>, optionalInputShape: ShapeDescriptor[], optionalInputShapeEnumValues: ShapeEnumValue[][], optionalInputShapeDefaults: array<array-key, numeric|string>, outputShape: ShapeDescriptor[], outputShapeEnumValues: ShapeEnumValue[][], optionalOutputShape: ShapeDescriptor[], optionalOutputShapeEnumValues: ShapeEnumValue[][]}>
	 */
	private ?array $availableTaskTypes = null;

	private IAppData $appData;
	private ?array $preferences = null;
	private ?array $providersById = null;

	/** @var ITaskType[]|null */
	private ?array $taskTypes = null;
	private ICache $distributedCache;

	private ?GetTaskProcessingProvidersEvent $eventResult = null;

	public function __construct(
		private IConfig $config,
		private Coordinator $coordinator,
		private IServerContainer $serverContainer,
		private LoggerInterface $logger,
		private TaskMapper $taskMapper,
		private IJobList $jobList,
		private IEventDispatcher $dispatcher,
		IAppDataFactory $appDataFactory,
		private IRootFolder $rootFolder,
		private \OCP\TextToImage\IManager $textToImageManager,
		private IUserMountCache $userMountCache,
		private IClientService $clientService,
		private IAppManager $appManager,
		ICacheFactory $cacheFactory,
	) {
		$this->appData = $appDataFactory->get('core');
		$this->distributedCache = $cacheFactory->createDistributed('task_processing::');
	}


	/**
	 * This is almost a copy of textProcessingManager->getProviders
	 * to avoid a dependency cycle between TextProcessingManager and TaskProcessingManager
	 */
	private function _getRawTextProcessingProviders(): array {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		$providers = [];

		foreach ($context->getTextProcessingProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				$providers[$class] = $this->serverContainer->get($class);
			} catch (\Throwable $e) {
				$this->logger->error('Failed to load Text processing provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		return $providers;
	}

	private function _getTextProcessingProviders(): array {
		$oldProviders = $this->_getRawTextProcessingProviders();
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

				public function process(?string $userId, array $input, callable $reportProgress): array {
					if ($this->provider instanceof \OCP\TextProcessing\IProviderWithUserId) {
						$this->provider->setUserId($userId);
					}
					try {
						return ['output' => $this->provider->process($input['input'])];
					} catch (\RuntimeException $e) {
						throw new ProcessingException($e->getMessage(), 0, $e);
					}
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
			};
			$newProviders[$provider->getId()] = $provider;
		}

		return $newProviders;
	}

	/**
	 * @return ITaskType[]
	 */
	private function _getTextProcessingTaskTypes(): array {
		$oldProviders = $this->_getRawTextProcessingProviders();
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

				public function process(?string $userId, array $input, callable $reportProgress): array {
					try {
						$folder = $this->appData->getFolder('text2image');
					} catch (\OCP\Files\NotFoundException) {
						$folder = $this->appData->newFolder('text2image');
					}
					$resources = [];
					$files = [];
					for ($i = 0; $i < $input['numberOfImages']; $i++) {
						$file = $folder->newFile(time() . '-' . rand(1, 100000) . '-' . $i);
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
					for ($i = 0; $i < $input['numberOfImages']; $i++) {
						if (is_resource($resources[$i])) {
							// If $resource hasn't been closed yet, we'll do that here
							fclose($resources[$i]);
						}
					}
					return ['images' => array_map(fn (ISimpleFile $file) => $file->getContent(), $files)];
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
			};
			$newProviders[$newProvider->getId()] = $newProvider;
		}

		return $newProviders;
	}

	/**
	 * This is almost a copy of SpeechToTextManager->getProviders
	 * to avoid a dependency cycle between SpeechToTextManager and TaskProcessingManager
	 */
	private function _getRawSpeechToTextProviders(): array {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}
		$providers = [];
		foreach ($context->getSpeechToTextProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				$providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|\Throwable $e) {
				$this->logger->error('Failed to load SpeechToText provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		return $providers;
	}

	/**
	 * @return IProvider[]
	 */
	private function _getSpeechToTextProviders(): array {
		$oldProviders = $this->_getRawSpeechToTextProviders();
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

				public function process(?string $userId, array $input, callable $reportProgress): array {
					if ($this->provider instanceof \OCP\SpeechToText\ISpeechToTextProviderWithUserId) {
						$this->provider->setUserId($userId);
					}
					try {
						$result = $this->provider->transcribeFile($input['input']);
					} catch (\RuntimeException $e) {
						throw new ProcessingException($e->getMessage(), 0, $e);
					}
					return ['output' => $result];
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
			};
			$newProviders[$newProvider->getId()] = $newProvider;
		}

		return $newProviders;
	}

	/**
	 * Dispatches the event to collect external providers and task types.
	 * Caches the result within the request.
	 */
	private function dispatchGetProvidersEvent(): GetTaskProcessingProvidersEvent {
		if ($this->eventResult !== null) {
			return $this->eventResult;
		}

		$this->eventResult = new GetTaskProcessingProvidersEvent();
		$this->dispatcher->dispatchTyped($this->eventResult);
		return $this->eventResult ;
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

		$event = $this->dispatchGetProvidersEvent();
		$externalProviders = $event->getProviders();
		foreach ($externalProviders as $provider) {
			if (!isset($providers[$provider->getId()])) {
				$providers[$provider->getId()] = $provider;
			} else {
				$this->logger->info('Skipping external task processing provider with ID ' . $provider->getId() . ' because a local provider with the same ID already exists.');
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

		if ($this->taskTypes !== null) {
			return $this->taskTypes;
		}

		// Default task types
		$taskTypes = [
			\OCP\TaskProcessing\TaskTypes\TextToText::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToText::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextTopics::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextTopics::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextHeadline::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextHeadline::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextSummary::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextSummary::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextFormalization::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextFormalization::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextSimplification::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextSimplification::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextChat::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextChat::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextTranslate::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextTranslate::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextReformulation::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextReformulation::class),
			\OCP\TaskProcessing\TaskTypes\TextToImage::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToImage::class),
			\OCP\TaskProcessing\TaskTypes\AudioToText::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\AudioToText::class),
			\OCP\TaskProcessing\TaskTypes\ContextWrite::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\ContextWrite::class),
			\OCP\TaskProcessing\TaskTypes\GenerateEmoji::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\GenerateEmoji::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextChangeTone::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextChangeTone::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextChatWithTools::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextChatWithTools::class),
			\OCP\TaskProcessing\TaskTypes\ContextAgentInteraction::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\ContextAgentInteraction::class),
			\OCP\TaskProcessing\TaskTypes\TextToTextProofread::ID => \OCP\Server::get(\OCP\TaskProcessing\TaskTypes\TextToTextProofread::class),
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

		$event = $this->dispatchGetProvidersEvent();
		$externalTaskTypes = $event->getTaskTypes();
		foreach ($externalTaskTypes as $taskType) {
			if (isset($taskTypes[$taskType->getId()])) {
				$this->logger->warning('External task processing task type is using ID ' . $taskType->getId() . ' which is already used by a locally registered task type (' . get_class($taskTypes[$taskType->getId()]) . ')');
			}
			$taskTypes[$taskType->getId()] = $taskType;
		}

		$taskTypes += $this->_getTextProcessingTaskTypes();

		$this->taskTypes = $taskTypes;
		return $this->taskTypes;
	}

	/**
	 * @return array
	 */
	private function _getTaskTypeSettings(): array {
		try {
			$json = $this->config->getAppValue('core', 'ai.taskprocessing_type_preferences', '');
			if ($json === '') {
				return [];
			}
			return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			$this->logger->error('Failed to get settings. JSON Error in ai.taskprocessing_type_preferences', ['exception' => $e]);
			$taskTypeSettings = [];
			$taskTypes = $this->_getTaskTypes();
			foreach ($taskTypes as $taskType) {
				$taskTypeSettings[$taskType->getId()] = false;
			};

			return $taskTypeSettings;
		}

	}

	/**
	 * @param ShapeDescriptor[] $spec
	 * @param array<array-key, string|numeric> $defaults
	 * @param array<array-key, ShapeEnumValue[]> $enumValues
	 * @param array $io
	 * @param bool $optional
	 * @return void
	 * @throws ValidationException
	 */
	private static function validateInput(array $spec, array $defaults, array $enumValues, array $io, bool $optional = false): void {
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($io[$key])) {
				if ($optional) {
					continue;
				}
				if (isset($defaults[$key])) {
					if (EShapeType::getScalarType($type) !== $type) {
						throw new ValidationException('Provider tried to set a default value for a non-scalar slot');
					}
					if (EShapeType::isFileType($type)) {
						throw new ValidationException('Provider tried to set a default value for a slot that is not text or number');
					}
					$type->validateInput($defaults[$key]);
					continue;
				}
				throw new ValidationException('Missing key: "' . $key . '"');
			}
			try {
				$type->validateInput($io[$key]);
				if ($type === EShapeType::Enum) {
					if (!isset($enumValues[$key])) {
						throw new ValidationException('Provider did not provide enum values for an enum slot: "' . $key . '"');
					}
					$type->validateEnum($io[$key], $enumValues[$key]);
				}
			} catch (ValidationException $e) {
				throw new ValidationException('Failed to validate input key "' . $key . '": ' . $e->getMessage());
			}
		}
	}

	/**
	 * Takes task input data and replaces fileIds with File objects
	 *
	 * @param array<array-key, list<numeric|string>|numeric|string> $input
	 * @param array<array-key, numeric|string> ...$defaultSpecs the specs
	 * @return array<array-key, list<numeric|string>|numeric|string>
	 */
	public function fillInputDefaults(array $input, ...$defaultSpecs): array {
		$spec = array_reduce($defaultSpecs, fn ($carry, $spec) => array_merge($carry, $spec), []);
		return array_merge($spec, $input);
	}

	/**
	 * @param ShapeDescriptor[] $spec
	 * @param array<array-key, ShapeEnumValue[]> $enumValues
	 * @param array $io
	 * @param bool $optional
	 * @return void
	 * @throws ValidationException
	 */
	private static function validateOutputWithFileIds(array $spec, array $enumValues, array $io, bool $optional = false): void {
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($io[$key])) {
				if ($optional) {
					continue;
				}
				throw new ValidationException('Missing key: "' . $key . '"');
			}
			try {
				$type->validateOutputWithFileIds($io[$key]);
				if (isset($enumValues[$key])) {
					$type->validateEnum($io[$key], $enumValues[$key]);
				}
			} catch (ValidationException $e) {
				throw new ValidationException('Failed to validate output key "' . $key . '": ' . $e->getMessage());
			}
		}
	}

	/**
	 * @param ShapeDescriptor[] $spec
	 * @param array<array-key, ShapeEnumValue[]> $enumValues
	 * @param array $io
	 * @param bool $optional
	 * @return void
	 * @throws ValidationException
	 */
	private static function validateOutputWithFileData(array $spec, array $enumValues, array $io, bool $optional = false): void {
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($io[$key])) {
				if ($optional) {
					continue;
				}
				throw new ValidationException('Missing key: "' . $key . '"');
			}
			try {
				$type->validateOutputWithFileData($io[$key]);
				if (isset($enumValues[$key])) {
					$type->validateEnum($io[$key], $enumValues[$key]);
				}
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
		$keys = array_unique(array_reduce($specs, fn ($carry, $spec) => array_merge($carry, array_keys($spec)), []));
		$keys = array_filter($keys, fn ($key) => array_key_exists($key, $array));
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

	public function getPreferredProvider(string $taskTypeId) {
		try {
			if ($this->preferences === null) {
				$this->preferences = $this->distributedCache->get('ai.taskprocessing_provider_preferences');
				if ($this->preferences === null) {
					$this->preferences = json_decode($this->config->getAppValue('core', 'ai.taskprocessing_provider_preferences', 'null'), associative: true, flags: JSON_THROW_ON_ERROR);
					$this->distributedCache->set('ai.taskprocessing_provider_preferences', $this->preferences, 60 * 3);
				}
			}

			$providers = $this->getProviders();
			if (isset($this->preferences[$taskTypeId])) {
				$providersById = $this->providersById ?? array_reduce($providers, static function (array $carry, IProvider $provider) {
					$carry[$provider->getId()] = $provider;
					return $carry;
				}, []);
				$this->providersById = $providersById;
				if (isset($providersById[$this->preferences[$taskTypeId]])) {
					return $providersById[$this->preferences[$taskTypeId]];
				}
			}
			// By default, use the first available provider
			foreach ($providers as $provider) {
				if ($provider->getTaskTypeId() === $taskTypeId) {
					return $provider;
				}
			}
		} catch (\JsonException $e) {
			$this->logger->warning('Failed to parse provider preferences while getting preferred provider for task type ' . $taskTypeId, ['exception' => $e]);
		}
		throw new \OCP\TaskProcessing\Exception\Exception('No matching provider found');
	}

	public function getAvailableTaskTypes(bool $showDisabled = false): array {
		if ($this->availableTaskTypes === null) {
			$cachedValue = $this->distributedCache->get('available_task_types_v2');
			if ($cachedValue !== null) {
				$this->availableTaskTypes = unserialize($cachedValue);
			}
		}
		// Either we have no cache or showDisabled is turned on, which we don't want to cache, ever.
		if ($this->availableTaskTypes === null || $showDisabled) {
			$taskTypes = $this->_getTaskTypes();
			$taskTypeSettings = $this->_getTaskTypeSettings();

			$availableTaskTypes = [];
			foreach ($taskTypes as $taskType) {
				if ((!$showDisabled) && isset($taskTypeSettings[$taskType->getId()]) && !$taskTypeSettings[$taskType->getId()]) {
					continue;
				}
				try {
					$provider = $this->getPreferredProvider($taskType->getId());
				} catch (\OCP\TaskProcessing\Exception\Exception $e) {
					continue;
				}
				try {
					$availableTaskTypes[$provider->getTaskTypeId()] = [
						'name' => $taskType->getName(),
						'description' => $taskType->getDescription(),
						'optionalInputShape' => $provider->getOptionalInputShape(),
						'inputShapeEnumValues' => $provider->getInputShapeEnumValues(),
						'inputShapeDefaults' => $provider->getInputShapeDefaults(),
						'inputShape' => $taskType->getInputShape(),
						'optionalInputShapeEnumValues' => $provider->getOptionalInputShapeEnumValues(),
						'optionalInputShapeDefaults' => $provider->getOptionalInputShapeDefaults(),
						'outputShape' => $taskType->getOutputShape(),
						'outputShapeEnumValues' => $provider->getOutputShapeEnumValues(),
						'optionalOutputShape' => $provider->getOptionalOutputShape(),
						'optionalOutputShapeEnumValues' => $provider->getOptionalOutputShapeEnumValues(),
					];
				} catch (\Throwable $e) {
					$this->logger->error('Failed to set up TaskProcessing provider ' . $provider::class, ['exception' => $e]);
				}
			}

			if ($showDisabled) {
				// Do not cache showDisabled, ever.
				return $availableTaskTypes;
			}

			$this->availableTaskTypes = $availableTaskTypes;
			$this->distributedCache->set('available_task_types_v2', serialize($this->availableTaskTypes), 60);
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
		$this->prepareTask($task);
		$task->setStatus(Task::STATUS_SCHEDULED);
		$this->storeTask($task);
		// schedule synchronous job if the provider is synchronous
		$provider = $this->getPreferredProvider($task->getTaskTypeId());
		if ($provider instanceof ISynchronousProvider) {
			$this->jobList->add(SynchronousBackgroundJob::class, null);
		}
	}

	public function runTask(Task $task): Task {
		if (!$this->canHandleTask($task)) {
			throw new \OCP\TaskProcessing\Exception\PreConditionNotMetException('No task processing provider is installed that can handle this task type: ' . $task->getTaskTypeId());
		}

		$provider = $this->getPreferredProvider($task->getTaskTypeId());
		if ($provider instanceof ISynchronousProvider) {
			$this->prepareTask($task);
			$task->setStatus(Task::STATUS_SCHEDULED);
			$this->storeTask($task);
			$this->processTask($task, $provider);
			$task = $this->getTask($task->getId());
		} else {
			$this->scheduleTask($task);
			// poll task
			while ($task->getStatus() === Task::STATUS_SCHEDULED || $task->getStatus() === Task::STATUS_RUNNING) {
				sleep(1);
				$task = $this->getTask($task->getId());
			}
		}
		return $task;
	}

	public function processTask(Task $task, ISynchronousProvider $provider): bool {
		try {
			try {
				$input = $this->prepareInputData($task);
			} catch (GenericFileException|NotPermittedException|LockedException|ValidationException|UnauthorizedException $e) {
				$this->logger->warning('Failed to prepare input data for a TaskProcessing task with synchronous provider ' . $provider->getId(), ['exception' => $e]);
				$this->setTaskResult($task->getId(), $e->getMessage(), null);
				return false;
			}
			try {
				$this->setTaskStatus($task, Task::STATUS_RUNNING);
				$output = $provider->process($task->getUserId(), $input, fn (float $progress) => $this->setTaskProgress($task->getId(), $progress));
			} catch (ProcessingException $e) {
				$this->logger->warning('Failed to process a TaskProcessing task with synchronous provider ' . $provider->getId(), ['exception' => $e]);
				$this->setTaskResult($task->getId(), $e->getMessage(), null);
				return false;
			} catch (\Throwable $e) {
				$this->logger->error('Unknown error while processing TaskProcessing task', ['exception' => $e]);
				$this->setTaskResult($task->getId(), $e->getMessage(), null);
				return false;
			}
			$this->setTaskResult($task->getId(), null, $output);
		} catch (NotFoundException $e) {
			$this->logger->info('Could not find task anymore after execution. Moving on.', ['exception' => $e]);
		} catch (Exception $e) {
			$this->logger->error('Failed to report result of TaskProcessing task', ['exception' => $e]);
		}
		return true;
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
		if ($task->getStatus() !== Task::STATUS_SCHEDULED && $task->getStatus() !== Task::STATUS_RUNNING) {
			return;
		}
		$task->setStatus(Task::STATUS_CANCELLED);
		$task->setEndedAt(time());
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		try {
			$this->taskMapper->update($taskEntity);
			$this->runWebhook($task);
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
		// only set the start time if the task is going from scheduled to running
		if ($task->getstatus() === Task::STATUS_SCHEDULED) {
			$task->setStartedAt(time());
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

	public function setTaskResult(int $id, ?string $error, ?array $result, bool $isUsingFileIds = false): void {
		// TODO: Not sure if we should rather catch the exceptions of getTask here and fail silently
		$task = $this->getTask($id);
		if ($task->getStatus() === Task::STATUS_CANCELLED) {
			$this->logger->info('A TaskProcessing ' . $task->getTaskTypeId() . ' task with id ' . $id . ' finished but was cancelled in the mean time. Moving on without storing result.');
			return;
		}
		if ($error !== null) {
			$task->setStatus(Task::STATUS_FAILED);
			$task->setEndedAt(time());
			// truncate error message to 1000 characters
			$task->setErrorMessage(mb_substr($error, 0, 1000));
			$this->logger->warning('A TaskProcessing ' . $task->getTaskTypeId() . ' task with id ' . $id . ' failed with the following message: ' . $error);
		} elseif ($result !== null) {
			$taskTypes = $this->getAvailableTaskTypes();
			$outputShape = $taskTypes[$task->getTaskTypeId()]['outputShape'];
			$outputShapeEnumValues = $taskTypes[$task->getTaskTypeId()]['outputShapeEnumValues'];
			$optionalOutputShape = $taskTypes[$task->getTaskTypeId()]['optionalOutputShape'];
			$optionalOutputShapeEnumValues = $taskTypes[$task->getTaskTypeId()]['optionalOutputShapeEnumValues'];
			try {
				// validate output
				if (!$isUsingFileIds) {
					$this->validateOutputWithFileData($outputShape, $outputShapeEnumValues, $result);
					$this->validateOutputWithFileData($optionalOutputShape, $optionalOutputShapeEnumValues, $result, true);
				} else {
					$this->validateOutputWithFileIds($outputShape, $outputShapeEnumValues, $result);
					$this->validateOutputWithFileIds($optionalOutputShape, $optionalOutputShapeEnumValues, $result, true);
				}
				$output = $this->removeSuperfluousArrayKeys($result, $outputShape, $optionalOutputShape);
				// extract raw data and put it in files, replace it with file ids
				if (!$isUsingFileIds) {
					$output = $this->encapsulateOutputFileData($output, $outputShape, $optionalOutputShape);
				} else {
					$this->validateOutputFileIds($output, $outputShape, $optionalOutputShape);
				}
				// Turn file objects into IDs
				foreach ($output as $key => $value) {
					if ($value instanceof Node) {
						$output[$key] = $value->getId();
					}
					if (is_array($value) && isset($value[0]) && $value[0] instanceof Node) {
						$output[$key] = array_map(fn ($node) => $node->getId(), $value);
					}
				}
				$task->setOutput($output);
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_SUCCESSFUL);
				$task->setEndedAt(time());
			} catch (ValidationException $e) {
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_FAILED);
				$task->setEndedAt(time());
				$error = 'The task was processed successfully but the provider\'s output doesn\'t pass validation against the task type\'s outputShape spec and/or the provider\'s own optionalOutputShape spec';
				$task->setErrorMessage($error);
				$this->logger->error($error, ['exception' => $e, 'output' => $result]);
			} catch (NotPermittedException $e) {
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_FAILED);
				$task->setEndedAt(time());
				$error = 'The task was processed successfully but storing the output in a file failed';
				$task->setErrorMessage($error);
				$this->logger->error($error, ['exception' => $e]);
			} catch (InvalidPathException|\OCP\Files\NotFoundException $e) {
				$task->setProgress(1);
				$task->setStatus(Task::STATUS_FAILED);
				$task->setEndedAt(time());
				$error = 'The task was processed successfully but the result file could not be found';
				$task->setErrorMessage($error);
				$this->logger->error($error, ['exception' => $e]);
			}
		}
		try {
			$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('The task was processed successfully but the provider\'s output could not be encoded as JSON for the database.', 0, $e);
		}
		try {
			$this->taskMapper->update($taskEntity);
			$this->runWebhook($task);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception($e->getMessage());
		}
		if ($task->getStatus() === Task::STATUS_SUCCESSFUL) {
			$event = new TaskSuccessfulEvent($task);
		} else {
			$event = new TaskFailedEvent($task, $error);
		}
		$this->dispatcher->dispatchTyped($event);
	}

	public function getNextScheduledTask(array $taskTypeIds = [], array $taskIdsToIgnore = []): Task {
		try {
			$taskEntity = $this->taskMapper->findOldestScheduledByType($taskTypeIds, $taskIdsToIgnore);
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
	 * Takes task input data and replaces fileIds with File objects
	 *
	 * @param string|null $userId
	 * @param array<array-key, list<numeric|string>|numeric|string> $input
	 * @param ShapeDescriptor[] ...$specs the specs
	 * @return array<array-key, list<File|numeric|string>|numeric|string|File>
	 * @throws GenericFileException|LockedException|NotPermittedException|ValidationException|UnauthorizedException
	 */
	public function fillInputFileData(?string $userId, array $input, ...$specs): array {
		if ($userId !== null) {
			\OC_Util::setupFS($userId);
		}
		$newInputOutput = [];
		$spec = array_reduce($specs, fn ($carry, $spec) => $carry + $spec, []);
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($input[$key])) {
				continue;
			}
			if (!in_array(EShapeType::getScalarType($type), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
				$newInputOutput[$key] = $input[$key];
				continue;
			}
			if (EShapeType::getScalarType($type) === $type) {
				// is scalar
				$node = $this->validateFileId((int)$input[$key]);
				$this->validateUserAccessToFile($input[$key], $userId);
				$newInputOutput[$key] = $node;
			} else {
				// is list
				$newInputOutput[$key] = [];
				foreach ($input[$key] as $item) {
					$node = $this->validateFileId((int)$item);
					$this->validateUserAccessToFile($item, $userId);
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

	public function getUserTasks(?string $userId, ?string $taskTypeId = null, ?string $customId = null): array {
		try {
			$taskEntities = $this->taskMapper->findByUserAndTaskType($userId, $taskTypeId, $customId);
			return array_map(fn ($taskEntity): Task => $taskEntity->toPublicTask(), $taskEntities);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the tasks', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding the tasks', 0, $e);
		}
	}

	public function getTasks(
		?string $userId, ?string $taskTypeId = null, ?string $appId = null, ?string $customId = null,
		?int $status = null, ?int $scheduleAfter = null, ?int $endedBefore = null,
	): array {
		try {
			$taskEntities = $this->taskMapper->findTasks($userId, $taskTypeId, $appId, $customId, $status, $scheduleAfter, $endedBefore);
			return array_map(fn ($taskEntity): Task => $taskEntity->toPublicTask(), $taskEntities);
		} catch (\OCP\DB\Exception $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem finding the tasks', 0, $e);
		} catch (\JsonException $e) {
			throw new \OCP\TaskProcessing\Exception\Exception('There was a problem parsing JSON after finding the tasks', 0, $e);
		}
	}

	public function getUserTasksByApp(?string $userId, string $appId, ?string $customId = null): array {
		try {
			$taskEntities = $this->taskMapper->findUserTasksByApp($userId, $appId, $customId);
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
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($output[$key])) {
				continue;
			}
			if (!in_array(EShapeType::getScalarType($type), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
				$newOutput[$key] = $output[$key];
				continue;
			}
			if (EShapeType::getScalarType($type) === $type) {
				/** @var SimpleFile $file */
				$file = $folder->newFile(time() . '-' . rand(1, 100000), $output[$key]);
				$newOutput[$key] = $file->getId(); // polymorphic call to SimpleFile
			} else {
				$newOutput = [];
				foreach ($output[$key] as $item) {
					/** @var SimpleFile $file */
					$file = $folder->newFile(time() . '-' . rand(1, 100000), $item);
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
	 * @throws ValidationException|UnauthorizedException
	 */
	public function prepareInputData(Task $task): array {
		$taskTypes = $this->getAvailableTaskTypes();
		$inputShape = $taskTypes[$task->getTaskTypeId()]['inputShape'];
		$optionalInputShape = $taskTypes[$task->getTaskTypeId()]['optionalInputShape'];
		$input = $task->getInput();
		$input = $this->removeSuperfluousArrayKeys($input, $inputShape, $optionalInputShape);
		$input = $this->fillInputFileData($task->getUserId(), $input, $inputShape, $optionalInputShape);
		return $input;
	}

	public function lockTask(Task $task): bool {
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		if ($this->taskMapper->lockTask($taskEntity) === 0) {
			return false;
		}
		$task->setStatus(Task::STATUS_RUNNING);
		return true;
	}

	/**
	 * @throws \JsonException
	 * @throws Exception
	 */
	public function setTaskStatus(Task $task, int $status): void {
		$currentTaskStatus = $task->getStatus();
		if ($currentTaskStatus === Task::STATUS_SCHEDULED && $status === Task::STATUS_RUNNING) {
			$task->setStartedAt(time());
		} elseif ($currentTaskStatus === Task::STATUS_RUNNING && ($status === Task::STATUS_FAILED || $status === Task::STATUS_CANCELLED)) {
			$task->setEndedAt(time());
		} elseif ($currentTaskStatus === Task::STATUS_UNKNOWN && $status === Task::STATUS_SCHEDULED) {
			$task->setScheduledAt(time());
		}
		$task->setStatus($status);
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		$this->taskMapper->update($taskEntity);
	}

	/**
	 * Validate input, fill input default values, set completionExpectedAt, set scheduledAt
	 *
	 * @param Task $task
	 * @return void
	 * @throws UnauthorizedException
	 * @throws ValidationException
	 * @throws \OCP\TaskProcessing\Exception\Exception
	 */
	private function prepareTask(Task $task): void {
		$taskTypes = $this->getAvailableTaskTypes();
		$taskType = $taskTypes[$task->getTaskTypeId()];
		$inputShape = $taskType['inputShape'];
		$inputShapeDefaults = $taskType['inputShapeDefaults'];
		$inputShapeEnumValues = $taskType['inputShapeEnumValues'];
		$optionalInputShape = $taskType['optionalInputShape'];
		$optionalInputShapeEnumValues = $taskType['optionalInputShapeEnumValues'];
		$optionalInputShapeDefaults = $taskType['optionalInputShapeDefaults'];
		// validate input
		$this->validateInput($inputShape, $inputShapeDefaults, $inputShapeEnumValues, $task->getInput());
		$this->validateInput($optionalInputShape, $optionalInputShapeDefaults, $optionalInputShapeEnumValues, $task->getInput(), true);
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
			$this->validateFileId($fileId);
			$this->validateUserAccessToFile($fileId, $task->getUserId());
		}
		// remove superfluous keys and set input
		$input = $this->removeSuperfluousArrayKeys($task->getInput(), $inputShape, $optionalInputShape);
		$inputWithDefaults = $this->fillInputDefaults($input, $inputShapeDefaults, $optionalInputShapeDefaults);
		$task->setInput($inputWithDefaults);
		$task->setScheduledAt(time());
		$provider = $this->getPreferredProvider($task->getTaskTypeId());
		// calculate expected completion time
		$completionExpectedAt = new \DateTime('now');
		$completionExpectedAt->add(new \DateInterval('PT' . $provider->getExpectedRuntime() . 'S'));
		$task->setCompletionExpectedAt($completionExpectedAt);
	}

	/**
	 * Store the task in the DB and set its ID in the \OCP\TaskProcessing\Task input param
	 *
	 * @param Task $task
	 * @return void
	 * @throws Exception
	 * @throws \JsonException
	 */
	private function storeTask(Task $task): void {
		// create a db entity and insert into db table
		$taskEntity = \OC\TaskProcessing\Db\Task::fromPublicTask($task);
		$this->taskMapper->insert($taskEntity);
		// make sure the scheduler knows the id
		$task->setId($taskEntity->getId());
	}

	/**
	 * @param array $output
	 * @param ShapeDescriptor[] ...$specs the specs that define which keys to keep
	 * @return array
	 * @throws NotPermittedException
	 */
	private function validateOutputFileIds(array $output, ...$specs): array {
		$newOutput = [];
		$spec = array_reduce($specs, fn ($carry, $spec) => $carry + $spec, []);
		foreach ($spec as $key => $descriptor) {
			$type = $descriptor->getShapeType();
			if (!isset($output[$key])) {
				continue;
			}
			if (!in_array(EShapeType::getScalarType($type), [EShapeType::Image, EShapeType::Audio, EShapeType::Video, EShapeType::File], true)) {
				$newOutput[$key] = $output[$key];
				continue;
			}
			if (EShapeType::getScalarType($type) === $type) {
				// Is scalar file ID
				$newOutput[$key] = $this->validateFileId($output[$key]);
			} else {
				// Is list of file IDs
				$newOutput = [];
				foreach ($output[$key] as $item) {
					$newOutput[$key][] = $this->validateFileId($item);
				}
			}
		}
		return $newOutput;
	}

	/**
	 * @param mixed $id
	 * @return File
	 * @throws ValidationException
	 */
	private function validateFileId(mixed $id): File {
		$node = $this->rootFolder->getFirstNodeById($id);
		if ($node === null) {
			$node = $this->rootFolder->getFirstNodeByIdInPath($id, '/' . $this->rootFolder->getAppDataDirectoryName() . '/');
			if ($node === null) {
				throw new ValidationException('Could not find file ' . $id);
			} elseif (!$node instanceof File) {
				throw new ValidationException('File with id "' . $id . '" is not a file');
			}
		} elseif (!$node instanceof File) {
			throw new ValidationException('File with id "' . $id . '" is not a file');
		}
		return $node;
	}

	/**
	 * @param mixed $fileId
	 * @param string|null $userId
	 * @return void
	 * @throws UnauthorizedException
	 */
	private function validateUserAccessToFile(mixed $fileId, ?string $userId): void {
		if ($userId === null) {
			throw new UnauthorizedException('User does not have access to file ' . $fileId);
		}
		$mounts = $this->userMountCache->getMountsForFileId($fileId);
		$userIds = array_map(fn ($mount) => $mount->getUser()->getUID(), $mounts);
		if (!in_array($userId, $userIds)) {
			throw new UnauthorizedException('User ' . $userId . ' does not have access to file ' . $fileId);
		}
	}

	/**
	 * Make a request to the task's webhookUri if necessary
	 *
	 * @param Task $task
	 */
	private function runWebhook(Task $task): void {
		$uri = $task->getWebhookUri();
		$method = $task->getWebhookMethod();

		if (!$uri || !$method) {
			return;
		}

		if (in_array($method, ['HTTP:GET', 'HTTP:POST', 'HTTP:PUT', 'HTTP:DELETE'], true)) {
			$client = $this->clientService->newClient();
			$httpMethod = preg_replace('/^HTTP:/', '', $method);
			$options = [
				'timeout' => 30,
				'body' => json_encode([
					'task' => $task->jsonSerialize(),
				]),
				'headers' => ['Content-Type' => 'application/json'],
			];
			try {
				$client->request($httpMethod, $uri, $options);
			} catch (ClientException|ServerException $e) {
				$this->logger->warning('Task processing HTTP webhook failed for task ' . $task->getId() . '. Request failed', ['exception' => $e]);
			} catch (\Exception|\Throwable $e) {
				$this->logger->warning('Task processing HTTP webhook failed for task ' . $task->getId() . '. Unknown error', ['exception' => $e]);
			}
		} elseif (str_starts_with($method, 'AppAPI:') && str_starts_with($uri, '/')) {
			$parsedMethod = explode(':', $method, 4);
			if (count($parsedMethod) < 3) {
				$this->logger->warning('Task processing AppAPI webhook failed for task ' . $task->getId() . '. Invalid method: ' . $method);
			}
			[, $exAppId, $httpMethod] = $parsedMethod;
			if (!$this->appManager->isEnabledForAnyone('app_api')) {
				$this->logger->warning('Task processing AppAPI webhook failed for task ' . $task->getId() . '. AppAPI is disabled or not installed.');
				return;
			}
			try {
				$appApiFunctions = \OCP\Server::get(\OCA\AppAPI\PublicFunctions::class);
			} catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
				$this->logger->warning('Task processing AppAPI webhook failed for task ' . $task->getId() . '. Could not get AppAPI public functions.');
				return;
			}
			$exApp = $appApiFunctions->getExApp($exAppId);
			if ($exApp === null) {
				$this->logger->warning('Task processing AppAPI webhook failed for task ' . $task->getId() . '. ExApp ' . $exAppId . ' is missing.');
				return;
			} elseif (!$exApp['enabled']) {
				$this->logger->warning('Task processing AppAPI webhook failed for task ' . $task->getId() . '. ExApp ' . $exAppId . ' is disabled.');
				return;
			}
			$requestParams = [
				'task' => $task->jsonSerialize(),
			];
			$requestOptions = [
				'timeout' => 30,
			];
			$response = $appApiFunctions->exAppRequest($exAppId, $uri, $task->getUserId(), $httpMethod, $requestParams, $requestOptions);
			if (is_array($response) && isset($response['error'])) {
				$this->logger->warning('Task processing AppAPI webhook failed for task ' . $task->getId() . '. Error during request to ExApp(' . $exAppId . '): ', $response['error']);
			}
		}
	}
}
