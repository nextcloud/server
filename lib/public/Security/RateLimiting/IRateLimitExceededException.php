<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\RateLimiting;

use Throwable;

/**
 * Thrown if the (anonymous) user has exceeded a rate limit
 *
 * @since 28.0.0
 */
interface IRateLimitExceededException extends Throwable {
}
