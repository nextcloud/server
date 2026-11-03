<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Session;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Gives the current session a new id while it stays logged in, e.g. after
 * the user proved their identity once more in this session.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface ISessionIdRenewer {
	/**
	 * Renews the id of the current session. The old id stops working.
	 *
	 * The session keeps its auth token: the token keeps its id, name and
	 * stored password and is moved to the new session id. A remember-me
	 * cookie that points to the old session id is updated as well.
	 *
	 * @throws SessionNotAvailableException if there is no session
	 * @since 36.0.0
	 */
	public function renewSessionId(): void;
}
