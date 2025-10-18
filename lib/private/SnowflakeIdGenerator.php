<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

/**
 * Nextcloud Snowflake ID generator
 *
 * Generates unique ID for database
 *
 * @since 33.0.0
 */
final class SnowflakeIdGenerator {
	public function __invoke(): int|float {
		// Time related
		$microtime = microtime(true);
		$seconds = ((int)$microtime) - SnowflakeId::TS_OFFSET;
		$milliseconds = ((int)($microtime * 1000)) % 1000;

		$serverId = $this->getServerId() & 0x1FF; // Keep 9 bits
		$isCli = (int)$this->isCli(); // 1 bit
		$sequenceId = $this->getSequenceId($seconds, $milliseconds); //  12 bits
		if ($sequenceId > 0xFFF) {
			// Throttle a bit, wait for next millisecond
			usleep(1000);
			return $this();
		}

		$firstHalf = $seconds & 0x7FFFFFFF;
		$secondHalf = (($milliseconds & 0x3FF) << 22) | ($serverId << 13) | ($isCli << 12) | $sequenceId;
		if (PHP_INT_SIZE === 8) {
			return $firstHalf << 32 | $secondHalf;
		}

		// Fallback for 32 bits systems
		return hexdec(bin2hex(pack('LL', $firstHalf, $secondHalf)));
	}

	private function getServerId(): int {
		return crc32(gethostname() ?: random_bytes(8));
	}

	private function isCli() {
		return PHP_SAPI === 'cli';
	}

	private function getSequenceId(int $seconds, int $milliseconds): int {
		if ($this->isCli()) {
			// APCu cache isn’t shared between CLI processes
			return random_int(0, 0xFFF - 1);
		}

		if (function_exists('apcu_inc')) {
			$key = 'sequence:' . $seconds . ':' . $milliseconds;
			$sequenceId = apcu_inc($key, ttl: 1);
			if ($sequenceId === false) {
				throw new \Exception('Failed to generate SnowflakeId with APCu');
			}

			return $sequenceId;
		}

		// TODO Implement file fallback?
		throw new \Exception('Failed to get sequence Id');
	}
}
