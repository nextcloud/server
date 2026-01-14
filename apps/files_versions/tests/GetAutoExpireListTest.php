<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Tests;

use OCA\Files_Versions\Storage;
use Test\TestCase;

class GetAutoExpireListTest extends TestCase {

	/**
	 * Frozen reference time for all tests
	 */
	private const NOW = 1600000000;

	/**
	 * Helper to call the private retention logic
	 *
	 * @param int $now
	 * @param array $versions
	 * @return array{array<int,array>, int}
	 */
	private static function callGetAutoExpireList(int $now, array $versions): array {
		$ref = new \ReflectionClass(Storage::class);
		$method = $ref->getMethod('getAutoExpireList');

		/** @var array{array<int,array>, int} */
		return $method->invoke(null, $now, $versions);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('provideBucketKeepsLatest')]
	public function testBucketKeepsLatest(
		int $newerAge,
		int $olderAge,
		int $newerSize,
		int $olderSize
	): void {
		// Assert provider contract: newer must be younger than older
		$this->assertLessThan(
			$olderAge,
			$newerAge,
			'Invalid test data: newerAge must be smaller than olderAge'
		);

		$now = time();

		$newer = $now - $newerAge;
		$older = $now - $olderAge;

		$versions = [
			$newer => [
				'version' => $newer,
				'size'    => $newerSize,
				'path'    => 'f',
			],
			$older => [
				'version' => $older,
				'size'    => $olderSize,
				'path'    => 'f',
			],
		];

		[$toDelete, $deletedSize] = self::callGetAutoExpireList($now, $versions);

		$deletedKeys = array_map('intval', array_keys($toDelete));

		$this->assertSame(
			[$older],
			$deletedKeys
		);

		$this->assertSame(
			$olderSize,
			$deletedSize
		);
	}

	public static function provideBucketKeepsLatest(): array {
		$DAY = 24 * 60 * 60;

		return [
			'seconds-range' => [
				8,    // newer (8s old)
				9,    // older (9s old)
				5,
				6,
			],
			'minutes-range' => [
				120,  // 2 minutes old
				150,  // 2.5 minutes old
				10,
				11,
			],
			'hours-range' => [
				5 * 3600,          // 5 hours old
				5 * 3600 + 1800,   // 5.5 hours old
				20,
				21,
			],
			'days-range' => [
				2 * $DAY,               // 2 days old
				2 * $DAY + 6 * 3600,    // 2.25 days old
				40,
				41,
			],
			'weeks-range' => [
				5 * $DAY,               // 5 days old
				5 * $DAY + 12 * 3600,   // 5.5 days old
				30,
				31,
			],
			'months-range' => [
				35 * $DAY,   // 35 days old
				37 * $DAY,   // 37 days old
				42,
				43,
			],
			'beyond-year-range' => [
				400 * $DAY,  // ~13.3 months old
				405 * $DAY,  // ~13.5 months old
				50,
				51,
			],
		];
	}


	#[\PHPUnit\Framework\Attributes\DataProvider('provideVersionRetentionRanges')]
	public function testRetentionOverTimeEveryTenMinutes(
		int $days,
		int $expectedMin,
		int $expectedMax,
	): void {
		$now = time();
		$versions = [];

		// One version every 10 minutes
		$interval = 600; // 10 minutes
		$total = $days * 24 * 6;

		for ($i = 0; $i < $total; $i++) {
			$ts = $now - ($i * $interval);
			$versions[$ts] = [
				'version' => $ts,
				'size' => 1,
				'path' => 'f',
			];
		}

		[$toDelete, $size] = self::callGetAutoExpireList($now, $versions);

		$retained = array_diff(array_keys($versions), array_keys($toDelete));
		$retainedCount = count($retained);

		$this->assertGreaterThanOrEqual(
			$expectedMin,
			$retainedCount,
			"Too few versions retained for {$days} days"
		);

		$this->assertLessThanOrEqual(
			$expectedMax,
			$retainedCount,
			"Too many versions retained for {$days} days"
		);
	}

	public static function provideVersionRetentionRanges(): array {
		return [
			'5 days' => [
				5,
				28,
				33,
			],
			'30 days' => [
				30,
				54,
				60,
			],
			'1 year' => [
				365,
				100,
				140,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('provideExactRetentionCounts')]
	public function testExactRetentionCounts(
		int $days,
		int $expectedRetained,
	): void {
		$now = self::NOW;
		$versions = [];

		// One version per hour, safely inside bucket slots
		for ($i = 0; $i < $days * 24; $i++) {
			$ts = $now - ($i * 3600) - 1;
			$versions[$ts] = ['version' => $ts, 'size' => 1, 'path' => 'f'];
		}

		[$toDelete] = self::callGetAutoExpireList($now, $versions);
		$retained = array_diff_key($versions, $toDelete);

		$this->assertSame(
			$expectedRetained,
			count($retained),
			"Exact retention count mismatch for {$days} days"
		);
	}

	/**
	 * @return array<string, array{int,int}>
	 */
	public static function provideExactRetentionCounts(): array {
		return [
			'five-days' => [
				5,
				self::expectedHourlyRetention(5),
			],
			'thirty-days' => [
				30,
				self::expectedHourlyRetention(30),
			],
			'one-year' => [
				365,
				self::expectedHourlyRetention(365),
			],
			'one-year-plus' => [
				500,
				self::expectedHourlyRetention(500),
			],
		];
	}

	private static function expectedHourlyRetention(int $days): int {
		// Hourly for first day
		$hourly = min(24, $days * 24);

		// Daily from day 1 to day 30
		$dailyDays = max(0, min($days, 30) - 1);
		$daily = $dailyDays;

		// Weekly beyond 30 days
		$weeklyDays = max(0, $days - 30);
		$weekly = intdiv($weeklyDays, 7) + ($weeklyDays > 0 ? 1 : 0);

		return $hourly + $daily + $weekly;
	}
}
