<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar;

interface ITimezoneService {
	public function getUserTimezone(string $userId): ?string;

	public function getDefaultTimezone(): string;

}
