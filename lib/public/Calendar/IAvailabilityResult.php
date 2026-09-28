<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar;

/**
 * DTO for the availability check results.
 * Holds information about whether an attendee is available or not during the request time slot.
 *
 * @since 31.0.0
 */
interface IAvailabilityResult {
	/**
	 * Get the attendee's email address.
	 *
	 * @since 31.0.0
	 */
	public function getAttendeeEmail(): string;

	/**
	 * Whether the attendee is available during the requested time slot.
	 *
	 * @since 31.0.0
	 */
	public function isAvailable(): bool;
}
