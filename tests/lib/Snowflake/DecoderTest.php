<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Snowflake;

use OC\Snowflake\Decoder;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

/**
 * @package Test
 */
class DecoderTest extends TestCase {
	private Decoder $decoder;

	public function setUp():void {
		$this->decoder = new Decoder();
	}

	#[DataProvider('provideSnowflakeIds')]
	public function testDecode(
		string $snowflakeId,
		float $timestamp,
		int $serverId,
		int $sequenceId,
		bool $isCli,
	): void {
		$data = $this->decoder->decode($snowflakeId);

		$this->assertEquals($timestamp, (float)$data['createdAt']->format('U.v'));
		$this->assertEquals($serverId, $data['serverId']);
		$this->assertEquals($sequenceId, $data['sequenceId']);
		$this->assertEquals($isCli, $data['isCli']);
	}

	public static function provideSnowflakeIds(): array {
		$data = [
			['4688076898113587', 1760368327.984, 392, 2099, true],
			// Max milliseconds
			['4190109696', 1759276800.999, 0, 0, false],
			// Max serverId
			['4186112', 1759276800.0, 511, 0, false],
			// Max sequenceId
			['4095', 1759276800.0, 0, 4095, false],
			// Max isCli
			['4096', 1759276800.0, 0, 0, true],
			// Min
			['0', 1759276800, 0, 0, false],
			// Other
			['250159983611680096', 1817521710, 392, 1376, true],
		];

		// 32 bits can't handle large timestamps correctly
		if (PHP_INT_SIZE === 8) {
			// Max all (can't happen because ms are up to 999)
			$data[] = ['9223372036854775807', 3906760448.023, 511, 4095, true];
			// Max all (real)
			$data[] = ['9223372036754112511', 3906760447.999, 511, 4095, true];
			// Max seconds
			$data[] = ['9223372032559808512', 3906760447, 0, 0, false];
		}

		return $data;
	}
}
