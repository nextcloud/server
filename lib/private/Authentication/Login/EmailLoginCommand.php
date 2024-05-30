<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OCP\IUserManager;

class EmailLoginCommand extends ALoginCommand {
	/** @var IUserManager */
	private $userManager;

	public function __construct(IUserManager $userManager) {
		$this->userManager = $userManager;
	}

	public function process(LoginData $loginData): LoginResult {
		if ($loginData->getUser() === false) {
			if (!filter_var($loginData->getUsername(), FILTER_VALIDATE_EMAIL)) {
				return $this->processNextOrFinishSuccessfully($loginData);
			}

			$users = $this->userManager->getByEmail($loginData->getUsername());
			// we only allow login by email if unique
			if (count($users) === 1) {
				// FIXME: This is a workaround to still stick to configured LDAP login filters
				// this can be removed once the email login is properly implemented in the local user backend
				// as described in https://github.com/nextcloud/server/issues/5221
				if ($users[0]->getBackendClassName() === 'LDAP') {
					return $this->processNextOrFinishSuccessfully($loginData);
				}

				$username = $users[0]->getUID();
				if ($username !== $loginData->getUsername()) {
					$user = $this->userManager->checkPassword(
						$username,
						$loginData->getPassword()
					);
					if ($user !== false) {
						$loginData->setUser($user);
						$loginData->setUsername($username);
					}
				}
			}
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
