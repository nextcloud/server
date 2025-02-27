<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Authentication\Login;

class Chain {
	public function __construct(
		private PreLoginHookCommand $preLoginHookCommand,
		private UserDisabledCheckCommand $userDisabledCheckCommand,
		private UidLoginCommand $uidLoginCommand,
		private EmailLoginCommand $emailLoginCommand,
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
			->setNext($this->emailLoginCommand)
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
