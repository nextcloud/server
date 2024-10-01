<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

class Chain {
	/** @var PreLoginHookCommand */
	private $preLoginHookCommand;

	/** @var UserDisabledCheckCommand */
	private $userDisabledCheckCommand;

	/** @var UidLoginCommand */
	private $uidLoginCommand;

	/** @var EmailLoginCommand */
	private $emailLoginCommand;

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

	public function __construct(PreLoginHookCommand $preLoginHookCommand,
		UserDisabledCheckCommand $userDisabledCheckCommand,
		UidLoginCommand $uidLoginCommand,
		EmailLoginCommand $emailLoginCommand,
		LoggedInCheckCommand $loggedInCheckCommand,
		CompleteLoginCommand $completeLoginCommand,
		CreateSessionTokenCommand $createSessionTokenCommand,
		ClearLostPasswordTokensCommand $clearLostPasswordTokensCommand,
		UpdateLastPasswordConfirmCommand $updateLastPasswordConfirmCommand,
		SetUserTimezoneCommand $setUserTimezoneCommand,
		TwoFactorCommand $twoFactorCommand,
		FinishRememberedLoginCommand $finishRememberedLoginCommand,
	) {
		$this->preLoginHookCommand = $preLoginHookCommand;
		$this->userDisabledCheckCommand = $userDisabledCheckCommand;
		$this->uidLoginCommand = $uidLoginCommand;
		$this->emailLoginCommand = $emailLoginCommand;
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
		$chain = $this->preLoginHookCommand;
		$chain
			->setNext($this->userDisabledCheckCommand)
			->setNext($this->uidLoginCommand)
			->setNext($this->emailLoginCommand)
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
