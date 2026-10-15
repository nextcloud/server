<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

final class SyncServiceResult {
	public function __construct(
		private readonly string $syncToken,
		private readonly int $downloadedEvents,
	) {
	}

	public function getSyncToken(): string {
		return $this->syncToken;
	}

	public function getDownloadedEvents(): int {
		return $this->downloadedEvents;
	}
}
