<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Event;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Emitted when a user *might* have gained or lost access to an existing share.
 *
 * For example, when a user is added to a group, they gain access to all shares for the group.
 *
 * @since 33.0.0
 */
class UserShareAccessUpdatedEvent extends Event {
	public function __construct(
		private readonly IUser $user,
	) {
		parent::__construct();
	}

	public function getUser(): IUser {
		return $this->user;
	}
}
