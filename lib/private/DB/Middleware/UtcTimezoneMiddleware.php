<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Custom doctrine middleware to ensure that the session timezone is set to UTC.
 *
 * @since 34.0.0
 */
final class UtcTimezoneMiddleware implements Middleware {

	#[\Override]
	public function wrap(Driver $driver): Driver {
		return new UtcTimezoneMiddlewareDriver($driver);
	}
}
