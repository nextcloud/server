<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Lib;

/**
 * Helper to turn a configured port into a usable TCP port number.
 *
 * The external storage settings store whatever the admin typed into the port
 * field, so the value can be missing, an empty string, a non-numeric string or
 * a number outside of the valid TCP port range.
 */
final class PortHelper {
	/** Lowest valid TCP port */
	public const MIN_PORT = 1;

	/** Highest valid TCP port */
	public const MAX_PORT = 65535;

	/**
	 * Parse a configured port value
	 *
	 * @param mixed $port the configured value, may be of any type
	 * @param int $fallback port to use when the configured value is not a valid TCP port
	 * @return int the configured port, or $fallback if it is not an integer within the valid TCP port range
	 */
	public static function parsePort(mixed $port, int $fallback): int {
		if (is_int($port)) {
			$parsedPort = $port;
		} elseif (is_string($port) && preg_match('/^\d+$/', $port) === 1) {
			$parsedPort = (int)$port;
		} else {
			return $fallback;
		}

		if ($parsedPort < self::MIN_PORT || $parsedPort > self::MAX_PORT) {
			return $fallback;
		}

		return $parsedPort;
	}
}
