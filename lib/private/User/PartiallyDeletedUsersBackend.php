<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User;

use OCP\Config\IUserConfig;
use OCP\IUserBackend;
use OCP\User\Backend\IGetHomeBackend;

/**
 * This is a "fake" backend for users that were deleted,
 * but not properly removed from Nextcloud (e.g. an exception occurred).
 * This backend is only needed because some APIs in user-deleted-events require a "real" user with backend.
 */
class PartiallyDeletedUsersBackend extends Backend implements IGetHomeBackend, IUserBackend {

	public function __construct(
		private IUserConfig $config,
	) {
	}

	#[\Override]
	public function deleteUser($uid): bool {
		// fake true, deleting failed users is automatically handled by User::delete()
		return true;
	}

	#[\Override]
	public function getBackendName(): string {
		return 'deleted users';
	}

	#[\Override]
	public function userExists(string $uid): bool {
		return $this->config->getValueBool($uid, 'core', 'deleted');
	}

	#[\Override]
	public function getHome(string $uid): string|false {
		return $this->config->getValueString($uid, 'core', 'deleted.home-path') ?: false;
	}

	#[\Override]
	public function getUsers(string $search = '', ?int $limit = null, ?int $offset = null): array {
		return iterator_to_array($this->config->searchUsersByValueBool('core', 'deleted', true));
	}

	/**
	 * Unmark a user as deleted.
	 * This typically the case if the user deletion failed in the backend but before the backend deleted the user,
	 * meaning the user still exists so we unmark them as it still can be accessed (and deleted) normally.
	 */
	public function unmarkUser(string $userId): void {
		$this->config->deleteUserConfig($userId, 'core', 'deleted');
		$this->config->deleteUserConfig($userId, 'core', 'deleted.home-path');
	}

}
