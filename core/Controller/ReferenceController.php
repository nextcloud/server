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

use OC\Collaboration\Reference\ReferenceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\IRequest;

class ReferenceController extends \OCP\AppFramework\Controller {
	private ReferenceManager $referenceManager;

	public function __construct($appName, IRequest $request, ReferenceManager $referenceManager, IAppDataFactory $appDataFactory) {
		parent::__construct($appName, $request);
		$this->referenceManager = $referenceManager;
		$this->appDataFactory = $appDataFactory;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param $referenceId
	 * @throws \OCP\Files\NotFoundException
	 */
	public function preview($referenceId) {
		$reference = $this->referenceManager->getReferenceByCacheKey($referenceId);
		if ($reference === null) {
			return new DataResponse('', Http::STATUS_NOT_FOUND);
		}

		try {
			$appData = $this->appDataFactory->get('core');
			$folder = $appData->getFolder('opengraph');
			$file = $folder->getFile($referenceId);
		} catch (NotFoundException $e) {
			return new DataResponse('', Http::STATUS_NOT_FOUND);
		}
		return new DataDownloadResponse($file->getContent(), $referenceId, $reference->getImageContentType());
	}
}
