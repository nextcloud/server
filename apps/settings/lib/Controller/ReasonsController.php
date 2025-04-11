<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataDisplayResponse;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ReasonsController extends Controller {

	/**
	 * @NoSubAdminRequired
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getPdf() {
		$data = file_get_contents(__DIR__ . '/../../data/Reasons to use Nextcloud.pdf');

		$resp = new DataDisplayResponse($data);
		$resp->addHeader('Content-Type', 'application/pdf');

		return $resp;
	}
}
