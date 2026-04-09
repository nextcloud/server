<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {

	public function __construct(
		IRequest $request,
		private IConfig $config,
		private string $userId,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	public function setDefaultAccept(bool $accept): JSONResponse {
		$this->config->setUserValue($this->userId, Application::APP_ID, 'default_accept', $accept ? 'yes' : 'no');
		return new JSONResponse();
	}

	#[NoAdminRequired]
	public function setUserShareFolder(string $shareFolder): JSONResponse {
		$this->config->setUserValue($this->userId, Application::APP_ID, 'share_folder', $shareFolder);
		return new JSONResponse();
	}

	#[NoAdminRequired]
	public function resetUserShareFolder(): JSONResponse {
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'share_folder');
		return new JSONResponse();
	}
}
