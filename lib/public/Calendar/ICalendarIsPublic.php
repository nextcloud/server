<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar;

/**
 * @since 33.0.1, 32.0.7, 31.0.14.1, 30.0.17.8
 */
interface ICalendarIsPublic {
	/**
	 * Gets the token of a publicly shared calendar.
	 *
	 * @since 33.0.1, 32.0.7, 31.0.14.1, 30.0.17.8
	 */
	public function getPublicToken(): ?string;
}
