<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\MountConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Encryption\IManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private IManager $encryptionManager,
		private UserGlobalStoragesService $userGlobalStoragesService,
		private BackendService $backendService,
		private GlobalAuth $globalAuth,
		private IUserSession $userSession,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$uid = $this->userSession->getUser()->getUID();

		$parameters = [
			'encryptionEnabled' => $this->encryptionManager->isEnabled(),
			'visibilityType' => BackendService::VISIBILITY_PERSONAL,
			'storages' => $this->userGlobalStoragesService->getStorages(),
			'backends' => $this->backendService->getAvailableBackends(),
			'authMechanisms' => $this->backendService->getAuthMechanisms(),
			'dependencies' => MountConfig::dependencyMessage($this->backendService->getBackends()),
			'allowUserMounting' => $this->backendService->isUserMountingAllowed(),
			'globalCredentials' => $this->globalAuth->getAuth($uid),
			'globalCredentialsUid' => $uid,
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
