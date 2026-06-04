<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Testing\Controller;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class RoutesController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	public function getRoutesInRoutesPhp(string $app): DataResponse {
		try {
			$appPath = $this->appManager->getAppPath($app);
		} catch (AppPathNotFoundException) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$file = $appPath . '/appinfo/routes.php';
		if (!file_exists($file)) {
			return new DataResponse();
		}

		$routes = include $file;

		return new DataResponse($routes);
	}
}
