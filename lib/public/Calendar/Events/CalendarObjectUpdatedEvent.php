<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar\Events;

/**
 * @since 32.0.0
 */
class CalendarObjectUpdatedEvent extends AbstractCalendarObjectEvent {

	/**
	 * @param int $calendarId
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData The object data after the update
	 * @param array $oldObjectData The object data before the update, in the same
	 *                             shape as $objectData (empty when unavailable)
	 * @since 32.0.0
	 */
	public function __construct(
		int $calendarId,
		array $calendarData,
		array $shares,
		array $objectData,
		private array $oldObjectData = [],
	) {
		parent::__construct($calendarId, $calendarData, $shares, $objectData);
	}

	/**
	 * Returns the object data as it was before the update.
	 *
	 * @return array
	 * @since 35.0.0
	 */
	public function getOldObjectData(): array {
		return $this->oldObjectData;
	}
}
