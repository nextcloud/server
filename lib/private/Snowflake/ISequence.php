<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

/**
 * Generates sequence IDs
 */
interface ISequence {
	/**
	 * Check if generator is available
	 */
	public function isAvailable(): bool;

	/**
	 * Returns next sequence ID for current time and server
	 */
	public function nextId(int $serverId, int $seconds, int $milliseconds): int|false;
}
