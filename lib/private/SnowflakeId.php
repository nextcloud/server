<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

use OCP\ISnowflakeId;
use Override;

/**
 * Nextcloud Snowflake ID
 *
 * Get information about Snowflake Id
 *
 * @since 33.0.0
 */
final class SnowflakeId implements ISnowflakeId {
	private int $seconds = 0;
	private int $milliseconds = 0;
	private bool $isCli = false;
	/** @var int<0, 511> */
	private int $serverId = 0;
	/** @var int<0, 4095> */
	private int $sequenceId = 0;

	public function __construct(
		private readonly int|string $id,
	) {
	}

	private function decode(): void {
		if ($this->seconds !== 0) {
			return;
		}

		PHP_INT_SIZE === 8
			? $this->decode64bits()
			: $this->decode32bits();
	}

	private function decode64bits(): void {
		$id = (int)$this->id;
		$firstHalf = $id >> 32;
		$secondHalf = $id & 0xFFFFFFFF;

		// First half without first bit is seconds
		$this->seconds = $firstHalf & 0x7FFFFFFF;

		// Decode second half
		$this->milliseconds = $secondHalf >> 22;
		$this->serverId = ($secondHalf >> 13) & 0x1FF;
		$this->isCli = (bool)(($secondHalf >> 12) & 0x1);
		$this->sequenceId = $secondHalf & 0xFFF;
	}

	private function decode32bits(): void {
		$id = is_int($this->id) ? number_format($this->id, 0, '', '') : $this->id;
		$id = $this->convertBase16($id);

		$firstQuarter = (int)hexdec(substr($id, 0, 4));
		$secondQuarter = (int)hexdec(substr($id, 4, 4));
		$thirdQuarter = (int)hexdec(substr($id, 8, 4));
		$fourthQuarter = (int)hexdec(substr($id, 12, 4));

		$this->seconds = (($firstQuarter & 0x7FFF) << 16) | ($secondQuarter & 0xFFFF);

		$this->milliseconds = ($thirdQuarter >> 6) & 0x3FF;

		$this->serverId = (($thirdQuarter & 0x3F) << 3) | (($fourthQuarter >> 13) & 0x7);
		$this->isCli = (bool)(($fourthQuarter >> 12) & 0x1);
		$this->sequenceId = $fourthQuarter & 0xFFF;
	}

	/**
	 * Convert base 10 number to base 16, padded to 16 characters
	 *
	 * Required on 32 bits systems as base_convert will lose precision with large numbers
	 */
	private function convertBase16(string $decimal): string {
		$hex = '';
		$digits = '0123456789ABCDEF';

		while (strlen($decimal) > 0 && $decimal !== '0') {
			$remainder = 0;
			$newDecimal = '';

			// Perform division by 16 manually for arbitrary precision
			for ($i = 0; $i < strlen($decimal); $i++) {
				$digit = (int)$decimal[$i];
				$current = $remainder * 10 + $digit;

				if ($current >= 16) {
					$quotient = (int)($current / 16);
					$remainder = $current % 16;
					$newDecimal .= chr(ord('0') + $quotient);
				} else {
					$remainder = $current;
					// Only add quotient digit if we already have some digits in result
					if (strlen($newDecimal) > 0) {
						$newDecimal .= '0';
					}
				}
			}

			// Add the remainder (0-15) as hex digit
			$hex = $digits[$remainder] . $hex;

			// Update decimal for next iteration
			$decimal = ltrim($newDecimal, '0');
		}

		return str_pad($hex, 16, '0', STR_PAD_LEFT);
	}

	#[Override]
	public function isCli(): bool {
		return $this->isCli;
	}

	#[Override]
	public function numeric(): int|string {
		return $this->id;
	}

	#[Override]
	public function seconds(): int {
		$this->decode();
		return $this->seconds;
	}

	#[Override]
	public function milliseconds(): int {
		$this->decode();
		return $this->milliseconds;
	}

	#[Override]
	public function createdAt(): float {
		$this->decode();
		return $this->seconds + self::TS_OFFSET + ($this->milliseconds / 1000);
	}

	#[Override]
	public function serverId(): int {
		$this->decode();
		return	$this->serverId;
	}

	#[Override]
	public function sequenceId(): int {
		$this->decode();
		return $this->sequenceId;
	}
}
