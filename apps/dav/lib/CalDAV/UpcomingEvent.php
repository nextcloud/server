<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use JsonSerializable;
use OCA\DAV\ResponseDefinitions;

class UpcomingEvent implements JsonSerializable {
	public function __construct(
		private string $uri,
		private ?int $recurrenceId,
		private string $calendarUri,
		private ?int $start,
		private ?string $summary,
		private ?string $location,
		private ?string $calendarAppUrl,
	) {
	}

	public function getUri(): string {
		return $this->uri;
	}

	public function getRecurrenceId(): ?int {
		return $this->recurrenceId;
	}

	public function getCalendarUri(): string {
		return $this->calendarUri;
	}

	public function getStart(): ?int {
		return $this->start;
	}

	public function getSummary(): ?string {
		return $this->summary;
	}

	public function getLocation(): ?string {
		return $this->location;
	}

	public function getCalendarAppUrl(): ?string {
		return $this->calendarAppUrl;
	}

	/**
	 * @see ResponseDefinitions
	 */
	public function jsonSerialize(): array {
		return [
			'uri' => $this->uri,
			'recurrenceId' => $this->recurrenceId,
			'calendarUri' => $this->calendarUri,
			'start' => $this->start,
			'summary' => $this->summary,
			'location' => $this->location,
			'calendarAppUrl' => $this->calendarAppUrl,
		];
	}
}
