<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use OCP\EventDispatcher\JsonSerializer;
use OCP\IUser;

/**
 * @since 28.0.0
 */
class UserFirstTimeLoggedInEvent extends Event implements IWebhookCompatibleEvent {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IUser $user,
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
	 * @since 34.0.0
	 */
	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			'user' => JsonSerializer::serializeUser($this->user)
		];
	}
}
