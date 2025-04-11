<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Authentication\Events\LoginFailed;
use OC\Core\Controller\LoginController;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class LoggedInCheckCommand extends ALoginCommand {
	/** @var LoggerInterface */
	private $logger;
	/** @var IEventDispatcher */
	private $dispatcher;

	public function __construct(LoggerInterface $logger,
		IEventDispatcher $dispatcher) {
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
	}

	public function process(LoginData $loginData): LoginResult {
		if ($loginData->getUser() === false) {
			$loginName = $loginData->getUsername();
			$password = $loginData->getPassword();
			$ip = $loginData->getRequest()->getRemoteAddress();

			$this->logger->warning("Login failed: $loginName (Remote IP: $ip)");

			$this->dispatcher->dispatchTyped(new LoginFailed($loginName, $password));

			return LoginResult::failure($loginData, LoginController::LOGIN_MSG_INVALIDPASSWORD);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
