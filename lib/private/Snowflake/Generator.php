<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
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
	public function __construct(
		private readonly ITimeFactory $timeFactory,
		private readonly IConfig $config,
		private readonly ISequence $sequenceGenerator,
	) {
	}

	#[Override]
	public function nextId(): string {
		// Relative time
		[$seconds, $milliseconds] = $this->getCurrentTime();

		$serverId = $this->getServerId() & 0x1FF; // Keep 9 bits
		$isCli = (int)$this->isCli(); // 1 bit
		$sequenceId = $this->sequenceGenerator->nextId($seconds, $milliseconds, $serverId); //  12 bits
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

	/**
	 * Return configured serverid or generate one if not set
	 */
	private function getServerId(): int {
		$serverid = $this->config->getSystemValueInt('serverid', -1);
		return $serverid > 0
			? $serverid
			: crc32(gethostname() ?: random_bytes(8));
	}

	private function isCli(): bool {
		return PHP_SAPI === 'cli';
	}
}
