<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Conversion;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\SystemConfig;
use OCP\Files\Conversion\ConversionMimeTuple;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\Conversion\IConversionProvider;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\ITempManager;
use OCP\PreConditionNotMetException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ConversionManager implements IConversionManager {
	/** @var string[] */
	private array $preferredApps = [
		'richdocuments',
	];

	/** @var IConversionProvider[] */
	private array $preferredProviders = [];

	/** @var IConversionProvider[] */
	private array $providers = [];

	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $serverContainer,
		private IRootFolder $rootFolder,
		private ITempManager $tempManager,
		private LoggerInterface $logger,
		private SystemConfig $config,
	) {
	}

	public function hasProviders(): bool {
		$context = $this->coordinator->getRegistrationContext();
		return !empty($context->getFileConversionProviders());
	}

	public function getMimeTypes(): array {
		$mimeTypes = [];

		foreach ($this->getProviders() as $provider) {
			$mimeTypes[] = $provider->getSupportedMimetypes();
		}

		/** @var list<ConversionMimeTuple> */
		$mimeTypes = array_merge(...$mimeTypes);
		return $mimeTypes;
	}

	public function convert(File $file, string $targetMimeType, ?string $destination = null): string {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No file conversion providers available');
		}

		// Operate in mebibytes
		$fileSize = $file->getSize() / (1024 * 1024);
		$threshold = $this->config->getValue('max_file_conversion_filesize', 100);
		if ($fileSize > $threshold) {
			throw new GenericFileException('File is too large to convert');
		}

		$fileMimeType = $file->getMimetype();
		$validProvider = $this->getValidProvider($fileMimeType, $targetMimeType);

		if ($validProvider !== null) {
			$convertedFile = $validProvider->convertFile($file, $targetMimeType);

			if ($destination !== null) {
				$convertedFile = $this->writeToDestination($destination, $convertedFile);
				return $convertedFile->getPath();
			}

			$tmp = $this->tempManager->getTemporaryFile();
			file_put_contents($tmp, $convertedFile);

			return $tmp;
		}

		throw new RuntimeException('Could not convert file');
	}

	public function getProviders(): array {
		if (count($this->providers) > 0) {
			return $this->providers;
		}

		$context = $this->coordinator->getRegistrationContext();
		foreach ($context->getFileConversionProviders() as $providerRegistration) {
			$class = $providerRegistration->getService();
			$appId = $providerRegistration->getAppId();

			try {
				if (in_array($appId, $this->preferredApps)) {
					$this->preferredProviders[$class] = $this->serverContainer->get($class);
					continue;
				}
				
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable $e) {
				$this->logger->error('Failed to load file conversion provider ' . $class, [
					'exception' => $e,
				]);
			}
		}

		return array_merge([], $this->preferredProviders, $this->providers);
	}

	private function writeToDestination(string $destination, mixed $content): File {
		return $this->rootFolder->newFile($destination, $content);
	}

	private function getValidProvider(string $fileMimeType, string $targetMimeType): ?IConversionProvider {
		$validProvider = null;
		foreach ($this->getProviders() as $provider) {
			$suitableMimeTypes = array_filter(
				$provider->getSupportedMimeTypes(),
				function (ConversionMimeTuple $mimeTuple) use ($fileMimeType, $targetMimeType) {
					['from' => $from, 'to' => $to] = $mimeTuple->jsonSerialize();
					
					$supportsTargetMimeType = in_array($targetMimeType, array_column($to, 'mime'));
					return ($from === $fileMimeType) && $supportsTargetMimeType;
				}
			);

			if (!empty($suitableMimeTypes)) {
				$validProvider = $provider;
				break;
			}
		}

		return $validProvider;
	}
}
