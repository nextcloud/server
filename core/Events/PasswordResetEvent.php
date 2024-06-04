<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Emitted after the user password is reset.
 *
 * @since 25.0.0
 */
class PasswordResetEvent extends Event {
	/**
	 * @since 25.0.0
	 */
	public function __construct(
		private IUser $user,
		private string $password,
	) {
		parent::__construct();
	}

	/**
	 * @since 25.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 25.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}
}
