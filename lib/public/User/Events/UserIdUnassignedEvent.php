<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted after removing the mapping between an external user and an internal userid
 * @since 31.0.0
 */
class UserIdUnassignedEvent extends Event {
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
