<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OCP\IConfig;

class ClearLostPasswordTokensCommand extends ALoginCommand {
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * User has successfully logged in, now remove the password reset link, when it is available
	 */
	public function process(LoginData $loginData): LoginResult {
		$this->config->deleteUserValue(
			$loginData->getUser()->getUID(),
			'core',
			'lostpassword'
		);

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
