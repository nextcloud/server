<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 28.0.0
 */
final class TwoFactorProviderUserDeleted extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IUser $user,
		private string $providerId,
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
	public function getProviderId(): string {
		return $this->providerId;
	}
}
