<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted by backends (like user_ldap) when a user created externally is mapped for the first time and assigned a userid
 * @since 31.0.0
 */
class UserIdAssignedEvent extends Event {
	/**
	 * @since 31.0.0
	 */
	public function __construct(
		private readonly string $userId,
	) {
		parent::__construct();
	}

	/**
	 * @since 31.0.0
	 */
	public function getUserId(): string {
		return $this->userId;
	}
}
