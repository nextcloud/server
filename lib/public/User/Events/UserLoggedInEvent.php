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
 * @since 18.0.0
 */
class UserLoggedInEvent extends Event {
	/**
	 * @since 18.0.0
	 */
	public function __construct(
		private IUser $user,
		private string $loginName,
		private ?string $password,
		private bool $isTokenLogin,
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
	 * @since 31.0.0
	 */
	public function getUid(): string {
		return $this->user->getUID();
	}

	/**
	 * @since 21.0.0
	 */
	public function getLoginName(): string {
		return $this->loginName;
	}

	/**
	 * @since 18.0.0
	 */
	public function getPassword(): ?string {
		return $this->password;
	}

	/**
	 * @since 18.0.0
	 */
	public function isTokenLogin(): bool {
		return $this->isTokenLogin;
	}
}
