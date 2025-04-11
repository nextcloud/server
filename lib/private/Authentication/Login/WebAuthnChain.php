<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

class WebAuthnChain {
	/** @var UserDisabledCheckCommand */
	private $userDisabledCheckCommand;

	/** @var LoggedInCheckCommand */
	private $loggedInCheckCommand;

	/** @var CompleteLoginCommand */
	private $completeLoginCommand;

	/** @var CreateSessionTokenCommand */
	private $createSessionTokenCommand;

	/** @var ClearLostPasswordTokensCommand */
	private $clearLostPasswordTokensCommand;

	/** @var UpdateLastPasswordConfirmCommand */
	private $updateLastPasswordConfirmCommand;

	/** @var SetUserTimezoneCommand */
	private $setUserTimezoneCommand;

	/** @var TwoFactorCommand */
	private $twoFactorCommand;

	/** @var FinishRememberedLoginCommand */
	private $finishRememberedLoginCommand;

	/** @var WebAuthnLoginCommand */
	private $webAuthnLoginCommand;

	public function __construct(UserDisabledCheckCommand $userDisabledCheckCommand,
		WebAuthnLoginCommand $webAuthnLoginCommand,
		LoggedInCheckCommand $loggedInCheckCommand,
		CompleteLoginCommand $completeLoginCommand,
		CreateSessionTokenCommand $createSessionTokenCommand,
		ClearLostPasswordTokensCommand $clearLostPasswordTokensCommand,
		UpdateLastPasswordConfirmCommand $updateLastPasswordConfirmCommand,
		SetUserTimezoneCommand $setUserTimezoneCommand,
		TwoFactorCommand $twoFactorCommand,
		FinishRememberedLoginCommand $finishRememberedLoginCommand,
	) {
		$this->userDisabledCheckCommand = $userDisabledCheckCommand;
		$this->webAuthnLoginCommand = $webAuthnLoginCommand;
		$this->loggedInCheckCommand = $loggedInCheckCommand;
		$this->completeLoginCommand = $completeLoginCommand;
		$this->createSessionTokenCommand = $createSessionTokenCommand;
		$this->clearLostPasswordTokensCommand = $clearLostPasswordTokensCommand;
		$this->updateLastPasswordConfirmCommand = $updateLastPasswordConfirmCommand;
		$this->setUserTimezoneCommand = $setUserTimezoneCommand;
		$this->twoFactorCommand = $twoFactorCommand;
		$this->finishRememberedLoginCommand = $finishRememberedLoginCommand;
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
