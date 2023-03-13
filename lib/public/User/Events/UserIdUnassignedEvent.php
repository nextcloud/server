<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 31.0.0
 */
class UserIdUnassignedEvent extends Event {
	/**
	 * @since 31.0.0
	 */
	public function __construct(
		private string $userId,
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
