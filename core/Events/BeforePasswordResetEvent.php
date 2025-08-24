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
 * Emitted before the user password is reset.
 *
 * @since 25.0.0
 */
class BeforePasswordResetEvent extends Event {
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
	 * @since 31.0.0
	 */
	public function getUid(): string {
		return $this->user->getUID();
	}

	/**
	 * @since 25.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}
}
