<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\Events;

use OCP\Authentication\Token\IToken;
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
		private IToken $token,
	) {
		parent::__construct();
	}

	/**
	 * returns the token that has been invalidated
	 *
	 * @since 32.0.0
	 */
	public function getToken(): IToken {
		return $this->token;
	}
}
