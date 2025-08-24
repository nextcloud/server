<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Settings\ISettings;

class Security implements ISettings {
	private MandatoryTwoFactor $mandatoryTwoFactor;

	public function __construct(
		private IManager $manager,
		private IUserManager $userManager,
		MandatoryTwoFactor $mandatoryTwoFactor,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$encryptionModules = $this->manager->getEncryptionModules();
		$defaultEncryptionModuleId = $this->manager->getDefaultEncryptionModuleId();
		$encryptionModuleList = [];
		foreach ($encryptionModules as $module) {
			$encryptionModuleList[$module['id']]['displayName'] = $module['displayName'];
			$encryptionModuleList[$module['id']]['default'] = false;
			if ($module['id'] === $defaultEncryptionModuleId) {
				$encryptionModuleList[$module['id']]['default'] = true;
			}
		}

		$this->initialState->provideInitialState('mandatory2FAState', $this->mandatoryTwoFactor->getState());
		$this->initialState->provideInitialState('two-factor-admin-doc', $this->urlGenerator->linkToDocs('admin-2fa'));
		$this->initialState->provideInitialState('encryption-enabled', $this->manager->isEnabled());
		$this->initialState->provideInitialState('encryption-ready', $this->manager->isReady());
		$this->initialState->provideInitialState('external-backends-enabled', count($this->userManager->getBackends()) > 1);
		$this->initialState->provideInitialState('encryption-modules', $encryptionModuleList);
		$this->initialState->provideInitialState('encryption-admin-doc', $this->urlGenerator->linkToDocs('admin-encryption'));

		return new TemplateResponse('settings', 'settings/admin/security', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 10;
	}
}
