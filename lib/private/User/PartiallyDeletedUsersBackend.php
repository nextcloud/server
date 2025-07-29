<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\User;

use OCP\IConfig;
use OCP\IUserBackend;
use OCP\User\Backend\IGetHomeBackend;

/**
 * This is a "fake" backend for users that were deleted,
 * but not properly removed from Nextcloud (e.g. an exception occurred).
 * This backend is only needed because some APIs in user-deleted-events require a "real" user with backend.
 */
class PartiallyDeletedUsersBackend extends Backend implements IGetHomeBackend, IUserBackend {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function deleteUser($uid): bool {
		// fake true, deleting failed users is automatically handled by User::delete()
		return true;
	}

	public function getBackendName(): string {
		return 'deleted users';
	}

	public function userExists($uid) {
		return $this->config->getUserValue($uid, 'core', 'deleted') === 'true';
	}

	public function getHome(string $uid): string|false {
		return $this->config->getUserValue($uid, 'core', 'deleted.home-path') ?: false;
	}

	public function getUsers($search = '', $limit = null, $offset = null) {
		return $this->config->getUsersForUserValue('core', 'deleted', 'true');
	}

	/**
	 * Unmark a user as deleted.
	 * This typically the case if the user deletion failed in the backend but before the backend deleted the user,
	 * meaning the user still exists so we unmark them as it still can be accessed (and deleted) normally.
	 */
	public function unmarkUser(string $userId): void {
		$this->config->deleteUserValue($userId, 'core', 'deleted');
		$this->config->deleteUserValue($userId, 'core', 'deleted.home-path');
	}

}
