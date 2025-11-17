<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Core\Controller\LoginController;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class UserDisabledCheckCommand extends ALoginCommand {
	public function __construct(
		private IUserManager $userManager,
		private LoggerInterface $logger,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$user = $this->userManager->get($loginData->getUsername());
		if ($user !== null && $user->isEnabled() === false) {
			$username = $loginData->getUsername();
			$ip = $loginData->getRequest()->getRemoteAddress();

			$this->logger->warning("Login failed: $username disabled (Remote IP: $ip)");

			return LoginResult::failure($loginData, LoginController::LOGIN_MSG_USERDISABLED);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
