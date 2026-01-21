<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

use OCP\Util;

/**
 * Helper class that covers memory info.
 */
class MemoryInfo {
	public const RECOMMENDED_MEMORY_LIMIT = 512 * 1024 * 1024;

	/**
	 * Tests if the memory limit is greater or equal the recommended value.
	 *
	 * @return bool
	 */
	public function isMemoryLimitSufficient(): bool {
		$memoryLimit = $this->getMemoryLimit();
		return $memoryLimit === -1 || $memoryLimit >= self::RECOMMENDED_MEMORY_LIMIT;
	}

	/**
	 * Returns the interpreted (by PHP) memory limit in bytes.
	 *
	 * @return int The memory limit in bytes, or -1 if unlimited.
	 * @throws \InvalidArgumentException If the memory_limit value cannot be parsed.
	 */
	public function getMemoryLimit(): int {
		$iniValue = ini_get('memory_limit');
		$bytes = ini_parse_quantity($iniValue); // can emit E_WARNING
		if ($bytes === false) {
			throw new \InvalidArgumentException($iniValue . ' is not a valid memory limit value (in memory_limit ini directive)');
		}
		return $bytes;
	}
}
