<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Guard;

use OCP\IRequest;

/**
 * Decides early whether a web request may proceed, e.g. to refuse requests
 * from a banned address on every entry point.
 *
 * Guards run for every web request after the apps were registered and before
 * the session is started, so they must be cheap and must not use the session.
 * A guard that throws is logged and ignored.
 *
 * @since 36.0.0
 */
interface IRequestGuard {
	/**
	 * @return GuardDecision|null a decision to refuse the request, or null to let it proceed
	 * @since 36.0.0
	 */
	public function check(IRequest $request): ?GuardDecision;
}
