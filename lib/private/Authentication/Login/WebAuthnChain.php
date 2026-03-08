<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

/**
 * Orchestrates the WebAuthn (passkeys/security keys) login command chain in a
 * security-sensitive order for interactive authentication.
 *
 * Mirrors the main login-chain {@see Chain} with adaptations to the 
 * WebAuthn-specific authentication flow (i.e., no pre-login hook or Flow v2
 * ephemeral-session step).
 */
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

	/**
	 * Runs the WebAuthn login pipeline for one login attempt.
	 */
	public function process(LoginData $loginData): LoginResult {
		// Phase 1: pre-auth eligibility checks
		$chain = $this->userDisabledCheckCommand;
		$chain
			// Phase 2: primary authentication and login-state transition
			->setNext($this->webAuthnLoginCommand)
			->setNext($this->loggedInCheckCommand)
			->setNext($this->completeLoginCommand)

			// Phase 3: session strategy and token materialization
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
