<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Accounts;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * This event is triggered when the account data of a user was updated.
 *
 * @since 28.0.0
 */
class UserUpdatedEvent extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		protected IUser $user,
		protected array $data,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 28.0.0
	 */
	public function getData(): array {
		return $this->data;
	}
}
