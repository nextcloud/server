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
	 * @param bool|int $timestamp
	 * @return \DateTimeZone
	 * @since 8.0.0 - parameter $timestamp was added in 8.1.0
	 */
	public function getTimeZone($timestamp = false);
}
