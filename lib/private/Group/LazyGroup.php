<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Group;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class LazyGroup implements IGroup {
	private ?IGroup $group = null;
	private ?bool $isDeleted = null;

	public function __construct(
		private string $gid,
		private IGroupManager $groupManager,
	) {
	}

	#[\Override]
	public function getGID(): string {
		return $this->gid;
	}

	private function getGroup(): IGroup {
		if ($this->group === null) {
			$this->group = $this->groupManager->get($this->gid);
			;
		}
		if ($this->group === null) {
			Server::get(LoggerInterface::class)->debug('Trying to use the deleted group: "' . $this->gid . '"', ['app' => 'core']);
			$this->isDeleted = true;
			$this->group = new Group($this->gid, [], Server::get(IEventDispatcher::class), Server::get(IUserManager::class));
		} else {
			$this->isDeleted = false;
		}
		return $this->group;
	}

	#[\Override]
	public function getDisplayName(): string {
		// Use display name cache from IGroupManager
		return $this->groupManager->getDisplayName($this->gid);
	}

	#[\Override]
	public function setDisplayName(string $displayName): bool {
		return $this->group->setDisplayName($displayName);
	}

	#[\Override]
	public function getUsers(): array {
		return $this->getGroup()->getUsers();
	}

	#[\Override]
	public function inGroup(IUser $user): bool {
		return $this->getGroup()->inGroup($user);
	}

	#[\Override]
	public function addUser(IUser $user): void {
		$this->getGroup()->addUser($user);
	}

	#[\Override]
	public function removeUser(IUser $user): void {
		$this->getGroup()->removeUser($user);
	}

	#[\Override]
	public function searchUsers(string $search, ?int $limit = null, ?int $offset = null): array {
		return $this->getGroup()->searchUsers($search, $limit, $offset);
	}

	#[\Override]
	public function count($search = ''): int|bool {
		return $this->getGroup()->count($search);
	}

	#[\Override]
	public function countDisabled(): int|bool {
		return $this->getGroup()->countDisabled();
	}

	#[\Override]
	public function searchDisplayName(string $search, ?int $limit = null, ?int $offset = null): array {
		return $this->getGroup()->searchDisplayName($search, $limit, $offset);
	}

	#[\Override]
	public function getBackendNames(): array {
		return $this->getGroup()->getBackendNames();
	}

	#[\Override]
	public function delete(): bool {
		return $this->getGroup()->delete();
	}

	#[\Override]
	public function canRemoveUser(): bool {
		return $this->getGroup()->canRemoveUser();
	}

	#[\Override]
	public function canAddUser(): bool {
		return $this->getGroup()->canAddUser();
	}

	#[\Override]
	public function hideFromCollaboration(): bool {
		return $this->getGroup()->hideFromCollaboration();
	}

	#[\Override]
	public function isDeleted(): bool {
		if ($this->isDeleted === null) {
			$this->getGroup();
		}
		return $this->isDeleted === true ? true : $this->getGroup()->isDeleted();
	}
}
