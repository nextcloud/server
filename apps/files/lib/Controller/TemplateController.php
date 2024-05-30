<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Controller;

use OCA\Files\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\Files\GenericFileException;
use OCP\Files\Template\ITemplateManager;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IRequest;

/**
 * @psalm-import-type FilesTemplateFile from ResponseDefinitions
 * @psalm-import-type FilesTemplateFileCreator from ResponseDefinitions
 */
class TemplateController extends OCSController {
	protected $templateManager;

	public function __construct($appName, IRequest $request, ITemplateManager $templateManager) {
		parent::__construct($appName, $request);
		$this->templateManager = $templateManager;
	}

	/**
	 * @NoAdminRequired
	 *
	 * List the available templates
	 *
	 * @return DataResponse<Http::STATUS_OK, array<FilesTemplateFileCreator>, array{}>
	 *
	 * 200: Available templates returned
	 */
	public function list(): DataResponse {
		return new DataResponse($this->templateManager->listTemplates());
	}

	/**
	 * @NoAdminRequired
	 *
	 * Create a template
	 *
	 * @param string $filePath Path of the file
	 * @param string $templatePath Name of the template
	 * @param string $templateType Type of the template
	 *
	 * @return DataResponse<Http::STATUS_OK, FilesTemplateFile, array{}>
	 * @throws OCSForbiddenException Creating template is not allowed
	 *
	 * 200: Template created successfully
	 */
	public function create(string $filePath, string $templatePath = '', string $templateType = 'user'): DataResponse {
		try {
			return new DataResponse($this->templateManager->createFromTemplate($filePath, $templatePath, $templateType));
		} catch (GenericFileException $e) {
			throw new OCSForbiddenException($e->getMessage());
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * Initialize the template directory
	 *
	 * @param string $templatePath Path of the template directory
	 * @param bool $copySystemTemplates Whether to copy the system templates to the template directory
	 *
	 * @return DataResponse<Http::STATUS_OK, array{template_path: string, templates: FilesTemplateFileCreator[]}, array{}>
	 * @throws OCSForbiddenException Initializing the template directory is not allowed
	 *
	 * 200: Template directory initialized successfully
	 */
	public function path(string $templatePath = '', bool $copySystemTemplates = false) {
		try {
			/** @var string $templatePath */
			$templatePath = $this->templateManager->initializeTemplateDirectory($templatePath, null, $copySystemTemplates);
			return new DataResponse([
				'template_path' => $templatePath,
				'templates' => array_map(fn (TemplateFileCreator $creator) => $creator->jsonSerialize(), $this->templateManager->listCreators()),
			]);
		} catch (\Exception $e) {
			throw new OCSForbiddenException($e->getMessage());
		}
	}
}
