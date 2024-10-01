<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\SpeechToText;

use InvalidArgumentException;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\SpeechToText\ISpeechToTextProvider;
use OCP\SpeechToText\ISpeechToTextProviderWithId;
use OCP\SpeechToText\ISpeechToTextProviderWithUserId;
use OCP\TaskProcessing\IManager as ITaskProcessingManager;
use OCP\TaskProcessing\Task;
use OCP\TaskProcessing\TaskTypes\AudioToText;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class SpeechToTextManager implements ISpeechToTextManager {
	/** @var ?ISpeechToTextProvider[] */
	private ?array $providers = null;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private IConfig $config,
		private IUserSession $userSession,
		private ITaskProcessingManager $taskProcessingManager,
	) {
	}

	public function getProviders(): array {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return [];
		}

		if ($this->providers !== null) {
			return $this->providers;
		}

		$this->providers = [];

		foreach ($context->getSpeechToTextProviders() as $providerServiceRegistration) {
			$class = $providerServiceRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable $e) {
				$this->logger->error('Failed to load SpeechToText provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		return $this->providers;
	}

	public function hasProviders(): bool {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			return false;
		}
		return !empty($context->getSpeechToTextProviders());
	}

	public function scheduleFileTranscription(File $file, ?string $userId, string $appId): void {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No SpeechToText providers have been registered');
		}
		try {
			$this->jobList->add(TranscriptionJob::class, [
				'fileId' => $file->getId(),
				'owner' => $file->getOwner()->getUID(),
				'userId' => $userId,
				'appId' => $appId,
			]);
		} catch (NotFoundException|InvalidPathException $e) {
			throw new InvalidArgumentException('Invalid file provided for file transcription: ' . $e->getMessage());
		}
	}

	public function cancelScheduledFileTranscription(File $file, ?string $userId, string $appId): void {
		try {
			$jobArguments = [
				'fileId' => $file->getId(),
				'owner' => $file->getOwner()->getUID(),
				'userId' => $userId,
				'appId' => $appId,
			];
			if (!$this->jobList->has(TranscriptionJob::class, $jobArguments)) {
				$this->logger->debug('Failed to cancel a Speech-to-text job for file ' . $file->getId() . '. No related job was found.');
				return;
			}
			$this->jobList->remove(TranscriptionJob::class, $jobArguments);
		} catch (NotFoundException|InvalidPathException $e) {
			throw new InvalidArgumentException('Invalid file provided to cancel file transcription: ' . $e->getMessage());
		}
	}

	public function transcribeFile(File $file, ?string $userId = null, string $appId = 'core'): string {
		// try to run a TaskProcessing core:audio2text task
		// this covers scheduling as well because OC\SpeechToText\TranscriptionJob calls this method
		try {
			if (isset($this->taskProcessingManager->getAvailableTaskTypes()['core:audio2text'])) {
				$taskProcessingTask = new Task(
					AudioToText::ID,
					['input' => $file->getId()],
					$appId,
					$userId,
					'from-SpeechToTextManager||' . $file->getId() . '||' . ($userId ?? '') . '||' . $appId,
				);
				$resultTask = $this->taskProcessingManager->runTask($taskProcessingTask);
				if ($resultTask->getStatus() === Task::STATUS_SUCCESSFUL) {
					$output = $resultTask->getOutput();
					if (isset($output['output']) && is_string($output['output'])) {
						return $output['output'];
					}
				}
			}
		} catch (Throwable $e) {
			throw new RuntimeException('Failed to run a Speech-to-text job from STTManager with TaskProcessing for file ' . $file->getId(), 0, $e);
		}

		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No SpeechToText providers have been registered');
		}

		$providers = $this->getProviders();

		$json = $this->config->getAppValue('core', 'ai.stt_provider', '');
		if ($json !== '') {
			$classNameOrId = json_decode($json, true);
			$provider = current(array_filter($providers, function ($provider) use ($classNameOrId) {
				if ($provider instanceof ISpeechToTextProviderWithId) {
					return $provider->getId() === $classNameOrId;
				}
				return $provider::class === $classNameOrId;
			}));
			if ($provider !== false) {
				$providers = [$provider];
			}
		}

		foreach ($providers as $provider) {
			try {
				if ($provider instanceof ISpeechToTextProviderWithUserId) {
					$provider->setUserId($this->userSession->getUser()?->getUID());
				}
				return $provider->transcribeFile($file);
			} catch (\Throwable $e) {
				$this->logger->info('SpeechToText transcription using provider ' . $provider->getName() . ' failed', ['exception' => $e]);
				throw new RuntimeException('SpeechToText transcription using provider "' . $provider->getName() . '" failed: ' . $e->getMessage());
			}
		}

		throw new RuntimeException('Could not transcribe file');
	}
}
