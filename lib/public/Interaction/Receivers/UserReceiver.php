<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Interaction\Receivers;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Interaction\InteractionReceiver;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use RuntimeException;

/**
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class UserReceiver implements InteractionReceiver {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly string $userId,
		private ?IUser $user = null,
	) {
	}

	/**
	 * @since 34.0.2
	 */
	public function getUser(): IUser {
		if ($this->user instanceof IUser) {
			return $this->user;
		}

		$user = Server::get(IUserManager::class)->get($this->userId);
		if ($user === null) {
			throw new RuntimeException('User does not exist: ' . $this->userId);
		}

		return $this->user = $user;
	}

	/**
	 * @since 34.0.2
	 */
	#[\Override]
	public function getID(): string {
		return $this->userId;
	}
}
