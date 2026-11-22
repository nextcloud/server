<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Model;

use Sabre\VObject\Component\VCalendar;

/**
 * Simple DTO to store a parsed example event and its UID.
 */
final class ExampleEvent {
	public function __construct(
		private readonly VCalendar $vCalendar,
		private readonly string $uid,
	) {
	}

	public function getUid(): string {
		return $this->uid;
	}

	public function getIcs(): string {
		return $this->vCalendar->serialize();
	}
}
