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
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IServerContainer;
use OCP\PreConditionNotMetException;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\SpeechToText\ISpeechToTextProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class SpeechToTextManager implements ISpeechToTextManager {
	/** @var ?ISpeechToTextProvider[] */
	private ?array $providers = null;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private IRootFolder $rootFolder,
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

		foreach ($context->getSpeechToTextProviders() as $providerRegistration) {
			$class = $providerRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable $e) {
				$this->logger->error('Failed to load SpeechToText provider ' . $class, [
					'exception' => $e
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
		return !empty($context->getTranslationProviders());
	}

	public function scheduleFileTranscription(string $path, array $context): void {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No SpeechToText providers have been registered');
		}
		try {
			$node = $this->rootFolder->get($path);
		} catch (NotFoundException $e) {
			throw new InvalidArgumentException('File does not exist: ' . $path);
		}
		if (!($node instanceof File)) {
			throw new InvalidArgumentException('Path does not resolve to a file');
		}
		$this->jobList->add(TranscriptionJob::class, [ 'path' => $path, 'context' => $context]);
	}

	public function transcribeFile(string $path): string {
		$provider = current($this->getProviders());
		if (!$provider) {
			throw new PreConditionNotMetException('No SpeechToText providers have been registered');
		}

		try {
			$node = $this->rootFolder->get($path);
			if (!($node instanceof File)) {
				throw new InvalidArgumentException('Path does not resolve to a file');
			}
			return $provider->transcribeFile($node);
		} catch (NotFoundException $e) {
			throw new InvalidArgumentException('File does not exist: ' . $path);
		}
	}
}
