<?php
/**
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 *
 */

declare(strict_types=1);


namespace OC\Files\Template;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Template\CreatedFromTemplateEvent;
use OCP\Files\Template\ICustomTemplateProvider;
use OCP\Files\Template\ITemplateManager;
use OCP\Files\Template\Template;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IConfig;
use OCP\IPreview;
use OCP\IServerContainer;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

class TemplateManager implements ITemplateManager {
	private $types = [];

	private $registeredProviders = [];
	private $providers;

	private $serverContainer;
	private $eventDispatcher;
	private $rootFolder;
	private $previewManager;
	private $config;
	private $l10n;
	private $logger;
	private $userId;

	public function __construct(
		IServerContainer $serverContainer,
		IEventDispatcher $eventDispatcher,
		IRootFolder $rootFolder,
		IUserSession $userSession,
		IPreview $previewManager,
		IConfig $config,
		IFactory $l10n,
		LoggerInterface $logger
	) {
		$this->serverContainer = $serverContainer;
		$this->eventDispatcher = $eventDispatcher;
		$this->rootFolder = $rootFolder;
		$this->previewManager = $previewManager;
		$this->config = $config;
		$this->l10n = $l10n->get('lib');
		$this->logger = $logger;
		$user = $userSession->getUser();
		$this->userId = $user ? $user->getUID() : null;
	}

	public function registerTemplateFileCreator(TemplateFileCreator $templateType): void {
		$this->types[] = $templateType;
	}

	public function registerTemplateProvider(string $providerClass): void {
		$this->registeredProviders[] = $providerClass;
	}

	public function getRegisteredProviders(): array {
		if ($this->providers !== null) {
			return $this->providers;
		}
		$this->providers = [];
		foreach ($this->registeredProviders as $providerClass) {
			$this->providers[$providerClass] = $this->serverContainer->get($providerClass);
		}
		return $this->providers;
	}

	public function listCreators(): array {
		return array_map(function (TemplateFileCreator $entry) {
			return array_merge($entry->jsonSerialize(), [
				'templates' => $this->getTemplateFiles($entry)
			]);
		}, $this->types);
	}

	/**
	 * @param string $filePath
	 * @param string $templateId
	 * @return array
	 * @throws GenericFileException
	 */
	public function createFromTemplate(string $filePath, string $templateId = '', string $templateType = 'user'): array {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		try {
			$userFolder->get($filePath);
			throw new GenericFileException($this->l10n->t('File already exists'));
		} catch (NotFoundException $e) {
		}
		try {
			$targetFile = $userFolder->newFile($filePath);
			if ($templateType === 'user' && $templateId !== '') {
				$template = $userFolder->get($templateId);
				$template->copy($targetFile->getPath());
			} else {
				$matchingProvider = array_filter($this->getRegisteredProviders(), function (ICustomTemplateProvider $provider) use ($templateType) {
					return $templateType === get_class($provider);
				});
				$provider = array_shift($matchingProvider);
				if ($provider) {
					$template = $provider->getCustomTemplate($templateId);
					$template->copy($targetFile->getPath());
				}
			}
			$this->eventDispatcher->dispatchTyped(new CreatedFromTemplateEvent($template, $targetFile));
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
	private function getTemplateFolder(): Node {
		return $this->rootFolder->getUserFolder($this->userId)->get($this->getTemplatePath());
	}

	private function getTemplateFiles(TemplateFileCreator $type): array {
		$templates = [];
		foreach ($this->getRegisteredProviders() as $provider) {
			foreach ($type->getMimetypes() as $mimetype) {
				foreach ($provider->getCustomTemplates($mimetype) as $template) {
					$templates[] = $template;
				}
			}
		}
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
			'hasPreview' => $this->previewManager->isAvailable($file)
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
		return $this->config->getUserValue($this->userId, 'core', 'templateDirectory', $this->l10n->t('Templates') . '/');
	}

	public function initializeTemplateDirectory(string $path = null, string $userId = null): void {
		if ($userId !== null) {
			$this->userId = $userId;
		}
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$templateDirectoryPath = $path ?? $this->l10n->t('Templates') . '/';
		try {
			$userFolder->get($templateDirectoryPath);
		} catch (NotFoundException $e) {
			$folder = $userFolder->newFolder($templateDirectoryPath);
			$folder->newFile('Testtemplate.txt');
		}
		$this->setTemplatePath($templateDirectoryPath);
	}
}
