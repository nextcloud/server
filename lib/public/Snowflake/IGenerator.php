<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Snowflake;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Nextcloud Snowflake ID generator
 *
 * Customized version of Snowflake IDs for Nextcloud:
 *   1 bit : Unused, always 0, avoid issue with PHP signed integers.
 *	31 bits: Timestamp from 2025-10-01. Allows to store a bit more than 68 years. Allows to find creation time.
 *	10 bits: Milliseconds (between 0 and 999)
 *	 9 bits: Server ID, identify server which generated the ID (between 0 and 1023)
 *	 1 bit : CLI or Web (0 or 1)
 *	12 bits: Sequence ID, usually a serial number of objects created in the same number on same server (between 0 and 4095)
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
interface IGenerator {

	/**
	 * Offset applied on timestamps to keep it short
	 * Start from 2025-10-01 at 00:00:00
	 *
	 * @since 33.0
	 */
	public const TS_OFFSET = 1759276800;

	/**
	 * Get a new Snowflake ID.
	 *
	 * Each call to this method is guaranteed to return a different ID.
	 *
	 * @since 33.0
	 */
	public function nextId(): string;
}
