<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar;

/**
 * The timezone service can be used to conveniently get a user's timezone string
 *
 * @since 34.0.0
 */
interface ITimezoneService {
	public function getUserTimezone(string $userId): ?string;

	public function getDefaultTimezone(): string;
}
