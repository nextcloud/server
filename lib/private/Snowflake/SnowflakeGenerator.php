<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IServerInfo;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;

/**
 * Nextcloud Snowflake ID generator
 *
 * Generates unique ID for database
 *
 * @since 33.0.0
 */
final readonly class SnowflakeGenerator implements ISnowflakeGenerator {
	public function __construct(
		private ITimeFactory $timeFactory,
		private ISequence $sequenceGenerator,
		private IServerInfo $serverInfo,
	) {
	}

	#[Override]
	public function nextId(): string {
		// Relative time
		[$seconds, $milliseconds] = $this->getCurrentTime();

		$serverId = $this->serverInfo->getServerId();
		$isCli = (int)$this->isCli(); // 1 bit
		$sequenceId = $this->sequenceGenerator->nextId($seconds, $milliseconds, $serverId); //  12 bits
		if ($sequenceId > 0xFFF || $sequenceId === false) {
			// Throttle a bit, wait for next millisecond
			usleep(1000);
			return $this->nextId();
		}

		return $this->packSnowflakeId($seconds, $milliseconds, $serverId, $isCli, $sequenceId);
	}

	/**
	 * Return minimal snowflake ID for a given timestamp
	 *
	 * Not a real snowflake ID!
	 * Only use it for comparisons. For example get all snowflake IDs generated before $timestamp
	 *
	 * @since 34.0.1
	 */
	#[Override]
	public function minForTimeId(int $timestamp): string {
		return $this->packSnowflakeId($timestamp - self::TS_OFFSET, 0, 0, 0, 0);
	}

	private function packSnowflakeId($seconds, $milliseconds, $serverId, $isCli, $sequenceId): string {
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

	private function isCli(): bool {
		return PHP_SAPI === 'cli';
	}
}
