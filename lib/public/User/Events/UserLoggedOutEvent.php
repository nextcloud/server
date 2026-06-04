<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Emitted when a user has been logged out successfully.
 *
 * @since 18.0.0
 */
class UserLoggedOutEvent extends Event {
	/** @var IUser|null */
	private $user;

	/**
	 * @since 18.0.0
	 */
	public function __construct(?IUser $user = null) {
		parent::__construct();
		$this->user = $user;
	}

	/**
	 * @since 18.0.0
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}
}
