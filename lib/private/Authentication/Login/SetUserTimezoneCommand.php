<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Core\AppInfo\Application;
use OC\Core\AppInfo\ConfigLexicon;
use OCP\IConfig;
use OCP\ISession;

class SetUserTimezoneCommand extends ALoginCommand {
	public function __construct(
		private IConfig $config,
		private ISession $session,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		if ($loginData->getTimeZoneOffset() !== '' && $this->isValidTimezone($loginData->getTimeZone())) {
			$userId = $loginData->getUser()->getUID();
			if ($this->config->getUserValue($userId, Application::APP_ID, ConfigLexicon::USER_TIMEZONE, '') === '') {
				$this->config->setUserValue($userId, Application::APP_ID, ConfigLexicon::USER_TIMEZONE, $loginData->getTimeZone());
			}
			$this->session->set(
				'timezone',
				$loginData->getTimeZoneOffset()
			);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}

	private function isValidTimezone(?string $value): bool {
		return $value && in_array($value, \DateTimeZone::listIdentifiers());
	}
}
