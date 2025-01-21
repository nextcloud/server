<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Authentication\Token\IToken;
use OC\User\Session;
use OCP\IConfig;

class CreateSessionTokenCommand extends ALoginCommand {

	public function __construct(
		private IConfig $config,
		private Session $userSession,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$tokenType = IToken::REMEMBER;
		if ($this->config->getSystemValueInt('remember_login_cookie_lifetime', 60 * 60 * 24 * 15) === 0) {
			$loginData->setRememberLogin(false);
			$tokenType = IToken::DO_NOT_REMEMBER;
		}

		$userId = $loginData->getUser()->getUID();
		if ($loginData->getPassword() === '') {
			$this->userSession->createSessionToken(
				$loginData->getRequest(),
				$userId,
				$loginData->getUsername(),
				null,
				$tokenType
			);
			$this->userSession->updateTokens(
				$userId,
				''
			);
		} else {
			$this->userSession->createSessionToken(
				$loginData->getRequest(),
				$userId,
				$loginData->getUsername(),
				$loginData->getPassword(),
				$tokenType
			);
			$this->userSession->updateTokens(
				$userId,
				$loginData->getPassword()
			);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
