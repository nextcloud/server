<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OC\User\NoUserException;
use OCA\Files\Template\OrganizationTemplateProvider;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IPreview;
use OCP\IRequest;

class OrganizationTemplateController extends OCSController {
	public function __construct(
		IRequest $request,
		private OrganizationTemplateProvider $provider,
		private IRootFolder $rootFolder,
		private IPreview $preview,
		private string $userId,
	) {
		parent::__construct('files', $request);
	}

	/**
	 * Get the folder published as organization templates
	 *
	 * Only administrators may access this setting.
	 *
	 * @return DataResponse<Http::STATUS_OK, array{template_path: string, available: bool, owner: string}, array{}>
	 *
	 * 200: Organization template folder returned
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function getPath(): DataResponse {
		$selection = $this->provider->getSelection();
		$path = '';
		$available = false;
		try {
			$folder = $this->provider->getFolder();
			$path = $this->rootFolder->getUserFolder($selection['owner'])->getRelativePath($folder->getPath()) ?? '';
			$available = true;
		} catch (NotFoundException|NotPermittedException|NoUserException $e) {
		}
		return new DataResponse(['template_path' => $path, 'available' => $available, 'owner' => $selection['owner']]);
	}

	/**
	 * Publish a folder's contents as templates for every authenticated user
	 *
	 * @param string $templatePath Folder in the administrator's Files, or empty to disable
	 * @return DataResponse<Http::STATUS_OK, array{template_path: string, available: bool, owner: string}, array{}>
	 * @throws OCSBadRequestException The folder is unavailable
	 *
	 * 200: Organization template folder updated
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function setPath(string $templatePath): DataResponse {
		if ($templatePath === '') {
			$this->provider->setSelection('', 0);
		} else {
			try {
				$userFolder = $this->rootFolder->getUserFolder($this->userId);
				$folder = $userFolder->get($templatePath);
				if (!$folder instanceof Folder || !$folder->isReadable() || $userFolder->getRelativePath($folder->getPath()) === null) {
					throw new OCSBadRequestException('Choose a readable folder');
				}
				$this->provider->setSelection($this->userId, $folder->getId());
			} catch (NotFoundException|NotPermittedException|InvalidPathException $e) {
				throw new OCSBadRequestException('Choose a readable folder');
			}
		}
		return $this->getPath();
	}

	/**
	 * Preview a published organization template
	 *
	 * @param string $id Template file ID
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>
	 * @throws OCSNotFoundException Template or preview unavailable
	 *
	 * 200: Preview returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function preview(string $id): FileDisplayResponse {
		try {
			$file = $this->provider->getCustomTemplate($id);
			$preview = $this->preview->getPreview($file, 512, 512);
			return new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => $preview->getMimeType()]);
		} catch (NotFoundException|NotPermittedException|NoUserException $e) {
			throw new OCSNotFoundException('Template preview unavailable');
		}
	}
}
