<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Nextcloud ID generator
 *
 * Generates unique ID
 * @since 33.0.0
 */
interface ISnowflakeId {
	public const TS_OFFSET = 1759276800; // 2025-10-01 00:00:00

	/**
	 * Returns sequence ID as int (64 bits servers) or string (32 bits servers)
	 *
	 * This method is suitable to store Sequence Id in database.
	 * Use BIGINT (8 bytes)
	 *
	 * @since 33.0
	 */
	public function numeric(): int|string;

	/**
	 * Returns whether the SequenceId was created in CLI or not (eg. FPM, Apache)
	 *
	 * @since 33.0
	 */
	public function isCli(): bool;

	/**
	 * Returns the number of seconds after self::TS_OFFSET
	 *
	 * Creation time of the sequence ID since self::TS_OFFSET
	 *
	 * @since 33.0
	 */
	public function seconds(): int;

	/**
	 * Returns the number of milliseconds of creation
	 *
	 * @since 33.0
	 */
	public function milliseconds(): int;

	/**
	 * Returns full millisecond creation timestamp
	 *
	 * @since 33.0
	 */
	public function createdAt(): float;

	/**
	 * Returns server ID (encoded on 9 bits)
	 *
	 * @return int<0, 511>
	 * @since 33.0
	 */
	public function serverId(): int;

	/**
	 * Returns sequence ID (encoded on 12 bits)
	 *
	 * @return int<0, 4095>
	 * @since 33.0
	 */
	public function sequenceId(): int;
}
