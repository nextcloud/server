<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Authentication\Token\IToken;
use OC\User\Session;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IURLGenerator;

class CreateSessionTokenCommand extends ALoginCommand {
	private const EPHEMERAL_SESSION_TTL = 5 * 60; // 5 minutes

	public function __construct(
		private IConfig $config,
		private Session $userSession,
		private IURLGenerator $urlGenerator,
		private ITimeFactory $timeFactory,
	) {
	}

	#[\Override]
	public function process(LoginData $loginData): LoginResult {
		if ($this->config->getSystemValueInt('remember_login_cookie_lifetime', 60 * 60 * 24 * 15) === 0) {
			$loginData->setRememberLogin(false);
		}
		if ($loginData->isRememberLogin()) {
			$tokenType = IToken::REMEMBER;
		} else {
			$tokenType = IToken::DO_NOT_REMEMBER;
		}

		$loginV2GrantRoute = $this->urlGenerator->linkToRoute('core.ClientFlowLoginV2.grantPage');
		$expires = null;
		if (str_starts_with($loginData->getRedirectUrl() ?? '', $loginV2GrantRoute)) {
			$expires = $this->timeFactory->getTime() + self::EPHEMERAL_SESSION_TTL;
		}

		if ($loginData->getPassword() === '') {
			$this->userSession->createSessionToken(
				$loginData->getRequest(),
				$loginData->getUser()->getUID(),
				$loginData->getUsername(),
				null,
				$tokenType,
				$expires,
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
				$tokenType,
				$expires,
			);
			$this->userSession->updateTokens(
				$loginData->getUser()->getUID(),
				$loginData->getPassword()
			);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
