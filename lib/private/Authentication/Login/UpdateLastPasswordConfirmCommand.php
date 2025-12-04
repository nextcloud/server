<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OCP\ISession;

class UpdateLastPasswordConfirmCommand extends ALoginCommand {
	public function __construct(
		private ISession $session,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$this->session->set(
			'last-password-confirm',
			$loginData->getUser()->getLastLogin()
		);

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
