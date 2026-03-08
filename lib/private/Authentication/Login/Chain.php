<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

/**
 * Orchestrates the login command chain in a security-sensitive order for interactive authentication.
 */
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

	/**
	 * Runs the login pipeline for one login attempt.
	 *
	 * Commands share mutable LoginData and may have side effects.
	 * A command may opt to permit processing to continue or return a final LoginResult early.
	 *
	 * If order changes, review login-flow invariants and related tests.
	 */
	public function process(LoginData $loginData): LoginResult {
		// Phase 1: pre-auth hooks and eligibility checks
		$chain = $this->preLoginHookCommand;
		$chain
			->setNext($this->userDisabledCheckCommand)

			// Phase 2: primary authentication and login-state transition
			->setNext($this->uidLoginCommand)
			->setNext($this->loggedInCheckCommand)
			->setNext($this->completeLoginCommand)

			// Phase 3: session strategy and token materialization
			->setNext($this->flowV2EphemeralSessionsCommand) // must precede standard token creation
			->setNext($this->createSessionTokenCommand)

			// Phase 4: post-auth maintenance and context updates
			->setNext($this->clearLostPasswordTokensCommand)
			->setNext($this->updateLastPasswordConfirmCommand)
			->setNext($this->setUserTimezoneCommand)

			// Phase 5: assurance/finalization gates
			->setNext($this->twoFactorCommand) // before remembered-login finalization
			->setNext($this->finishRememberedLoginCommand);

		return $chain->process($loginData);
	}
}
