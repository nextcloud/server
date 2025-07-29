<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Provisioning_API\Controller;

use OC\App\AppStore\AppNotFoundException;
use OC\Installer;
use OC_App;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IRequest;

class AppsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppManager $appManager,
		private Installer $installer,
		private IAppConfig $appConfig,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function verifyAppId(string $app): string {
		$cleanId = $this->appManager->cleanAppId($app);
		if ($cleanId !== $app) {
			throw new \InvalidArgumentException('Invalid app id given');
		}
		return $cleanId;
	}

	/**
	 * Get a list of installed apps
	 *
	 * @param ?string $filter Filter for enabled or disabled apps
	 * @return DataResponse<Http::STATUS_OK, array{apps: list<string>}, array{}>
	 * @throws OCSException
	 *
	 * 200: Installed apps returned
	 */
	public function getApps(?string $filter = null): DataResponse {
		$apps = (new OC_App())->listAllApps();
		/** @var list<string> $list */
		$list = [];
		foreach ($apps as $app) {
			$list[] = $app['id'];
		}
		if ($filter) {
			switch ($filter) {
				case 'enabled':
					return new DataResponse(['apps' => \OC_App::getEnabledApps()]);
					break;
				case 'disabled':
					$enabled = OC_App::getEnabledApps();
					return new DataResponse(['apps' => array_values(array_diff($list, $enabled))]);
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
		try {
			$app = $this->verifyAppId($app);
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), OCSController::RESPOND_UNAUTHORISED);
		}
		$info = $this->appManager->getAppInfo($app);
		if (!is_null($info)) {
			return new DataResponse($info);
		}

		throw new OCSException('The request app was not found', OCSController::RESPOND_NOT_FOUND);
	}

	/**
	 * Enable an app
	 *
	 * @param string $app ID of the app
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: App enabled successfully
	 */
	#[PasswordConfirmationRequired]
	public function enable(string $app): DataResponse {
		try {
			$app = $this->verifyAppId($app);

			if (!$this->installer->isDownloaded($app)) {
				$this->installer->downloadApp($app);
			}

			if ($this->appConfig->getValueString($app, 'installed_version', '') === '') {
				$this->installer->installApp($app);
			}

			$this->appManager->enableApp($app);
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), OCSController::RESPOND_UNAUTHORISED);
		} catch (AppPathNotFoundException|AppNotFoundException $e) {
			throw new OCSException('The request app was not found', OCSController::RESPOND_NOT_FOUND);
		}
		return new DataResponse();
	}

	/**
	 * Disable an app
	 *
	 * @param string $app ID of the app
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: App disabled successfully
	 */
	#[PasswordConfirmationRequired]
	public function disable(string $app): DataResponse {
		try {
			$app = $this->verifyAppId($app);
			$this->appManager->disableApp($app);
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), OCSController::RESPOND_UNAUTHORISED);
		}
		return new DataResponse();
	}
}
