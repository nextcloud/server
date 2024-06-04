<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Provisioning_API\Controller;

use OC_App;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class AppsController extends OCSController {
	/** @var IAppManager */
	private $appManager;

	public function __construct(
		string $appName,
		IRequest $request,
		IAppManager $appManager
	) {
		parent::__construct($appName, $request);

		$this->appManager = $appManager;
	}

	/**
	 * Get a list of installed apps
	 *
	 * @param ?string $filter Filter for enabled or disabled apps
	 * @return DataResponse<Http::STATUS_OK, array{apps: string[]}, array{}>
	 * @throws OCSException
	 *
	 * 200: Installed apps returned
	 */
	public function getApps(?string $filter = null): DataResponse {
		$apps = (new OC_App())->listAllApps();
		$list = [];
		foreach ($apps as $app) {
			$list[] = $app['id'];
		}
		/** @var string[] $list */
		if ($filter) {
			switch ($filter) {
				case 'enabled':
					return new DataResponse(['apps' => \OC_App::getEnabledApps()]);
					break;
				case 'disabled':
					$enabled = OC_App::getEnabledApps();
					return new DataResponse(['apps' => array_diff($list, $enabled)]);
					break;
				default:
					// Invalid filter variable
					throw new OCSException('', 101);
			}
		} else {
			return new DataResponse(['apps' => $list]);
		}
	}

	/**
	 * Get the app info for an app
	 *
	 * @param string $app ID of the app
	 * @return DataResponse<Http::STATUS_OK, array<string, ?mixed>, array{}>
	 * @throws OCSException
	 *
	 * 200: App info returned
	 */
	public function getAppInfo(string $app): DataResponse {
		$info = $this->appManager->getAppInfo($app);
		if (!is_null($info)) {
			return new DataResponse($info);
		}

		throw new OCSException('The request app was not found', OCSController::RESPOND_NOT_FOUND);
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * Enable an app
	 *
	 * @param string $app ID of the app
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: App enabled successfully
	 */
	public function enable(string $app): DataResponse {
		try {
			$this->appManager->enableApp($app);
		} catch (AppPathNotFoundException $e) {
			throw new OCSException('The request app was not found', OCSController::RESPOND_NOT_FOUND);
		}
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * Disable an app
	 *
	 * @param string $app ID of the app
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 *
	 * 200: App disabled successfully
	 */
	public function disable(string $app): DataResponse {
		$this->appManager->disableApp($app);
		return new DataResponse();
	}
}
