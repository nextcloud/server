<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OCP\IUserManager;

class WebAuthnLoginCommand extends ALoginCommand {
	/** @var IUserManager */
	private $userManager;

	public function __construct(IUserManager $userManager) {
		$this->userManager = $userManager;
	}

	public function process(LoginData $loginData): LoginResult {
		$user = $this->userManager->get($loginData->getUsername());
		$loginData->setUser($user);
		if ($user === null) {
			$loginData->setUser(false);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
