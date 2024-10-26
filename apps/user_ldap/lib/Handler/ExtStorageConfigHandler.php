<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Handler;

use OCA\Files_External\Config\IConfigHandler;
use OCA\Files_External\Config\SimpleSubstitutionTrait;
use OCA\Files_External\Config\UserContext;
use OCA\User_LDAP\User_Proxy;

class ExtStorageConfigHandler extends UserContext implements IConfigHandler {
	use SimpleSubstitutionTrait;

	/**
	 * @param mixed $optionValue
	 * @return mixed the same type as $optionValue
	 * @since 16.0.0
	 * @throws \Exception
	 */
	public function handle($optionValue) {
		$this->placeholder = 'home';
		$user = $this->getUser();

		if ($user === null) {
			return $optionValue;
		}

		$backend = $user->getBackend();
		if (!$backend instanceof User_Proxy) {
			return $optionValue;
		}

		$access = $backend->getLDAPAccess($user->getUID());
		if (!$access) {
			return $optionValue;
		}

		$attribute = $access->connection->ldapExtStorageHomeAttribute;
		if (empty($attribute)) {
			return $optionValue;
		}

		$ldapUser = $access->userManager->get($user->getUID());
		$extHome = $ldapUser !== null ? $ldapUser->getExtStorageHome() : '';

		return $this->processInput($optionValue, $extHome);
	}
}
