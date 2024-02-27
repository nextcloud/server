<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OC\AppFramework\OCS;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;

/**
 * @psalm-import-type DataResponseType from DataResponse
 * @template S of int
 * @template-covariant T of DataResponseType
 * @template H of array<string, mixed>
 * @template-extends BaseResponse<int, DataResponseType, array<string, mixed>>
 */
class V2Response extends BaseResponse {
	/**
	 * The V2 endpoint just passes on status codes.
	 * Of course we have to map the OCS specific codes to proper HTTP status codes
	 *
	 * @return int
	 */
	public function getStatus() {
		$status = parent::getStatus();
		if ($status === OCSController::RESPOND_UNAUTHORISED) {
			return Http::STATUS_UNAUTHORIZED;
		} elseif ($status === OCSController::RESPOND_NOT_FOUND) {
			return Http::STATUS_NOT_FOUND;
		} elseif ($status === OCSController::RESPOND_SERVER_ERROR || $status === OCSController::RESPOND_UNKNOWN_ERROR) {
			return Http::STATUS_INTERNAL_SERVER_ERROR;
		} elseif ($status < 200 || $status > 600) {
			return Http::STATUS_BAD_REQUEST;
		}

		return $status;
	}

	/**
	 * Construct the meta part of the response
	 * And then late the base class render
	 *
	 * @return string
	 */
	public function render() {
		$status = parent::getStatus();

		$meta = [
			'status' => $status >= 200 && $status < 300 ? 'ok' : 'failure',
			'statuscode' => $this->getOCSStatus(),
			'message' => $status >= 200 && $status < 300 ? 'OK' : $this->statusMessage ?? '',
		];

		if ($this->itemsCount !== null) {
			$meta['totalitems'] = $this->itemsCount;
		}
		if ($this->itemsPerPage !== null) {
			$meta['itemsperpage'] = $this->itemsPerPage;
		}

		return $this->renderResult($meta);
	}
}
