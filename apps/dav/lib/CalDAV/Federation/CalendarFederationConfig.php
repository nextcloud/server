<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCP\AppFramework\Services\IAppConfig;

class CalendarFederationConfig {
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	public function isFederationEnabled(): bool {
		return $this->appConfig->getAppValueBool('enableCalendarFederation', true);
	}
}
