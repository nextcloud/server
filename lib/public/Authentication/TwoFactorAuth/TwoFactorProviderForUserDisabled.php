<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 22.0.0
 * @deprecated 28.0.0 Use \OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed instead
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed
 */
class TwoFactorProviderForUserDisabled extends Event {
	/** @var IProvider */
	private $provider;

	/** @var IUser */
	private $user;

	/**
	 * @since 22.0.0
	 */
	public function __construct(IUser $user, IProvider $provider) {
		$this->user = $user;
		$this->provider = $provider;
	}

	/**
	 * @return IUser
	 * @since 22.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @return IProvider
	 * @since 22.0.0
	 */
	public function getProvider(): IProvider {
		return $this->provider;
	}
}
