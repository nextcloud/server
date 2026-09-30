<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Template;

use OC\User\NoUserException;
use OCA\Files\ConfigLexicon;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Template\ICustomTemplateProvider;
use OCP\Files\Template\Template;
use OCP\IAppConfig;
use OCP\IURLGenerator;

/** An administrator explicitly publishes this folder's contents to all users. */
class OrganizationTemplateProvider implements ICustomTemplateProvider {
	public function __construct(
		private IRootFolder $rootFolder,
		private IAppConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	/** @return array{owner: string, folder: int} */
	public function getSelection(): array {
		$selection = $this->config->getValueArray('files', ConfigLexicon::ORGANIZATION_TEMPLATE_FOLDER, []);
		return ['owner' => (string)($selection['owner'] ?? ''), 'folder' => (int)($selection['folder'] ?? 0)];
	}

	public function setSelection(string $owner, int $folder): void {
		$this->config->setValueArray('files', ConfigLexicon::ORGANIZATION_TEMPLATE_FOLDER, ['owner' => $owner, 'folder' => $folder]);
	}

	public function getFolder(): Folder {
		$selection = $this->getSelection();
		if ($selection['owner'] !== '' && $selection['folder'] > 0) {
			foreach ($this->rootFolder->getUserFolder($selection['owner'])->getById($selection['folder']) as $folder) {
				if ($folder instanceof Folder && $folder->isReadable()) {
					return $folder;
				}
			}
		}
		throw new NotFoundException('No organization template folder available');
	}

	public function getCustomTemplates(string $mimetype): array {
		try {
			$folder = $this->getFolder();
			$templates = [];
			foreach ($folder->searchByMime($mimetype) as $file) {
				if ($file instanceof File && $file->isReadable()) {
					$template = new Template(self::class, (string)$file->getId(), $file);
					$template->setHasPreview(true);
					$template->setCustomPreviewUrl($this->urlGenerator->linkToOCSRouteAbsolute('files.OrganizationTemplate.preview', ['id' => (string)$file->getId(), 'etag' => $file->getEtag()]));
					$templates[] = $template;
				}
			}
			return $templates;
		} catch (NotFoundException|NotPermittedException|NoUserException $e) {
			return [];
		}
	}

	public function getCustomTemplate(string $template): File {
		if (!ctype_digit($template)) {
			throw new NotFoundException('Invalid template identifier');
		}
		$folder = $this->getFolder();
		foreach ($folder->getById((int)$template) as $file) {
			if ($file instanceof File && $file->isReadable() && $folder->getRelativePath($file->getPath()) !== null) {
				return $file;
			}
		}
		throw new NotFoundException('Template no longer available');
	}
}
