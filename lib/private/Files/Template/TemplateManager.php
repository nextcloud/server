<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Template;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Files\Cache\Scanner;
use OC\Files\Filesystem;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\GenericFileException;
use OCP\Files\IFilenameValidator;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Template\BeforeGetTemplatesEvent;
use OCP\Files\Template\Field;
use OCP\Files\Template\FileCreatedFromTemplateEvent;
use OCP\Files\Template\ICustomTemplateProvider;
use OCP\Files\Template\ITemplateManager;
use OCP\Files\Template\RegisterTemplateCreatorEvent;
use OCP\Files\Template\Template;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IConfig;
use OCP\IPreview;
use OCP\IServerContainer;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

class TemplateManager implements ITemplateManager {
	private $registeredTypes = [];
	private $types = [];

	/** @var array|null */
	private $providers = null;

	private $serverContainer;
	private $eventDispatcher;
	private $rootFolder;
	private $userManager;
	private $previewManager;
	private $config;
	private $l10n;
	private $logger;
	private $userId;
	private $l10nFactory;
	/** @var Coordinator */
	private $bootstrapCoordinator;

	public function __construct(
		IServerContainer $serverContainer,
		IEventDispatcher $eventDispatcher,
		Coordinator $coordinator,
		IRootFolder $rootFolder,
		IUserSession $userSession,
		IUserManager $userManager,
		IPreview $previewManager,
		IConfig $config,
		IFactory $l10nFactory,
		LoggerInterface $logger,
		private IFilenameValidator $filenameValidator,
	) {
		$this->serverContainer = $serverContainer;
		$this->eventDispatcher = $eventDispatcher;
		$this->bootstrapCoordinator = $coordinator;
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->previewManager = $previewManager;
		$this->config = $config;
		$this->l10nFactory = $l10nFactory;
		$this->l10n = $l10nFactory->get('lib');
		$this->logger = $logger;
		$user = $userSession->getUser();
		$this->userId = $user ? $user->getUID() : null;
	}

	public function registerTemplateFileCreator(callable $callback): void {
		$this->registeredTypes[] = $callback;
	}

	public function getRegisteredProviders(): array {
		if ($this->providers !== null) {
			return $this->providers;
		}

		$context = $this->bootstrapCoordinator->getRegistrationContext();

		$this->providers = [];
		foreach ($context->getTemplateProviders() as $provider) {
			$class = $provider->getService();
			$this->providers[$class] = $this->serverContainer->get($class);
		}
		return $this->providers;
	}

	public function getTypes(): array {
		if (!empty($this->types)) {
			return $this->types;
		}
		$this->eventDispatcher->dispatchTyped(new RegisterTemplateCreatorEvent($this));
		foreach ($this->registeredTypes as $registeredType) {
			$this->types[] = $registeredType();
		}
		return $this->types;
	}

	public function listCreators(): array {
		$types = $this->getTypes();
		usort($types, function (TemplateFileCreator $a, TemplateFileCreator $b) {
			return $a->getOrder() - $b->getOrder();
		});
		return $types;
	}

	public function listTemplates(): array {
		return array_values(array_map(function (TemplateFileCreator $entry) {
			return array_merge($entry->jsonSerialize(), [
				'templates' => $this->getTemplateFiles($entry)
			]);
		}, $this->listCreators()));
	}

	public function listTemplateFields(int $fileId): array {
		foreach ($this->listCreators() as $creator) {
			$fields = $this->getTemplateFields($creator, $fileId);
			if (empty($fields)) {
				continue;
			}

			return $fields;
		}

		return [];
	}

	/**
	 * @param string $filePath
	 * @param string $templateId
	 * @param array $templateFields
	 * @return array
	 * @throws GenericFileException
	 */
	public function createFromTemplate(string $filePath, string $templateId = '', string $templateType = 'user', array $templateFields = []): array {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		try {
			$userFolder->get($filePath);
			throw new GenericFileException($this->l10n->t('File already exists'));
		} catch (NotFoundException $e) {
		}
		try {
			if (!$userFolder->nodeExists(dirname($filePath))) {
				throw new GenericFileException($this->l10n->t('Invalid path'));
			}
			$folder = $userFolder->get(dirname($filePath));
			$template = null;
			if ($templateType === 'user' && $templateId !== '') {
				$template = $userFolder->get($templateId);
			} else {
				$matchingProvider = array_filter($this->getRegisteredProviders(), function (ICustomTemplateProvider $provider) use ($templateType) {
					return $templateType === get_class($provider);
				});
				$provider = array_shift($matchingProvider);
				if ($provider) {
					$template = $provider->getCustomTemplate($templateId);
				}
			}

			$filename = basename($filePath);
			$this->filenameValidator->validateFilename($filename);
			$targetFile = $folder->newFile($filename, ($template instanceof File ? $template->fopen('rb') : null));

			$this->eventDispatcher->dispatchTyped(new FileCreatedFromTemplateEvent($template, $targetFile, $templateFields));
			return $this->formatFile($userFolder->get($filePath));
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new GenericFileException($this->l10n->t('Failed to create file from template'));
		}
	}

	/**
	 * @return Folder
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OC\User\NoUserException
	 */
	private function getTemplateFolder(): Folder {
		if ($this->getTemplatePath() !== '') {
			$path = $this->rootFolder->getUserFolder($this->userId)->get($this->getTemplatePath());
			if ($path instanceof Folder) {
				return $path;
			}
		}
		throw new NotFoundException();
	}

	/**
	 * @return list<Template>
	 */
	private function getTemplateFiles(TemplateFileCreator $type): array {
		$templates = array_merge(
			$this->getProviderTemplates($type),
			$this->getUserTemplates($type)
		);

		$this->eventDispatcher->dispatchTyped(new BeforeGetTemplatesEvent($templates, false));

		return $templates;
	}

	/**
	 * @return list<Template>
	 */
	private function getProviderTemplates(TemplateFileCreator $type): array {
		$templates = [];
		foreach ($this->getRegisteredProviders() as $provider) {
			foreach ($type->getMimetypes() as $mimetype) {
				foreach ($provider->getCustomTemplates($mimetype) as $template) {
					$templates[] = $template;
				}
			}
		}

		return $templates;
	}

	/**
	 * @return list<Template>
	 */
	private function getUserTemplates(TemplateFileCreator $type): array {
		$templates = [];

		try {
			$userTemplateFolder = $this->getTemplateFolder();
		} catch (\Exception $e) {
			return $templates;
		}

		foreach ($type->getMimetypes() as $mimetype) {
			foreach ($userTemplateFolder->searchByMime($mimetype) as $templateFile) {
				$template = new Template(
					'user',
					$this->rootFolder->getUserFolder($this->userId)->getRelativePath($templateFile->getPath()),
					$templateFile
				);
				$template->setHasPreview($this->previewManager->isAvailable($templateFile));
				$templates[] = $template;
			}
		}

		return $templates;
	}

	/*
	 * @return list<Field>
	 */
	private function getTemplateFields(TemplateFileCreator $type, int $fileId): array {
		$providerTemplates = $this->getProviderTemplates($type);
		$userTemplates = $this->getUserTemplates($type);

		$matchedTemplates = array_filter(
			array_merge($providerTemplates, $userTemplates),
			function (Template $template) use ($fileId) {
				return $template->jsonSerialize()['fileid'] === $fileId;
			});

		if (empty($matchedTemplates)) {
			return [];
		}

		$this->eventDispatcher->dispatchTyped(new BeforeGetTemplatesEvent($matchedTemplates, true));

		return array_values(array_map(function (Template $template) {
			return $template->jsonSerialize()['fields'] ?? [];
		}, $matchedTemplates));
	}

	/**
	 * @param Node|File $file
	 * @return array
	 * @throws NotFoundException
	 * @throws \OCP\Files\InvalidPathException
	 */
	private function formatFile(Node $file): array {
		return [
			'basename' => $file->getName(),
			'etag' => $file->getEtag(),
			'fileid' => $file->getId(),
			'filename' => $this->rootFolder->getUserFolder($this->userId)->getRelativePath($file->getPath()),
			'lastmod' => $file->getMTime(),
			'mime' => $file->getMimetype(),
			'size' => $file->getSize(),
			'type' => $file->getType(),
			'hasPreview' => $this->previewManager->isAvailable($file),
			'permissions' => $file->getPermissions(),
		];
	}

	public function hasTemplateDirectory(): bool {
		try {
			$this->getTemplateFolder();
			return true;
		} catch (\Exception $e) {
		}
		return false;
	}

	public function setTemplatePath(string $path): void {
		$this->config->setUserValue($this->userId, 'core', 'templateDirectory', $path);
	}

	public function getTemplatePath(): string {
		return $this->config->getUserValue($this->userId, 'core', 'templateDirectory', '');
	}

	public function initializeTemplateDirectory(?string $path = null, ?string $userId = null, $copyTemplates = true): string {
		if ($userId !== null) {
			$this->userId = $userId;
		}

		$defaultSkeletonDirectory = \OC::$SERVERROOT . '/core/skeleton';
		$defaultTemplateDirectory = \OC::$SERVERROOT . '/core/skeleton/Templates';
		$skeletonPath = $this->config->getSystemValueString('skeletondirectory', $defaultSkeletonDirectory);
		$skeletonTemplatePath = $this->config->getSystemValueString('templatedirectory', $defaultTemplateDirectory);
		$isDefaultSkeleton = $skeletonPath === $defaultSkeletonDirectory;
		$isDefaultTemplates = $skeletonTemplatePath === $defaultTemplateDirectory;
		$userLang = $this->l10nFactory->getUserLanguage($this->userManager->get($this->userId));

		if ($skeletonTemplatePath === '') {
			$this->setTemplatePath('');
			return '';
		}

		try {
			$l10n = $this->l10nFactory->get('lib', $userLang);
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			$userTemplatePath = $path ?? $this->config->getAppValue('core', 'defaultTemplateDirectory', $l10n->t('Templates')) . '/';

			// Initial user setup without a provided path
			if ($path === null) {
				// All locations are default so we just need to rename the directory to the users language
				if ($isDefaultSkeleton && $isDefaultTemplates) {
					if (!$userFolder->nodeExists('Templates')) {
						return '';
					}
					$newPath = Filesystem::normalizePath($userFolder->getPath() . '/' . $userTemplatePath);
					if ($newPath !== $userFolder->get('Templates')->getPath()) {
						$userFolder->get('Templates')->move($newPath);
					}
					$this->setTemplatePath($userTemplatePath);
					return $userTemplatePath;
				}

				if ($isDefaultSkeleton && !empty($skeletonTemplatePath) && !$isDefaultTemplates && $userFolder->nodeExists('Templates')) {
					$shippedSkeletonTemplates = $userFolder->get('Templates');
					$shippedSkeletonTemplates->delete();
				}
			}

			try {
				$folder = $userFolder->get($userTemplatePath);
			} catch (NotFoundException $e) {
				$folder = $userFolder->get(dirname($userTemplatePath));
				$folder = $folder->newFolder(basename($userTemplatePath));
			}

			$folderIsEmpty = count($folder->getDirectoryListing()) === 0;

			if (!$copyTemplates) {
				$this->setTemplatePath($userTemplatePath);
				return $userTemplatePath;
			}

			if (!$isDefaultTemplates && $folderIsEmpty) {
				$localizedSkeletonTemplatePath = $this->getLocalizedTemplatePath($skeletonTemplatePath, $userLang);
				if (!empty($localizedSkeletonTemplatePath) && file_exists($localizedSkeletonTemplatePath)) {
					\OC_Util::copyr($localizedSkeletonTemplatePath, $folder);
					$userFolder->getStorage()->getScanner()->scan($folder->getInternalPath(), Scanner::SCAN_RECURSIVE);
					$this->setTemplatePath($userTemplatePath);
					return $userTemplatePath;
				}
			}

			if ($path !== null && $isDefaultSkeleton && $isDefaultTemplates && $folderIsEmpty) {
				$localizedSkeletonPath = $this->getLocalizedTemplatePath($skeletonPath . '/Templates', $userLang);
				if (!empty($localizedSkeletonPath) && file_exists($localizedSkeletonPath)) {
					\OC_Util::copyr($localizedSkeletonPath, $folder);
					$userFolder->getStorage()->getScanner()->scan($folder->getInternalPath(), Scanner::SCAN_RECURSIVE);
					$this->setTemplatePath($userTemplatePath);
					return $userTemplatePath;
				}
			}

			$this->setTemplatePath($path ?? '');
			return $this->getTemplatePath();
		} catch (\Throwable $e) {
			$this->logger->error('Failed to initialize templates directory to user language ' . $userLang . ' for ' . $userId, ['app' => 'files_templates', 'exception' => $e]);
		}
		$this->setTemplatePath('');
		return $this->getTemplatePath();
	}

	private function getLocalizedTemplatePath(string $skeletonTemplatePath, string $userLang) {
		$localizedSkeletonTemplatePath = str_replace('{lang}', $userLang, $skeletonTemplatePath);

		if (!file_exists($localizedSkeletonTemplatePath)) {
			$dialectStart = strpos($userLang, '_');
			if ($dialectStart !== false) {
				$localizedSkeletonTemplatePath = str_replace('{lang}', substr($userLang, 0, $dialectStart), $skeletonTemplatePath);
			}
			if ($dialectStart === false || !file_exists($localizedSkeletonTemplatePath)) {
				$localizedSkeletonTemplatePath = str_replace('{lang}', 'default', $skeletonTemplatePath);
			}
		}

		return $localizedSkeletonTemplatePath;
	}
}
