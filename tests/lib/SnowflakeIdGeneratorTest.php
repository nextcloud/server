<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\SnowflakeIdGenerator;

/**
 * @package Test
 */
class SnowflakeIdGeneratorTest extends TestCase {
	public function testGenerator(): void {
		$generator = new SnowflakeIdGenerator();

		$snowflakeId = $generator();
		$this->assertGreaterThan(0x100000000, $snowflakeId);
		if (PHP_INT_SIZE < 8) {
			$this->assertIsFloat($snowflakeId);
		} else {
			$this->assertIsInt($snowflakeId);
		}
	}
}
