<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;

class ReferenceController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IReferenceManager $referenceManager,
		private IAppDataFactory $appDataFactory,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get a preview for a reference
	 *
	 * @param string $referenceId the reference cache key
	 * @return DataDownloadResponse<Http::STATUS_OK, string, array{}>|DataResponse<Http::STATUS_NOT_FOUND, '', array{}>
	 *
	 * 200: Preview returned
	 * 404: Reference not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/core/references/preview/{referenceId}')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function preview(string $referenceId): DataDownloadResponse|DataResponse {
		$reference = $this->referenceManager->getReferenceByCacheKey($referenceId);

		try {
			$appData = $this->appDataFactory->get('core');
			$folder = $appData->getFolder('opengraph');
			$file = $folder->getFile($referenceId);
			$contentType = $reference === null || $reference->getImageContentType() === null
				? $file->getMimeType()
				: $reference->getImageContentType();
			$response = new DataDownloadResponse(
				$file->getContent(),
				$referenceId,
				$contentType
			);
		} catch (NotFoundException|NotPermittedException $e) {
			$response = new DataResponse('', Http::STATUS_NOT_FOUND);
		}
		$response->cacheFor(3600, false, true);
		return $response;
	}
}
