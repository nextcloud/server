<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\OCS;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;

/**
 * @psalm-import-type DataResponseType from DataResponse
 * @template S of Http::STATUS_*
 * @template-covariant T of DataResponseType
 * @template H of array<string, mixed>
 * @template-extends BaseResponse<Http::STATUS_*, DataResponseType, array<string, mixed>>
 */
class V2Response extends BaseResponse {
	/**
	 * The V2 endpoint just passes on status codes.
	 * Of course we have to map the OCS specific codes to proper HTTP status codes
	 *
	 * @return Http::STATUS_*
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
