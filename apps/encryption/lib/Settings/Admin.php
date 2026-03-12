<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Settings;

use OC\Files\View;
use OCA\Encryption\AppInfo\Application;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {
	public function __construct(
		private IL10N $l,
		private LoggerInterface $logger,
		private IUserSession $userSession,
		private IConfig $config,
		private IUserManager $userManager,
		private ISession $session,
		private IInitialState $initialState,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$crypt = new Crypt(
			$this->logger,
			$this->userSession,
			$this->config,
			$this->l);

		$util = new Util(
			new View(),
			$crypt,
			$this->userSession,
			$this->config,
			$this->userManager);

		// Check if an adminRecovery account is enabled for recovering files after lost pwd
		$recoveryAdminEnabled = $this->appConfig->getValueBool('encryption', 'recoveryAdminEnabled');
		$session = new Session($this->session);

		$encryptHomeStorage = $util->shouldEncryptHomeStorage();

		$this->initialState->provideInitialState('adminSettings', [
			'recoveryEnabled' => $recoveryAdminEnabled,
			'initStatus' => $session->getStatus(),
			'encryptHomeStorage' => $encryptHomeStorage,
			'masterKeyEnabled' => $util->isMasterKeyEnabled(),
		]);

		\OCP\Util::addStyle(Application::APP_ID, 'settings_admin');
		\OCP\Util::addScript(Application::APP_ID, 'settings_admin');
		return new TemplateResponse(Application::APP_ID, 'settings', renderAs: '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 11;
	}
}
