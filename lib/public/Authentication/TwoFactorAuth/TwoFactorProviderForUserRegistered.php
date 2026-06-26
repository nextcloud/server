<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 28.0.0
 */
class TwoFactorProviderForUserRegistered extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IUser $user,
		private IProvider $provider,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getProvider(): IProvider {
		return $this->provider;
	}

	/**
	 * @since 28.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
