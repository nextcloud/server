<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Controller;

use OCA\Viewer\Service\UserConfig;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ConfigController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private UserConfig $userConfig,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Set one of the user's viewer settings
	 *
	 * @param string $key The setting to change
	 * @param int|bool $value Its new value: a number for a setting with a range, true or false for a toggle
	 * @return DataResponse<Http::STATUS_OK, array{key: string, value: int|bool}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: Setting saved
	 * 400: Unknown setting, or a value it does not accept
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/config/{key}')]
	public function setConfig(string $key, int|bool $value): DataResponse {
		try {
			$this->userConfig->setConfig($key, $value);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse(['key' => $key, 'value' => $value]);
	}
}
