<?php

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCP\IConfig;
use OCP\PreConditionNotMetException;
use OCP\Share\IManager;

/**
 * Class DeletedUsersIndex
 * @package OCA\User_LDAP
 */
class DeletedUsersIndex {
	protected ?array $deletedUsers = null;

	public function __construct(
		protected IConfig $config,
		protected UserMapping $mapping,
		private IManager $shareManager,
	) {
	}

	/**
	 * reads LDAP users marked as deleted from the database
	 * @return OfflineUser[]
	 */
	private function fetchDeletedUsers(): array {
		$deletedUsers = $this->config->getUsersForUserValue('user_ldap', 'isDeleted', '1');

		$userObjects = [];
		foreach ($deletedUsers as $user) {
			$userObject = new OfflineUser($user, $this->config, $this->mapping, $this->shareManager);
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
	 * returns all LDAP users that are marked as deleted
	 * @return OfflineUser[]
	 */
	public function getUsers(): array {
		if (is_array($this->deletedUsers)) {
			return $this->deletedUsers;
		}
		return $this->fetchDeletedUsers();
	}

	/**
	 * whether at least one user was detected as deleted
	 */
	public function hasUsers(): bool {
		if (!is_array($this->deletedUsers)) {
			$this->fetchDeletedUsers();
		}
		return is_array($this->deletedUsers) && (count($this->deletedUsers) > 0);
	}

	/**
	 * marks a user as deleted
	 *
	 * @throws PreConditionNotMetException
	 */
	public function markUser(string $ocName): void {
		if ($this->isUserMarked($ocName)) {
			// the user is already marked, do not write to DB again
			return;
		}
		$this->config->setUserValue($ocName, 'user_ldap', 'isDeleted', '1');
		$this->config->setUserValue($ocName, 'user_ldap', 'foundDeleted', (string)time());
		$this->deletedUsers = null;
	}

	public function isUserMarked(string $ocName): bool {
		return ($this->config->getUserValue($ocName, 'user_ldap', 'isDeleted', '0') === '1');
	}
}
