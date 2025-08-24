<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\MountConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Encryption\IManager;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IManager $encryptionManager,
		private GlobalStoragesService $globalStoragesService,
		private BackendService $backendService,
		private GlobalAuth $globalAuth,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			'encryptionEnabled' => $this->encryptionManager->isEnabled(),
			'visibilityType' => BackendService::VISIBILITY_ADMIN,
			'storages' => $this->globalStoragesService->getStorages(),
			'backends' => $this->backendService->getAvailableBackends(),
			'authMechanisms' => $this->backendService->getAuthMechanisms(),
			'dependencies' => MountConfig::dependencyMessage($this->backendService->getBackends()),
			'allowUserMounting' => $this->backendService->isUserMountingAllowed(),
			'globalCredentials' => $this->globalAuth->getAuth(''),
			'globalCredentialsUid' => '',
		];

		return new TemplateResponse('files_external', 'settings', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
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
