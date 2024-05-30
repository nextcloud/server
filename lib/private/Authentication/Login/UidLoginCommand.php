<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\User\Manager;
use OCP\IUser;

class UidLoginCommand extends ALoginCommand {
	/** @var Manager */
	private $userManager;

	public function __construct(Manager $userManager) {
		$this->userManager = $userManager;
	}

	/**
	 * @param LoginData $loginData
	 *
	 * @return LoginResult
	 */
	public function process(LoginData $loginData): LoginResult {
		/* @var $loginResult IUser */
		$user = $this->userManager->checkPasswordNoLogging(
			$loginData->getUsername(),
			$loginData->getPassword()
		);

		$loginData->setUser($user);

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
