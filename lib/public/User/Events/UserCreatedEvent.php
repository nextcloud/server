<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Emitted when a new user has been created on the back-end.
 *
 * @since 18.0.0
 */
#[Listenable(since: '18.0.0')]
#[Dispatchable(since: '18.0.0')]
class UserCreatedEvent extends Event {
	/**
	 * @since 18.0.0
	 */
	public function __construct(
		private readonly IUser $user,
		private readonly string $password,
	) {
		parent::__construct();
	}

	/**
	 * @since 18.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 18.0.0
	 */
	public function getUid(): string {
		return $this->user->getUID();
	}

	/**
	 * @since 18.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}
}
