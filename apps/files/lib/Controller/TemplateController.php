<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCA\Files\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\Files\Folder;
use OCP\Files\GenericFileException;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Template\ITemplateManager;
use OCP\Files\Template\Template;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IRequest;

/**
 * @psalm-import-type FilesTemplateFile from ResponseDefinitions
 * @psalm-import-type FilesTemplateFileCreator from ResponseDefinitions
 * @psalm-import-type FilesTemplateFileCreatorWithTemplates from ResponseDefinitions
 * @psalm-import-type FilesTemplateField from ResponseDefinitions
 * @psalm-import-type FilesTemplate from ResponseDefinitions
 */
class TemplateController extends OCSController {
	public function __construct(
		$appName,
		IRequest $request,
		protected ITemplateManager $templateManager,
		private IRootFolder $rootFolder,
		private string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the personal template directory
	 *
	 * @return DataResponse<Http::STATUS_OK, array{template_path: string, available: bool}, array{}>
	 *
	 * 200: Personal template directory returned
	 */
	#[NoAdminRequired]
	public function getPath(): DataResponse {
		$path = $this->templateManager->getTemplatePath();
		$available = false;
		if ($path !== '') {
			try {
				$folder = $this->rootFolder->getUserFolder($this->userId)->get($path);
				$available = $folder instanceof Folder && $folder->isReadable();
			} catch (NotFoundException|NotPermittedException|InvalidPathException $e) {
			}
		}
		return new DataResponse(['template_path' => $path, 'available' => $available]);
	}

	/**
	 * Select an existing personal template directory, or clear the selection
	 *
	 * @param string $templatePath User-relative folder path, or an empty string to clear the selection
	 * @return DataResponse<Http::STATUS_OK, array{template_path: string, available: bool}, array{}>
	 * @throws OCSBadRequestException The path does not refer to an existing folder
	 * @throws OCSForbiddenException The folder is not readable
	 *
	 * 200: Personal template directory updated
	 */
	#[NoAdminRequired]
	public function setPath(string $templatePath): DataResponse {
		if ($templatePath !== '') {
			try {
				$userFolder = $this->rootFolder->getUserFolder($this->userId);
				$folder = $userFolder->get($templatePath);
				if (!$folder instanceof Folder) {
					throw new OCSBadRequestException('The template path must be an existing folder');
				}
				if (!$folder->isReadable()) {
					throw new OCSForbiddenException('The template folder must be readable');
				}
				$templatePath = $userFolder->getRelativePath($folder->getPath());
				if ($templatePath === null) {
					throw new OCSBadRequestException('Invalid template folder');
				}
				$templatePath = '/' . trim($templatePath, '/');
			} catch (NotFoundException|InvalidPathException $e) {
				throw new OCSBadRequestException('The template path must be an existing folder');
			} catch (NotPermittedException $e) {
				throw new OCSForbiddenException('The template folder must be readable');
			}
		}
		$this->templateManager->setTemplatePath($templatePath);
		return new DataResponse(['template_path' => $templatePath, 'available' => $templatePath !== '']);
	}

	/**
	 * List the available templates
	 *
	 * @return DataResponse<Http::STATUS_OK, list<FilesTemplateFileCreatorWithTemplates>, array{}>
	 *
	 * 200: Available templates returned
	 */
	#[NoAdminRequired]
	public function list(): DataResponse {
		/* Convert embedded Template instances to arrays to match return type */
		return new DataResponse(array_map(static function (array $templateFileCreator) {
			$templateFileCreator['templates'] = array_map(static fn (Template $template) => $template->jsonSerialize(), $templateFileCreator['templates']);
			return $templateFileCreator;
		}, $this->templateManager->listTemplates()));
	}

	/**
	 * List the fields for the template specified by the given file ID
	 *
	 * @param int $fileId File ID of the template
	 * @return DataResponse<Http::STATUS_OK, array<string, FilesTemplateField>, array{}>
	 *
	 * 200: Fields returned
	 */
	#[NoAdminRequired]
	public function listTemplateFields(int $fileId): DataResponse {
		$fields = $this->templateManager->listTemplateFields($fileId);

		return new DataResponse(
			array_merge([], ...$fields),
			Http::STATUS_OK
		);
	}

	/**
	 * Create a template
	 *
	 * @param string $filePath Path of the file
	 * @param string $templatePath Name of the template
	 * @param string $templateType Type of the template
	 * @param list<FilesTemplateField> $templateFields Fields of the template
	 *
	 * @return DataResponse<Http::STATUS_OK, FilesTemplateFile, array{}>
	 * @throws OCSForbiddenException Creating template is not allowed
	 *
	 * 200: Template created successfully
	 */
	#[NoAdminRequired]
	public function create(
		string $filePath,
		string $templatePath = '',
		string $templateType = 'user',
		array $templateFields = [],
	): DataResponse {
		try {
			return new DataResponse($this->templateManager->createFromTemplate(
				$filePath,
				$templatePath,
				$templateType,
				$templateFields));
		} catch (GenericFileException $e) {
			throw new OCSForbiddenException($e->getMessage());
		}
	}

	/**
	 * Initialize the template directory
	 *
	 * @param string $templatePath Path of the template directory
	 * @param bool $copySystemTemplates Whether to copy the system templates to the template directory
	 *
	 * @return DataResponse<Http::STATUS_OK, array{template_path: string, templates: list<FilesTemplateFileCreator>}, array{}>
	 * @throws OCSForbiddenException Initializing the template directory is not allowed
	 *
	 * 200: Template directory initialized successfully
	 */
	#[NoAdminRequired]
	public function path(string $templatePath = '', bool $copySystemTemplates = false) {
		try {
			/** @var string $templatePath */
			$templatePath = $this->templateManager->initializeTemplateDirectory($templatePath, null, $copySystemTemplates);
			return new DataResponse([
				'template_path' => $templatePath,
				'templates' => array_values(array_map(fn (TemplateFileCreator $creator) => $creator->jsonSerialize(), $this->templateManager->listCreators())),
			]);
		} catch (\Exception $e) {
			throw new OCSForbiddenException($e->getMessage());
		}
	}
}
