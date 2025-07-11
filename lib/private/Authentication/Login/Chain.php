<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

class Chain {
	public function __construct(
		private PreLoginHookCommand $preLoginHookCommand,
		private UserDisabledCheckCommand $userDisabledCheckCommand,
		private UidLoginCommand $uidLoginCommand,
		private LoggedInCheckCommand $loggedInCheckCommand,
		private CompleteLoginCommand $completeLoginCommand,
		private CreateSessionTokenCommand $createSessionTokenCommand,
		private ClearLostPasswordTokensCommand $clearLostPasswordTokensCommand,
		private UpdateLastPasswordConfirmCommand $updateLastPasswordConfirmCommand,
		private SetUserTimezoneCommand $setUserTimezoneCommand,
		private TwoFactorCommand $twoFactorCommand,
		private FinishRememberedLoginCommand $finishRememberedLoginCommand,
		private FlowV2EphemeralSessionsCommand $flowV2EphemeralSessionsCommand,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$chain = $this->preLoginHookCommand;
		$chain
			->setNext($this->userDisabledCheckCommand)
			->setNext($this->uidLoginCommand)
			->setNext($this->loggedInCheckCommand)
			->setNext($this->completeLoginCommand)
			->setNext($this->flowV2EphemeralSessionsCommand)
			->setNext($this->createSessionTokenCommand)
			->setNext($this->clearLostPasswordTokensCommand)
			->setNext($this->updateLastPasswordConfirmCommand)
			->setNext($this->setUserTimezoneCommand)
			->setNext($this->twoFactorCommand)
			->setNext($this->finishRememberedLoginCommand);

		return $chain->process($loginData);
	}
}
