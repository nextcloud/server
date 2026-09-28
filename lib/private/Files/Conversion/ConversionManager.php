<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Conversion;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\ForbiddenException;
use OC\SystemConfig;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\Conversion\IConversionProvider;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ITempManager;
use OCP\L10N\IFactory;
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

	/** @var list<IConversionProvider> */
	private array $preferredProviders = [];

	/** @var list<IConversionProvider> */
	private array $providers = [];

	private IL10N $l10n;
	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $serverContainer,
		private IRootFolder $rootFolder,
		private ITempManager $tempManager,
		private LoggerInterface $logger,
		private SystemConfig $config,
		IFactory $l10nFactory,
	) {
		$this->l10n = $l10nFactory->get('files');
	}

	public function hasProviders(): bool {
		$context = $this->coordinator->getRegistrationContext();
		return !empty($context->getFileConversionProviders());
	}

	public function getProviders(): array {
		$providers = [];
		foreach ($this->getRegisteredProviders() as $provider) {
			$providers = array_merge($providers, $provider->getSupportedMimeTypes());
		}
		return $providers;
	}

	public function convert(File $file, string $targetMimeType, ?string $destination = null): string {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException($this->l10n->t('No file conversion providers available'));
		}

		// Operate in mebibytes
		$fileSize = $file->getSize() / (1024 * 1024);
		$threshold = $this->config->getValue('max_file_conversion_filesize', 100);
		if ($fileSize > $threshold) {
			throw new GenericFileException($this->l10n->t('File is too large to convert'));
		}

		$fileMimeType = $file->getMimetype();
		$validProvider = $this->getValidProvider($fileMimeType, $targetMimeType);

		if ($validProvider !== null) {
			// Get the target extension given by the provider
			$targetExtension = '';
			foreach ($validProvider->getSupportedMimeTypes() as $mimeProvider) {
				if ($mimeProvider->getTo() === $targetMimeType) {
					$targetExtension = $mimeProvider->getExtension();
					break;
				}
			}
			// If destination not provided, we use the same path
			// as the original file, but with the new extension
			if ($destination === null) {
				$basename = pathinfo($file->getPath(), PATHINFO_FILENAME);
				$parent = $file->getParent();
				$destination = $parent->getFullPath($basename . '.' . $targetExtension);
			}

			// If destination doesn't match the target extension, we throw an error
			if (pathinfo($destination, PATHINFO_EXTENSION) !== $targetExtension) {
				throw new GenericFileException($this->l10n->t('Destination does not match conversion extension'));
			}

			// Check destination before converting
			$this->checkDestination($destination);

			// Convert the file and write it to the destination
			$convertedFile = $validProvider->convertFile($file, $targetMimeType);
			$convertedFile = $this->writeToDestination($destination, $convertedFile);
			return $convertedFile->getPath();
		}

		throw new RuntimeException($this->l10n->t('Could not convert file'));
	}

	/**
	 * @return list<IConversionProvider>
	 */
	private function getRegisteredProviders(): array {
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

		return array_values(array_merge([], $this->preferredProviders, $this->providers));
	}

	private function checkDestination(string $destination): void {
		if (!$this->rootFolder->nodeExists(dirname($destination))) {
			throw new ForbiddenException($this->l10n->t('Destination does not exist'));
		}

		$folder = $this->rootFolder->get(dirname($destination));
		if (!$folder->isCreatable()) {
			throw new ForbiddenException($this->l10n->t('Destination is not creatable'));
		}
	}

	private function writeToDestination(string $destination, mixed $content): File {
		$this->checkDestination($destination);

		if ($this->rootFolder->nodeExists($destination)) {
			$file = $this->rootFolder->get($destination);
			$parent = $file->getParent();

			// Folder permissions is already checked in checkDestination method
			$newName = $parent->getNonExistingName(basename($destination));
			$destination = $parent->getFullPath($newName);
		}

		return $this->rootFolder->newFile($destination, $content);
	}

	private function getValidProvider(string $fileMimeType, string $targetMimeType): ?IConversionProvider {
		foreach ($this->getRegisteredProviders() as $provider) {
			foreach ($provider->getSupportedMimeTypes() as $mimeProvider) {
				if ($mimeProvider->getFrom() === $fileMimeType && $mimeProvider->getTo() === $targetMimeType) {
					return $provider;
				}
			}
		}

		return null;
	}
}
