<?php

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCP\Config\IUserConfig;
use OCP\PreConditionNotMetException;
use OCP\Share\IManager;

/**
 * Class DeletedUsersIndex
 * @package OCA\User_LDAP
 */
class DeletedUsersIndex {
	protected ?array $deletedUsers = null;

	public function __construct(
		protected IUserConfig $userConfig,
		protected UserMapping $mapping,
		private IManager $shareManager,
	) {
	}

	/**
	 * Reads LDAP users marked as deleted from the database.
	 *
	 * @return OfflineUser[]
	 */
	private function fetchDeletedUsers(): array {
		$deletedUsers = $this->userConfig->searchUsersByValueBool('user_ldap', 'isDeleted', true);

		$userObjects = [];
		foreach ($deletedUsers as $user) {
			$userObject = new OfflineUser($user, $this->userConfig, $this->mapping, $this->shareManager);
			if ($userObject->getLastLogin() > $userObject->getDetectedOn()) {
				$userObject->unmark();
			} else {
				$userObjects[] = $userObject;
			}
		}
		$this->deletedUsers = $userObjects;

		return $this->deletedUsers;
	}

	/**
	 * Returns all LDAP users that are marked as deleted.
	 *
	 * @return OfflineUser[]
	 */
	public function getUsers(): array {
		if (is_array($this->deletedUsers)) {
			return $this->deletedUsers;
		}
		return $this->fetchDeletedUsers();
	}

	/**
	 * Whether at least one user was detected as deleted.
	 */
	public function hasUsers(): bool {
		if (!is_array($this->deletedUsers)) {
			$this->fetchDeletedUsers();
		}
		return is_array($this->deletedUsers) && (count($this->deletedUsers) > 0);
	}

	/**
	 * Marks a user as deleted.
	 *
	 * @throws PreConditionNotMetException
	 */
	public function markUser(string $ocName): void {
		if ($this->isUserMarked($ocName)) {
			// the user is already marked, do not write to DB again
			return;
		}
		$this->userConfig->setValueBool($ocName, 'user_ldap', 'isDeleted', true);
		$this->userConfig->setValueInt($ocName, 'user_ldap', 'foundDeleted', time());
		$this->deletedUsers = null;
	}

	public function isUserMarked(string $ocName): bool {
		return $this->userConfig->getValueBool($ocName, 'user_ldap', 'isDeleted');
	}
}
