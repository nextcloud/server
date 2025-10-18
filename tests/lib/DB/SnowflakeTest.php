<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use OC\DB\Snowflake\NextcloudSequenceResolver;
use OC\DB\Snowflake\SnowflakeGenerator;
use OCP\Server;
use PHPUnit\Framework\Attributes\TestWith;
use Test\TestCase;

class SnowflakeTest extends TestCase {
	#[TestWith(data: [true, true, 42])] // 42ms
	#[TestWith(data: [true, false, 42])]
	#[TestWith(data: [false, true, 42])]
	#[TestWith(data: [true, false, 42])]
	#[TestWith(data: [false, true, 60 * 60 * 24 * 365 * 10])] // ~10 years
	#[TestWith(data: [false, false, 60 * 60 * 24 * 365 * 10])]
	public function testLayout(bool $isCLIExpected, bool $is32BitsSystem, int $timeDiff): void {
		if (!$is32BitsSystem && PHP_INT_SIZE < 8) {
			$this->markTestSkipped('Unable to run 64 bits code on 32 bits system.');
		}

		$baseTimestamp = strtotime('2025-01-01');
		$resolver = $this->createMock(NextcloudSequenceResolver::class);
		$resolver->method('isAvailable')->willReturn(true);
		$resolver->method('sequence')->willReturn(42);

		$snowFlake = $this->getMockBuilder(SnowflakeGenerator::class)
			->setConstructorArgs([21, 22, $resolver, $isCLIExpected])
			->onlyMethods(['getCurrentMillisecond', 'is32BitsSystem'])
			->getMock();

		if (PHP_INT_SIZE < 8) {
			$timeDiffString = gmp_strval(gmp_mul($timeDiff, 1000));
			$snowFlake->method('getCurrentMillisecond')
				->willReturn(gmp_strval(gmp_mul(gmp_add($baseTimestamp, $timeDiff), 1000)));
		} else {
			$timeDiffString = (string)($timeDiff * 1000);
			$snowFlake->method('getCurrentMillisecond')
				->willReturn((string)(($baseTimestamp + $timeDiff) * 1000));
		}

		$snowFlake->method('is32BitsSystem')
			->willReturn($is32BitsSystem);

		$snowFlake->setStartTimeStamp($baseTimestamp);

		$id = $snowFlake->nextId();

		[
			'sequence' => $sequence,
			'timestamp' => $timestamp,
			'workerid' => $workerId,
			'datacenter' => $datacenter,
			'iscli' => $isCLI,
		] = $snowFlake->parseId($id);

		$this->assertEquals(22, $workerId);
		$this->assertEquals(21, $datacenter);
		$this->assertEquals($isCLIExpected, $isCLI);
		$this->assertEquals($timeDiffString, $timestamp);
		$this->assertEquals(42, $sequence);
	}

	#[TestWith(data: [true, true])]
	#[TestWith(data: [true, false])]
	#[TestWith(data: [false, true])]
	#[TestWith(data: [false, false])]
	public function testRandomSequence(bool $random, bool $is32BitsSystem): void {
		if (!$is32BitsSystem && PHP_INT_SIZE < 8) {
			$this->markTestSkipped('Unable to run 64 bits code on 32 bits system.');
		}

		$baseTimestamp = strtotime('2025-01-01');
		if ($random) {
			$resolver = $this->createMock(NextcloudSequenceResolver::class);
			$resolver->method('isAvailable')->willReturn(false);
		} else {
			$resolver = Server::get(NextcloudSequenceResolver::class);
			$this->assertTrue($resolver->isAvailable());
		}

		$snowFlake = $this->getMockBuilder(SnowflakeGenerator::class)
			->setConstructorArgs([21, 22, $resolver, true])
			->onlyMethods(['getCurrentMillisecond', 'is32BitsSystem'])
			->getMock();

		$timeDiff = 42;
		if (PHP_INT_SIZE < 8) {
			$snowFlake->method('getCurrentMillisecond')
				->willReturn(gmp_strval(gmp_mul(gmp_add($baseTimestamp, $timeDiff), 1000)));
		} else {
			$snowFlake->method('getCurrentMillisecond')
				->willReturn((string)(($baseTimestamp + $timeDiff) * 1000));
		}

		$snowFlake->method('is32BitsSystem')
			->willReturn($is32BitsSystem);

		$snowFlake->setStartTimeStamp($baseTimestamp);

		$ids = [];
		for ($i = 0; $i < 20; $i++) {
			$ids[] = $snowFlake->nextId();
		}

		$this->assertEquals($ids, array_unique($ids));
	}

	#[TestWith(data: [true])]
	#[TestWith(data: [false])]
	public function testSetStartTimeStamp(bool $is32BitsSystem): void {
		if (!$is32BitsSystem && PHP_INT_SIZE < 8) {
			$this->markTestSkipped('Unable to run 64 bits code on 32 bits system.');
		}

		$generator = $this->getMockBuilder(SnowflakeGenerator::class)
			->setConstructorArgs([21, 22, $this->createMock(NextcloudSequenceResolver::class), true])
			->onlyMethods(['is32BitsSystem'])
			->getMock();

		$generator->method('is32BitsSystem')
			->willReturn($is32BitsSystem);
		$generator->setStartTimeStamp(strtotime('2025-01-01'));
		$this->assertEquals('1735689600000', $generator->getStartTimeStamp());
	}
}
