<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\User;

use OCP\IUser;
use OCP\IUserManager;
use OCP\UserInterface;

class FakeUserManager implements IUserManager {

	/** @var UserInterface[] */
	private array $backends = [];
	/** @var array<string, IUser> */
	private array $users = [];

	#[\Override]
	public function registerBackend(UserInterface $backend) {
		$this->backends[] = $backend;
	}

	#[\Override]
	public function getBackends() {
		return $this->backends;
	}

	#[\Override]
	public function removeBackend(UserInterface $backend) {
		$this->backends = array_filter($this->backends, fn ($b) => $b !== $backend);
	}

	#[\Override]
	public function clearBackends() {
		$this->backends = [];
	}

	#[\Override]
	public function get($uid) {
		return $this->users[$uid] ?? null;
	}

	#[\Override]
	public function getDisplayName(string $uid): ?string {
		return $this->get($uid)?->getDisplayName();
	}

	#[\Override]
	public function userExists($uid) {
		return $this->get($uid) !== null;
	}

	#[\Override]
	public function checkPassword($loginName, $password) {
		return $this->get($loginName)->getPasswordHash() === md5($password);
	}

	#[\Override]
	public function search($pattern, $limit = null, $offset = 0) {
		$results = array_filter($this->users, fn (IUser $user) => str_contains($user->getUID(), $pattern));
		if ($limit !== null) {
			return array_slice($results, $offset, $limit);
		}
		return $results;
	}

	#[\Override]
	public function searchDisplayName($pattern, $limit = null, $offset = 0) {
		$results = array_filter($this->users, fn (IUser $user) => str_contains($user->getDisplayName(), $pattern));
		if ($limit !== null) {
			return array_slice($results, $offset, $limit);
		}
		return $results;
	}

	#[\Override]
	public function getDisabledUsers(?int $limit = null, int $offset = 0, string $search = ''): array {
		$results = array_filter($this->users, fn (IUser $user) => !$user->isEnabled() && str_contains($user->getUID(), $search));
		if ($limit !== null) {
			return array_slice($results, $offset, $limit);
		}
		return $results;
	}

	#[\Override]
	public function searchKnownUsersByDisplayName(string $searcher, string $pattern, ?int $limit = null, ?int $offset = null): array {
		throw new \Exception('Fake method not implemented.');
	}

	#[\Override]
	public function createUser($uid, $password) {
		return $this->users[$uid] = new FakeUser($uid, $password, $this);
	}

	#[\Override]
	public function createUserFromBackend($uid, $password, UserInterface $backend) {
		return $this->users[$uid] = new FakeUser($uid, $password, $this, $backend);
	}

	#[\Override]
	public function countUsers(bool $onlyMappedUsers = false) {
		return count($this->users);
	}

	#[\Override]
	public function countUsersTotal(int $limit = 0, bool $onlyMappedUsers = false): int|false {
		return count($this->users);
	}

	#[\Override]
	public function callForAllUsers(\Closure $callback, $search = '') {
		foreach ($this->users as $user) {
			if (str_contains($user->getUID(), $search)) {
				$callback($user);
			}
		}
	}

	#[\Override]
	public function countDisabledUsers() {
		return count(array_filter($this->users, fn (IUser $user) => !$user->isEnabled()));
		}

	#[\Override]
	public function countSeenUsers() {
		return count(array_filter($this->users, fn (IUser $user) => $user->getFirstLogin() > 0));
	}

	#[\Override]
	public function callForSeenUsers(\Closure $callback) {
		foreach ($this->users as $user) {
			if ($user->getFirstLogin() > 0) {
				$callback($user);
			}
		}
	}

	#[\Override]
	public function getByEmail($email) {
		foreach ($this->users as $user) {
			if ($user->getEMailAddress() === $email) {
				return $user;
			}
		}
		return null;
	}

	#[\Override]
	public function validateUserId(string $uid, bool $checkDataDirectory = false): void {
		throw new \Exception('Fake method not implemented.');
	}

	#[\Override]
	public function getLastLoggedInUsers(?int $limit = null, int $offset = 0, string $search = ''): array {
		$results = array_filter($this->users, fn (IUser $user) => str_contains($user->getUID(), $search));
		usort($results, fn (IUser $a, IUser $b) => $b->getLastLogin() <=> $a->getLastLogin());
		return array_slice($results, $offset, $limit);
	}

	#[\Override]
	public function getSeenUsers(int $offset = 0, ?int $limit = null): \Iterator {
		$results = array_filter($this->users, fn (IUser $user) => $user->getFirstLogin() > 0);
		return new \ArrayIterator(array_slice($results, $offset, $limit));
	}

	#[\Override]
	public function getExistingUser(string $userId, ?string $displayName = null): IUser {
		return $this->users[$userId];
	}

	#[\Override]
	public function getAvatarUrlLight(string $userId, int $size): string {
		return 'core/avatar/getAvatar?userId=' . $userId . '&size=' . $size;
	}

	#[\Override]
	public function getAvatarUrlDark(string $userId, int $size): string {
		return 'core/avatar/getAvatarDark?userId=' . $userId . '&size=' . $size;
	}

	public function deleteUser(IUser $user): bool {
		unset($this->users[$user->getUID()]);
		return true;
	}
}
