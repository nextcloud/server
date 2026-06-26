<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Event;

use OCP\AppFramework\Attribute\Dispatchable;
use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Emitted when one or multiple users *might* have gained or lost access to an existing share.
 *
 * For example, when a user is added to a group, they gain access to all shares for the group.
 *
 * @since 33.0.0
 */
#[Dispatchable(since: '33.0.0')]
class UserShareAccessUpdatedEvent extends Event {
	/** @var list<IUser> $users */
	private readonly array $users;

	/**
	 * @param IUser|list<IUser> $users
	 */
	public function __construct(
		IUser|array $users,
	) {
		parent::__construct();
		$this->users = is_array($users) ? $users : [$users];
	}

	/**
	 * @return list<IUser>
	 */
	public function getUsers(): array {
		return $this->users;
	}
}
