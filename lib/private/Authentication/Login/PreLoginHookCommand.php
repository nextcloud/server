<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Hooks\PublicEmitter;
use OCP\IUserManager;

class PreLoginHookCommand extends ALoginCommand {
	public function __construct(
		private IUserManager $userManager,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		if ($this->userManager instanceof PublicEmitter) {
			$this->userManager->emit(
				'\OC\User',
				'preLogin',
				[
					$loginData->getUsername(),
					$loginData->getPassword(),
				]
			);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
