<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Authentication\Events;

use OCP\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;

/**
 * Dispatched when a user granted a login flow v2 request, after the app
 * password for the new client was created.
 *
 * The request that grants the flow may come from another address than the
 * one that started it, e.g. when someone sends the user a link to a flow
 * they started themselves.
 *
 * @since 36.0.0
 */
class LoginFlowV2GrantedEvent extends Event {
	/**
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly IToken $token,
		private readonly ?string $initiatorIp,
		private readonly int $startedAt,
	) {
		parent::__construct();
	}

	/**
	 * The app password created for the client
	 *
	 * @since 36.0.0
	 */
	public function getToken(): IToken {
		return $this->token;
	}

	/**
	 * The address the flow was started from, or null for flows started
	 * before it was recorded
	 *
	 * @since 36.0.0
	 */
	public function getInitiatorIp(): ?string {
		return $this->initiatorIp;
	}

	/**
	 * When the flow was started, as a Unix timestamp
	 *
	 * @since 36.0.0
	 */
	public function getStartedAt(): int {
		return $this->startedAt;
	}
}
