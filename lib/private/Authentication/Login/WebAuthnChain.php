<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

class WebAuthnChain {
	public function __construct(
		private UserDisabledCheckCommand $userDisabledCheckCommand,
		private WebAuthnLoginCommand $webAuthnLoginCommand,
		private LoggedInCheckCommand $loggedInCheckCommand,
		private CompleteLoginCommand $completeLoginCommand,
		private CreateSessionTokenCommand $createSessionTokenCommand,
		private ClearLostPasswordTokensCommand $clearLostPasswordTokensCommand,
		private UpdateLastPasswordConfirmCommand $updateLastPasswordConfirmCommand,
		private SetUserTimezoneCommand $setUserTimezoneCommand,
		private TwoFactorCommand $twoFactorCommand,
		private FinishRememberedLoginCommand $finishRememberedLoginCommand,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$chain = $this->userDisabledCheckCommand;
		$chain
			->setNext($this->webAuthnLoginCommand)
			->setNext($this->loggedInCheckCommand)
			->setNext($this->completeLoginCommand)
			->setNext($this->createSessionTokenCommand)
			->setNext($this->clearLostPasswordTokensCommand)
			->setNext($this->updateLastPasswordConfirmCommand)
			->setNext($this->setUserTimezoneCommand)
			->setNext($this->twoFactorCommand)
			->setNext($this->finishRememberedLoginCommand);

		return $chain->process($loginData);
	}
}
