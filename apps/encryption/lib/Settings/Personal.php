<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Settings;

use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private IConfig $config,
		private Session $session,
		private Util $util,
		private IUserSession $userSession,
	) {
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm() {
		$recoveryAdminEnabled = $this->config->getAppValue('encryption', 'recoveryAdminEnabled');
		$privateKeySet = $this->session->isPrivateKeySet();

		if (!$recoveryAdminEnabled && $privateKeySet) {
			return new TemplateResponse('settings', 'settings/empty', [], '');
		}

		$userId = $this->userSession->getUser()->getUID();
		$recoveryEnabledForUser = $this->util->isRecoveryEnabledForUser($userId);

		$parameters = [
			'recoveryEnabled' => $recoveryAdminEnabled,
			'recoveryEnabledForUser' => $recoveryEnabledForUser,
			'privateKeySet' => $privateKeySet,
			'initialized' => $this->session->getStatus(),
		];
		return new TemplateResponse('encryption', 'settings-personal', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
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
	 * @since 9.1
	 */
	public function getPriority() {
		return 80;
	}
}
