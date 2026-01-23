<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

/**
 * Helper class that covers memory info.
 */
class MemoryInfo {
	public const RECOMMENDED_MEMORY_LIMIT = 512 * 1024 * 1024;

	/**
	 * Tests if the memory limit is compliant with the recommendation value.
	 *
	 * @return bool
	 * @throws \InvalidArgumentException (via $this->getMemoryLimit()) if the memory limit is misconfigured.
	 */
	public function isMemoryLimitSufficient(): bool {
		$memoryLimit = $this->getMemoryLimit();
		return $memoryLimit === -1 || $memoryLimit >= self::RECOMMENDED_MEMORY_LIMIT;
	}

	/**
	 * Returns the interpreted (by PHP) memory limit in bytes.
	 *
	 * @return int The memory limit in bytes, or -1 if unlimited.
	 * @throws \InvalidArgumentException if the memory_limit value cannot be parsed.
	 */
	public function getMemoryLimit(): int {
		$iniValue = ini_get('memory_limit');

		set_error_handler(function($errno, $errstr) {
        	throw new \ErrorException($errstr, 0, $errno);
		});

		try {
			$bytes = ini_parse_quantity($iniValue); // can emit E_WARNING
			return $bytes;
		} catch (\ErrorException $e) {
			throw new \InvalidArgumentException('Error parsing PHP memory_limit ini directive: ' . $e->getMessage());
		} finally {
			restore_error_handler();
		}
	}
}
