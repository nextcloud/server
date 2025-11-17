<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\User\Session;
use OCP\IConfig;

class FinishRememberedLoginCommand extends ALoginCommand {
	public function __construct(
		private Session $userSession,
		private IConfig $config,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		if ($loginData->isRememberLogin() && !$this->config->getSystemValueBool('auto_logout', false)) {
			$this->userSession->createRememberMeToken($loginData->getUser());
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
