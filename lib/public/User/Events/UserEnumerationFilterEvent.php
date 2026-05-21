<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * @brief This event is triggered when a list of users is returned to the user.
 *
 * Applications that restricts the enumeration of users should listen to this event
 * and modify the list of users with the getUsers and setUsers method.
 *
 * @since 31.0.14
 */
#[Consumable(since: '31.0.14')]
#[Listenable(since: '31.0.14')]
class UserEnumerationFilterEvent extends Event {
	/** @var list<string> $initialUsers */
	private readonly array $initialUsers;

	/**
	 * @param list<string> $users
	 * @since 31.0.14
	 */
	public function __construct(
		private array $users,
	) {
		$this->initialUsers = $users;
		parent::__construct();
	}

	/**
	 * @return list<string>
	 * @since 31.0.14
	 */
	public function getUsers(): array {
		return $this->users;
	}

	/**
	 * @param list<string> $users
	 * @since 31.0.14
	 */
	public function setUsers(array $users): void {
		$this->users = $users;
	}

	/**
	 * Get the users what were filtered out by one of the listeners of this event.
	 *
	 * @return array<int<0, max>, string> $users
	 * @since 31.0.14
	 */
	public function getFilteredOutUsers(): array {
		return array_diff($this->initialUsers, $this->users);
	}
}
