<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\SnowflakeId;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @package Test
 */
class SnowflakeIdTest extends TestCase {
	#[DataProvider('provideSnowflakeIds')]
	public function testDecode(
		int|string $snowflakeId,
		float $timestamp,
		int $serverId,
		int $sequenceId,
		bool $isCli,
	): void {
		$snowflake = new SnowflakeId($snowflakeId);

		$this->assertEquals($snowflakeId, $snowflake->numeric());
		$this->assertEquals($timestamp, $snowflake->createdAt());
		$this->assertEquals($serverId, $snowflake->serverId());
		$this->assertEquals($sequenceId, $snowflake->sequenceId());
		$this->assertEquals($isCli, $snowflake->isCli());
	}

	public static function provideSnowflakeIds(): array {
		return PHP_INT_SIZE === 8
			?  [
				[4688076898113587, 1760368327.984, 392, 2099, true],
				// Max all (can't happen ms are up to 999)
				[0x7fffffffffffffff, 3906760448.023, 511, 4095, true],
				// Max all (real)
				[0x7ffffffff9ffffff, 3906760447.999, 511, 4095, true],
				// Max seconds
				[0x7fffffff00000000, 3906760447, 0, 0, false],
				// Max milliseconds
				[4190109696, 1759276800.999, 0, 0, false],
				// Max serverId
				[4186112, 1759276800.0, 511, 0, false],
				// Max sequenceId
				[4095, 1759276800.0, 0, 4095, false],
				// Max isCli
				[4096, 1759276800.0, 0, 0, true],
				// Min
				[0, 1759276800, 0, 0, false],
			]
			:  [
				['4688076898113587', 1760368327.984, 392, 2099, true],
				// Max all (can't this-appen ms are up to 999)
				['9223372036854775807', 3906760448.023, 511, 4095, true],
				// Max all (real)
				['9223372036754112511', 3906760447.999, 511, 4095, true],
				// Max seconds
				['9223372032559808512', 3906760447, 0, 0, false],
				// Max milliseconds
				['4190109696', 1759276800.999, 0, 0, false],
				// Max serverId
				['4186112', 1759276800.0, 511, 0, false],
				[4186112, 1759276800.0, 511, 0, false],
				// Max secondsuenceId
				['4095', 1759276800.0, 0, 4095, false],
				[4095, 1759276800.0, 0, 4095, false],
				// Max isCli
				['4096', 1759276800.0, 0, 0, true],
				[4096, 1759276800.0, 0, 0, true],
				// Min
				['0', 1759276800, 0, 0, false],
				[0, 1759276800, 0, 0, false],
			];
	}
}
