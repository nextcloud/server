<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;

/**
 * Asks the user of the current session for their second factor again, e.g.
 * before a sensitive action in a session that looks suspicious.
 *
 * @since 36.0.0
 */
interface IChallengeRequester {
	/**
	 * Whether the current session can be challenged: the user has a second
	 * factor and the session was not created with an app password.
	 *
	 * @param IUser $user the user of the current session
	 * @since 36.0.0
	 */
	public function isAvailable(IUser $user): bool;

	/**
	 * Marks the current session as needing the second factor again. Until it is
	 * solved, every request of the session is redirected to the challenge.
	 *
	 * @param IUser $user the user of the current session
	 * @param string $redirectUrl where to go after the challenge was solved
	 * @return string the URL of the challenge
	 * @throws \LogicException if the session cannot be challenged, see isAvailable()
	 * @since 36.0.0
	 */
	public function requestChallenge(IUser $user, string $redirectUrl): string;
}
