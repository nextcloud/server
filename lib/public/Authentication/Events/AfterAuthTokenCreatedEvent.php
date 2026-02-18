<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted after a new authentication token is generated and before it is persisted.
 *
 * Apps may override the token value to enforce custom policies (length, charset, format).
 *
 * @since 34.0.0
 */
class AfterAuthTokenCreatedEvent extends Event {

	/**
	 * @since 34.0.0
	 */
	public function __construct(
		private string $token,
	) {
		parent::__construct();
	}

	/**
	 * @since 34.0.0
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @since 34.0.0
	 */
	public function setToken(string $token): void {
		$this->token = $token;
	}
}