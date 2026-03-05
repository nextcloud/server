<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Settings;

use OCA\Encryption\AppInfo\Application;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private Session $session,
		private Util $util,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private IAppConfig $appConfig,
		private IManager $manager,
	) {
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm() {
		$recoveryAdminEnabled = $this->appConfig->getValueBool('encryption', 'recoveryAdminEnabled');
		$privateKeySet = $this->session->isPrivateKeySet();

		if (!$recoveryAdminEnabled && $privateKeySet) {
			return new TemplateResponse('settings', 'settings/empty', [], '');
		}

		$userId = $this->userSession->getUser()->getUID();
		$recoveryEnabledForUser = $this->util->isRecoveryEnabledForUser($userId);

		$this->initialState->provideInitialState('personalSettings', [
			'recoveryEnabled' => $recoveryAdminEnabled,
			'recoveryEnabledForUser' => $recoveryEnabledForUser,
			'privateKeySet' => $privateKeySet,
			'initialized' => $this->session->getStatus(),
		]);

		\OCP\Util::addStyle(Application::APP_ID, 'settings_personal');
		\OCP\Util::addScript(Application::APP_ID, 'settings_personal');
		return new TemplateResponse(Application::APP_ID, 'settings', renderAs: '');
	}

	public function getSection() {
		if (!$this->manager->isEnabled()) {
			return null;
		}

		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority() {
		return 80;
	}
}
