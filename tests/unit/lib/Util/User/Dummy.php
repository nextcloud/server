<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Util\User;

use OC\User\Backend;
use OCP\IUserBackend;

/**
 * dummy user backend, does not keep state, only for testing use
 */
class Dummy extends Backend implements IUserBackend {
	private array $users = [];
	private array $displayNames = [];

	public function createUser($uid, $password): bool {
		if (isset($this->users[$uid])) {
			return false;
		} else {
			$this->users[$uid] = $password;
			return true;
		}
	}

	#[\Override]
	public function deleteUser($uid): bool {
		if (isset($this->users[$uid])) {
			unset($this->users[$uid]);
			return true;
		} else {
			return false;
		}
	}

	public function setPassword($uid, $password): bool {
		if (isset($this->users[$uid])) {
			$this->users[$uid] = $password;
			return true;
		} else {
			return false;
		}
	}

	public function checkPassword($uid, $password): string|false {
		if (isset($this->users[$uid]) && $this->users[$uid] === $password) {
			return $uid;
		}

		return false;
	}

	public function loginName2UserName($loginName): string|false {
		if (isset($this->users[strtolower($loginName)])) {
			return strtolower($loginName);
		}
		return false;
	}

	#[\Override]
	public function getUsers($search = '', $limit = null, $offset = null): array {
		if (empty($search)) {
			return array_keys($this->users);
		}
		$result = [];
		foreach (array_keys($this->users) as $user) {
			if (stripos($user, $search) !== false) {
				$result[] = $user;
			}
		}
		return $result;
	}

	#[\Override]
	public function userExists($uid): bool {
		return isset($this->users[$uid]);
	}

	#[\Override]
	public function hasUserListings(): bool {
		return true;
	}

	public function countUsers(): int {
		return 0;
	}

	public function setDisplayName($uid, $displayName): bool {
		$this->displayNames[$uid] = $displayName;
		return true;
	}

	#[\Override]
	public function getDisplayName($uid): string {
		return $this->displayNames[$uid] ?? $uid;
	}

	#[\Override]
	public function getBackendName(): string {
		return 'Dummy';
	}
}
