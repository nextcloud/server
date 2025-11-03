<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Snowflake\IGenerator;
use Override;

/**
 * Nextcloud Snowflake ID generator
 *
 * Generates unique ID for database
 *
 * @since 33.0.0
 */
final class Generator implements IGenerator {
	private int $lastSeconds = -1;
	private int $lastMilliseconds = -1;
	private int $sequence;

	public function __construct(
		private readonly ITimeFactory $timeFactory,
	) {
	}

	#[Override]
	public function nextId(): string {
		// Time related
		[$seconds, $milliseconds] = $this->getCurrentTime();

		$serverId = $this->getServerId() & 0x1FF; // Keep 9 bits
		$isCli = (int)$this->isCli(); // 1 bit
		$sequenceId = $this->getSequenceId($seconds, $milliseconds, $serverId); //  12 bits
		if ($sequenceId > 0xFFF || $sequenceId === false) {
			// Throttle a bit, wait for next millisecond
			usleep(1000);
			return $this->nextId();
		}

		if (PHP_INT_SIZE === 8) {
			$firstHalf = $seconds & 0x7FFFFFFF;
			$secondHalf = (($milliseconds & 0x3FF) << 22) | ($serverId << 13) | ($isCli << 12) | $sequenceId;
			return (string)($firstHalf << 32 | $secondHalf);
		}

		// Fallback for 32 bits systems
		$firstQuarter = ($seconds >> 16) & 0x7FFF;
		$secondQuarter = $seconds & 0xFFFF;
		$thirdQuarter = ($milliseconds & 0x3FF) << 6 | ($serverId >> 3) & 0x3F;
		$fourthQuarter = ($serverId & 0x7) << 13 | ($isCli & 0x1) << 12 | $sequenceId & 0xFFF;

		$bin = pack('n*', $firstQuarter, $secondQuarter, $thirdQuarter, $fourthQuarter);

		$bytes = unpack('C*', $bin);
		if ($bytes === false) {
			throw new \Exception('Fail to unpack');
		}

		return $this->convertToDecimal(array_values($bytes));
	}

	/**
	 * Mostly copied from Symfony:
	 * https://github.com/symfony/symfony/blob/v7.3.4/src/Symfony/Component/Uid/BinaryUtil.php#L49
	 */
	private function convertToDecimal(array $bytes): string {
		$base = 10;
		$digits = '';

		while ($count = \count($bytes)) {
			$quotient = [];
			$remainder = 0;

			for ($i = 0; $i !== $count; ++$i) {
				$carry = $bytes[$i] + ($remainder << (PHP_INT_SIZE === 8 ? 16 : 8));
				$digit = intdiv($carry, $base);
				$remainder = $carry % $base;

				if ($digit || $quotient) {
					$quotient[] = $digit;
				}
			}

			$digits = $remainder . $digits;
			$bytes = $quotient;
		}

		return $digits;
	}

	private function getCurrentTime(): array {
		$time = $this->timeFactory->now();
		return [
			$time->getTimestamp() - self::TS_OFFSET,
			(int)$time->format('v'),
		];
	}

	private function getServerId(): int {
		return crc32(gethostname() ?: random_bytes(8));
	}

	private function isCli(): bool {
		return PHP_SAPI === 'cli';
	}

	/**
	 * Generates sequence ID from APCu (general case) or random if APCu disabled or CLI
	 *
	 * @return int|false Sequence ID or false if APCu not ready
	 * @throws \Exception if there is an error with APCu
	 */
	private function getSequenceId(int $seconds, int $milliseconds, int $serverId): int|false {
		$key = 'seq:' . $seconds . ':' . $milliseconds;

		// Use APCu as fastest local cache, but not shared between processes in CLI
		if (!$this->isCli() && function_exists('apcu_enabled') && apcu_enabled()) {
			if ((int)apcu_cache_info(true)['creation_time'] === $seconds) {
				// APCu cache was just started
				// It means a sequence was maybe deleted
				return false;
			}

			$success = false;
			$sequenceId = apcu_inc($key, success: $success, ttl: 1);
			if ($success === true) {
				return $sequenceId;
			}

			throw new \Exception('Failed to generate SnowflakeId with APCu');
		}

		// Otherwise, just return a random number
		if ($this->lastSeconds === $seconds && $this->lastMilliseconds === $milliseconds) {
			$this->sequence++;
			$this->lastSeconds = $seconds;
			$this->lastMilliseconds = $milliseconds;

			return $this->sequence;
		}

		$this->sequence = crc32(uniqid((string)random_int(0, PHP_INT_MAX), true)) % 0xFFF;
		$this->lastSeconds = $seconds;
		$this->lastMilliseconds = $milliseconds;

		return $this->sequence;
	}
}
