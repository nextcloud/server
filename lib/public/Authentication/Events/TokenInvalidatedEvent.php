<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted when an authentication token is invalidated
 *
 * @since 32.0.0
 */
class TokenInvalidatedEvent extends Event {

	/**
	 * @since 32.0.0
	 */
	public function __construct(
		private string $userId,
		private int $tokenId,
	) {
		parent::__construct();
	}

	/**
	 * returns the uid of the user associated with the invalidated token
	 *
	 * @since 32.0.0
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * returns the ID of the token that is being invalidated
	 *
	 * @since 32.0.0
	 */
	public function getTokenId(): int {
		return $this->tokenId;
	}
}
