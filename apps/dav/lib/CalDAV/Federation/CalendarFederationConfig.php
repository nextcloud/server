<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCP\IAppConfig;

class CalendarFederationConfig {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private \OCP\GlobalScale\IConfig $gsConfig,
	) {
	}

	public function isFederationEnabled(): bool {
		return $this->appConfig->getValueBool('dav', 'enableCalendarFederation', true);
	}

	/**
	 * Check if users are allowed to create federated shares
	 */
	public function isOutgoingServer2serverShareEnabled(): bool {
		if ($this->gsConfig->onlyInternalFederation()) {
			return false;
		}
		return $this->appConfig->getValueBool('files_sharing', 'outgoing_server2server_share_enabled', true);
	}

	/**
	 * Check if users are allowed to receive federated shares
	 */
	public function isIncomingServer2serverShareEnabled(): bool {
		if ($this->gsConfig->onlyInternalFederation()) {
			return false;
		}
		return $this->appConfig->getValueBool('files_sharing', 'incoming_server2server_share_enabled', true);
	}
}
