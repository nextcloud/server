<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\Events;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * Dispatched when a public share link is accessed with a token that does not
 * exist, or with a wrong share password, on the share page or over WebDAV.
 *
 * @since 36.0.0
 */
class ShareLinkAccessFailedEvent extends Event {
	/**
	 * @since 36.0.0
	 */
	public const REASON_NOT_FOUND = 'not_found';

	/**
	 * @since 36.0.0
	 */
	public const REASON_WRONG_PASSWORD = 'wrong_password';

	/**
	 * @param self::REASON_* $reason
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly string $token,
		private readonly ?IShare $share,
		private readonly string $reason,
	) {
		parent::__construct();
	}

	/**
	 * The token that was used
	 *
	 * @since 36.0.0
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * The share, or null if no share exists for the token
	 *
	 * @since 36.0.0
	 */
	public function getShare(): ?IShare {
		return $this->share;
	}

	/**
	 * @return self::REASON_*
	 * @since 36.0.0
	 */
	public function getReason(): string {
		return $this->reason;
	}
}
