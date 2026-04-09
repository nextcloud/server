<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Used to notify the filesystem setup manager that the available mounts for a user have changed
 *
 * @since 24.0.0
 */
class InvalidateMountCacheEvent extends Event {
	private ?IUser $user;

	/**
	 * @param IUser|null $user user
	 *
	 * @since 24.0.0
	 */
	public function __construct(?IUser $user) {
		parent::__construct();
		$this->user = $user;
	}

	/**
	 * @return IUser|null user
	 *
	 * @since 24.0.0
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}
}
