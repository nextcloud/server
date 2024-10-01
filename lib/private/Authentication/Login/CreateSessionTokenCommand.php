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
	/** @var IConfig */
	private $config;

	/** @var Session */
	private $userSession;

	public function __construct(IConfig $config,
		Session $userSession) {
		$this->config = $config;
		$this->userSession = $userSession;
	}

	public function process(LoginData $loginData): LoginResult {
		$tokenType = IToken::REMEMBER;
		if ($this->config->getSystemValueInt('remember_login_cookie_lifetime', 60 * 60 * 24 * 15) === 0) {
			$loginData->setRememberLogin(false);
			$tokenType = IToken::DO_NOT_REMEMBER;
		}

		if ($loginData->getPassword() === '') {
			$this->userSession->createSessionToken(
				$loginData->getRequest(),
				$loginData->getUser()->getUID(),
				$loginData->getUsername(),
				null,
				$tokenType
			);
			$this->userSession->updateTokens(
				$loginData->getUser()->getUID(),
				''
			);
		} else {
			$this->userSession->createSessionToken(
				$loginData->getRequest(),
				$loginData->getUser()->getUID(),
				$loginData->getUsername(),
				$loginData->getPassword(),
				$tokenType
			);
			$this->userSession->updateTokens(
				$loginData->getUser()->getUID(),
				$loginData->getPassword()
			);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
