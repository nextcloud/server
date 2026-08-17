<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\User_LDAP\Service;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCP\IUserManager;
use OCP\User\Exceptions\UserNotFoundException;

class CheckUserService {
	public function __construct(
		protected readonly User_Proxy $backend,
		protected readonly Helper $helper,
		protected readonly DeletedUsersIndex $dui,
		protected readonly UserMapping $mapping,
		protected readonly IUserManager $userManager,
	) {
	}

	/**
	 * @param string $uid
	 * @param bool $update
	 * @return array{exists: true, wasMapped: true, attributes?: array<string, string>}
	 * @throws UserNotFoundException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function checkUser(string $uid, bool $update): int {
		if ($this->backend->getLDAPAccess($uid)->stringResemblesDN($uid)) {
			$username = $this->backend->dn2UserName($uid);
			if ($username !== false) {
				$uid = $username;
			}
		}
		$wasMapped = $this->userWasMapped($uid);
		$exists = $this->backend->userExistsOnLDAP($uid, true);
		if ($exists === true) {
			if ($update) {
				$attributes = $this->updateUser($uid);
				return ['exists' => true, 'wasMapped' => $wasMapped, 'attributes' => $attributes];
			}
			return ['exists' => true, 'wasMapped' => $wasMapped];
		}

		if (!$wasMapped) {
			throw new UserNotFoundException('The given user is not a recognized LDAP user.');
		}

		$this->dui->markUser($uid);
		return ['exists' => true, 'wasMapped' => $wasMapped];
	}

	/**
	 * checks whether a user is actually mapped
	 * @param string $ocName the username as used in Nextcloud
	 */
	protected function userWasMapped(string $ocName): bool {
		$dn = $this->mapping->getDNByName($ocName);
		return $dn !== false;
	}

	/**
	 * Checks whether the setup allows reliable checking of LDAP user existence
	 */
	public function assertAllowed(bool $force): bool {
		// we don't check ldapUserCleanupInterval from config.php because this
		// action is triggered manually, while the setting only controls the
		// background job.
		return !$this->helper->haveDisabledConfigurations() || $force;
	}

	/**
	 * @param string $uid
	 * @return array<string, string> The attributes
	 */
	private function updateUser(string $uid): array {
		try {
			$access = $this->backend->getLDAPAccess($uid);
			$attrs = $access->userManager->getAttributes();
			$user = $access->userManager->get($uid);
			$avatarAttributes = $access->getConnection()->resolveRule('avatar');
			$baseDn = $this->helper->DNasBaseParameter($user->getDN());
			$result = $access->search('objectclass=*', $baseDn, $attrs, 1, 0);
			$attributes = [];
			foreach ($result[0] as $attribute => $valueSet) {
				foreach ($valueSet as $value) {
					if (in_array($attribute, $avatarAttributes)) {
						$value = '{ImageData}';
					}
					$attributes[$attribute] = $value;
				}
			}
			$access->batchApplyUserAttributes($result);
			return $attributes;
		} catch (\Exception $e) {
			throw new \Exception('Error while trying to lookup and update attributes from LDAP', previous: $e);
		}
	}
}
