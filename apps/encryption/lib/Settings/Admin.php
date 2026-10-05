<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Settings;

use OCA\Encryption\AppInfo\Application;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	public function __construct(
		private Session $session,
		private Util $util,
		private IInitialState $initialState,
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		// Check if an adminRecovery account is enabled for recovering files after lost pwd
		$recoveryAdminEnabled = $this->appConfig->getValueBool('encryption', 'recoveryAdminEnabled');

		$encryptHomeStorage = $this->util->shouldEncryptHomeStorage();

		$this->initialState->provideInitialState('adminSettings', [
			'recoveryEnabled' => $recoveryAdminEnabled,
			'initStatus' => $this->session->getStatus(),
			'encryptHomeStorage' => $encryptHomeStorage,
			'masterKeyEnabled' => $this->util->isMasterKeyEnabled(),
		]);

		\OCP\Util::addStyle(Application::APP_ID, 'settings_admin');
		\OCP\Util::addScript(Application::APP_ID, 'settings_admin');
		return new TemplateResponse(Application::APP_ID, 'settings', renderAs: '');
	}

	#[\Override]
	public function getSection(): string {
		return 'security';
	}

	#[\Override]
	public function getPriority(): int {
		return 11;
	}
}
