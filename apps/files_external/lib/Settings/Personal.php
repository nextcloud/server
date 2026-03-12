<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Service\BackendService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class Personal implements ISettings {
	use CommonSettingsTrait;

	public function __construct(
		?string $userId,
		private BackendService $backendService,
		private GlobalAuth $globalAuth,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
		private IManager $encryptionManager,
	) {
		$this->userId = $userId;
		$this->visibility = BackendService::VISIBILITY_PERSONAL;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$this->setInitialState();
		$this->loadScriptsAndStyles();
		return new TemplateResponse('files_external', 'settings', renderAs: '');
	}

	public function getSection() {
		if (!$this->backendService->isUserMountingAllowed()) {
			return null;
		}

		return 'externalstorages';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 40;
	}
}
