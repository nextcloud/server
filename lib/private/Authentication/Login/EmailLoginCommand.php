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
