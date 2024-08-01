<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;

class RequestTimeController extends Controller {

	/**
	 * @param string $search
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function getTimezones($search = '') {
		$timezones = \DateTimeZone::listIdentifiers();

		if ($search !== '') {
			$timezones = array_filter($timezones, function ($timezone) use ($search) {
				return stripos($timezone, $search) !== false;
			});
		}

		$timezones = array_slice($timezones, 0, 10);

		$response = [];
		foreach ($timezones as $timezone) {
			$response[$timezone] = $timezone;
		}
		return new JSONResponse($response);
	}
}
