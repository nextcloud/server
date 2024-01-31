<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
