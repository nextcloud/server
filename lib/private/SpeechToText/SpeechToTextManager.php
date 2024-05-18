<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

	public function transcribeFile(File $file): string {
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
