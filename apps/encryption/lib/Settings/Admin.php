<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Settings;

use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
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
		$recoveryAdminEnabled = $this->config->getAppValue('encryption', 'recoveryAdminEnabled', '0');
		$session = new Session($this->session);

		$encryptHomeStorage = $util->shouldEncryptHomeStorage();

		$parameters = [
			'recoveryEnabled' => $recoveryAdminEnabled,
			'initStatus' => $session->getStatus(),
			'encryptHomeStorage' => $encryptHomeStorage,
			'masterKeyEnabled' => $util->isMasterKeyEnabled(),
		];

		return new TemplateResponse('encryption', 'settings-admin', $parameters, '');
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
