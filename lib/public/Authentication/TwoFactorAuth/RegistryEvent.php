<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 15.0.0
 * @deprecated 28.0.0 Use TwoFactorProviderForUserRegistered or TwoFactorProviderForUserUnregistered instead
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered
 */
class RegistryEvent extends Event {
	private IProvider $provider;

	private IUser $user;

	/**
	 * @since 15.0.0
	 */
	public function __construct(IProvider $provider, IUser $user) {
		parent::__construct();
		$this->provider = $provider;
		$this->user = $user;
	}

	/**
	 * @since 15.0.0
	 */
	public function getProvider(): IProvider {
		return $this->provider;
	}

	/**
	 * @since 15.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
