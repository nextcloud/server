<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Snowflake;

use OCP\Snowflake\IDecoder;
use OCP\Snowflake\IGenerator;
use Override;

/**
 * Nextcloud Snowflake ID
 *
 * Get information about Snowflake Id
 *
 * @since 33.0.0
 */
final class Decoder implements IDecoder {
	#[Override]
	public function decode(string $snowflakeId): array {
		if (!ctype_digit($snowflakeId)) {
			throw new \Exception('Invalid Snowflake ID: ' . $snowflakeId);
		}

		/** @var array{seconds: positive-int, milliseconds: int<0,999>, serverId: int<0, 1023>, sequenceId: int<0,4095>, isCli: bool} $data */
		$data = PHP_INT_SIZE === 8
			? $this->decode64bits((int)$snowflakeId)
			: $this->decode32bits($snowflakeId);

		$data['createdAt'] = new \DateTimeImmutable(
			sprintf(
				'@%d.%03d',
				$data['seconds'] + IGenerator::TS_OFFSET + intdiv($data['milliseconds'], 1000),
				$data['milliseconds'] % 1000,
			)
		);

		return $data;
	}

	private function decode64bits(int $snowflakeId): array {
		$firstHalf = $snowflakeId >> 32;
		$secondHalf = $snowflakeId & 0xFFFFFFFF;

		$seconds = $firstHalf & 0x7FFFFFFF;
		$milliseconds = $secondHalf >> 22;

		return [
			'seconds' => $seconds,
			'milliseconds' => $milliseconds,
			'serverId' => ($secondHalf >> 13) & 0x1FF,
			'sequenceId' => $secondHalf & 0xFFF,
			'isCli' => (bool)(($secondHalf >> 12) & 0x1),
		];
	}

	private function decode32bits(string $snowflakeId): array {
		$id = $this->convertBase16($snowflakeId);

		$firstQuarter = (int)hexdec(substr($id, 0, 4));
		$secondQuarter = (int)hexdec(substr($id, 4, 4));
		$thirdQuarter = (int)hexdec(substr($id, 8, 4));
		$fourthQuarter = (int)hexdec(substr($id, 12, 4));

		$seconds = (($firstQuarter & 0x7FFF) << 16) | ($secondQuarter & 0xFFFF);
		$milliseconds = ($thirdQuarter >> 6) & 0x3FF;

		return [
			'seconds' => $seconds,
			'milliseconds' => $milliseconds,
			'serverId' => (($thirdQuarter & 0x3F) << 3) | (($fourthQuarter >> 13) & 0x7),
			'sequenceId' => $fourthQuarter & 0xFFF,
			'isCli' => (bool)(($fourthQuarter >> 12) & 0x1),
		];
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
}
