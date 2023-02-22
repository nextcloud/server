<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
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
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Http\Response;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;

class ReferenceController extends Controller {
	private IReferenceManager $referenceManager;
	private IAppDataFactory $appDataFactory;

	public function __construct(string $appName, IRequest $request, IReferenceManager $referenceManager, IAppDataFactory $appDataFactory) {
		parent::__construct($appName, $request);
		$this->referenceManager = $referenceManager;
		$this->appDataFactory = $appDataFactory;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $referenceId the reference cache key
	 * @return Response
	 */
	public function preview(string $referenceId): Response {
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
