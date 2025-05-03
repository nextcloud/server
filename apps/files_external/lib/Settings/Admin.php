<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	use CommonSettingsTrait;

	public function __construct(
		private IManager $encryptionManager,
		private GlobalStoragesService $globalStoragesService,
		private BackendService $backendService,
		private GlobalAuth $globalAuth,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		// Shared settings (user & admin)
		$this->setInitialState(BackendService::VISIBILITY_ADMIN);

		// Admin specific
		$backends = $this->backendService->getAvailableBackends();
		$allowedBackends = array_filter($backends, fn (Backend $backend) => $backend->isVisibleFor(BackendService::VISIBILITY_PERSONAL));
		$this->initialState->provideInitialState('user-mounting', [
			'allowUserMounting' => $this->backendService->isUserMountingAllowed(),
			'allowedBackends' => array_values(array_map(fn (Backend $backend) => $backend->getIdentifier(), $allowedBackends)),
			'backends' => array_values(
				array_map(
					fn (Backend $backend) => [
						'id' => $backend->getIdentifier(),
						'displayName' => $backend->getText(),
						'deprecated' => $backend->getDeprecateTo()?->getIdentifier(),
					],
					$backends,
				),
			),
		]);

		$this->loadScriptsAndStyles();
		return new TemplateResponse('files_external', 'settings', renderAs: '');
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
