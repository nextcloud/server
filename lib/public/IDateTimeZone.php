<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Interface IDateTimeZone
 *
 * @since 8.0.0
 */
interface IDateTimeZone {

	/**
	 * Get the timezone for a given user.
	 * If a timestamp is passed the timezone for that given timestamp is retrieved (might differ due to DST).
	 * If no userId is passed the current user is used.
	 *
	 * @param bool|int $timestamp
	 * @param ?string $userId - The user to fetch the timezone for (defaults to current user)
	 * @return \DateTimeZone
	 * @since 8.0.0
	 * @since 8.1.0 - parameter $timestamp was added
	 * @since 32.0.0 - parameter $userId was added
	 */
	public function getTimeZone($timestamp = false, ?string $userId = null);

	/**
	 * Get the timezone configured as the default for this Nextcloud server.
	 * While the PHP timezone is always set to UTC in Nextcloud this is the timezone
	 * to use for all time offset calculations if no user value is specified.
	 *
	 * @since 32.0.0
	 */
	public function getDefaultTimeZone(): \DateTimeZone;
}
