<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\OCS;

use OCP\AppFramework\Http;
use OCP\AppFramework\OCSController;

/**
 * @template-covariant S of Http::STATUS_*
 * @template-covariant H of array<string, mixed>
 * @template-extends BaseResponse<Http::STATUS_*, mixed, array<string, mixed>>
 */
class V1Response extends BaseResponse {
	/**
	 * The V1 endpoint has very limited http status codes basically everything
	 * is status 200 except 401
	 *
	 * @return Http::STATUS_*
	 */
	public function getStatus() {
		$status = parent::getStatus();
		if ($status === OCSController::RESPOND_UNAUTHORISED) {
			return Http::STATUS_UNAUTHORIZED;
		}

		return Http::STATUS_OK;
	}

	/**
	 * In v1 all OK is 100
	 *
	 * @return int
	 */
	public function getOCSStatus() {
		$status = parent::getOCSStatus();

		if ($status === Http::STATUS_OK) {
			return 100;
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
		$meta = [
			'status' => $this->getOCSStatus() === 100 ? 'ok' : 'failure',
			'statuscode' => $this->getOCSStatus(),
			'message' => $this->getOCSStatus() === 100 ? 'OK' : $this->statusMessage ?? '',
			'totalitems' => (string)($this->itemsCount ?? ''),
			'itemsperpage' => (string)($this->itemsPerPage ?? ''),
		];

		return $this->renderResult($meta);
	}
}
