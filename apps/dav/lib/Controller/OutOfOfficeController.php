<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Controller;

use OCA\DAV\Db\AbsenceMapper;
use OCA\DAV\ResponseDefinitions;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type DAVOutOfOfficeData from ResponseDefinitions
 */
class OutOfOfficeController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private AbsenceMapper $absenceMapper,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the currently configured out-of-office data of a user.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $userId The user id to get out-of-office data for.
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, ?DAVOutOfOfficeData, array{}>
	 *
	 * 200: Out-of-office data
	 * 404: No out-of-office data was found
	 */
	public function getCurrentOutOfOfficeData(string $userId): DataResponse {
		try {
			$data = $this->absenceMapper->findByUserId($userId);
		} catch (DoesNotExistException) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse([
			'id' => $data->getId(),
			'userId' => $data->getUserId(),
			'firstDay' => $data->getFirstDay(),
			'lastDay' => $data->getLastDay(),
			'status' => $data->getStatus(),
			'message' => $data->getMessage(),
		]);
	}
}
