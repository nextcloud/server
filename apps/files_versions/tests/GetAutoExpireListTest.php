<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2025 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Tests;

use OCA\Files_Versions\Storage;
use ReflectionClass;
use ReflectionException;

class GetAutoExpireListTest extends \Test\TestCase {

	/**
	 * @throws ReflectionException
	 * @since 32.0.0
	 */
	protected static function callGetAutoExpireList(int $time, array $versions): array {
		$ref = new ReflectionClass(Storage::class);
		$method = $ref->getMethod('getAutoExpireList');
		$method->setAccessible(true);

		return $method->invokeArgs(null, [$time, $versions]);
	}

	/**
	 * @since 32.0.0
	 * @dataProvider provideBucketKeepsLatest
	 */
	public function testBucketKeepsLatest(int $offset1, int $offset2, int $size1, int $size2) {
		$now = time();

		$first = $now - $offset1;
		$second = $first - $offset2;

		$versions = [
			$first => ['version' => $first,  'size' => $size1, 'path' => 'f'],
			$second => ['version' => $second, 'size' => $size2, 'path' => 'f'],
		];

		[$toDelete, $size] = self::callGetAutoExpireList($now, $versions);

		$deletedKeys = array_map('intval', array_keys($toDelete));

		$this->assertEquals([$second], $deletedKeys, 'Older version was not deleted');
		$this->assertEquals($versions[$second]['size'], $size, 'Deleted size mismatch');
	}

	/**
	 * Provides test cases for different bucket intervals.
	 * Each case is [offset1 (age of first), offset2 (extra gap for second), size1, size2].
	 * @return array<string, array{int,int,int,int}>
	 */
	public static function provideBucketKeepsLatest(): array {
		$DAY = 24 * 60 * 60;
		$WEEK = 7 * $DAY;

		return [
			'minute' => [
				8,    // 8s old
				1,    // 9s old → both in same 2s slot
				5,
				6,
			],
			'hour' => [
				2 * 60,   // 2 minutes old
				30,       // 2m30s old → both in same 1m slot
				10,
				11,
			],
			'day' => [
				5 * 3600,   // 5 hours old
				1800,       // 5.5h old → both in same 1h slot
				20,
				21,
			],
			'week' => [
				2 * $DAY,   // 2 days old
				6 * 3600,   // 2.25 days old → both in same 1d slot
				40,
				41,
			],
			'month' => [
				5 * $DAY,      // 5 days old
				12 * 60 * 60,  // 5.5 days old → both in same 1d slot
				30,
				31,
			],
			'year' => [
				35 * $DAY,   // 35 days old
				2 * $DAY,    // 37 days old → both in same 1w slot
				42,
				43,
			],
			'beyond-year' => [
				400 * $DAY,   // ~13.3 months old
				5 * $DAY,     // 405 days old → same 30d slot
				50,
				51,
			],
		];
	}

	/**
	 * @since 32.0.0
	 */
	public function testFiveDaysOfVersionsEveryTenMinutes() {
		$now = time();
		$versions = [];

		// Create one version every 10 minutes for 5 days
		for ($i = 0; $i < (5 * 24 * 6); $i++) {
			$ts = $now - ($i * 600);
			$versions[$ts] = ['version' => $ts, 'size' => 1, 'path' => 'f'];
		}

		[$toDelete, $size] = self::callGetAutoExpireList($now, $versions);
		$retained = array_diff(array_keys($versions), array_keys($toDelete));

		// Expect ~28-33 retained due to bucket rules
		$this->assertGreaterThanOrEqual(28, count($retained));
		$this->assertLessThanOrEqual(33, count($retained));
	}

	/**
	 * @since 32.0.0
	 */
	public function testThirtyDaysOfVersionsEveryTenMinutes() {
		$now = time();
		$versions = [];

		// Create one version every 10 minutes for 30 days
		for ($i = 0; $i < (30 * 24 * 6); $i++) {
			$ts = $now - ($i * 600);
			$versions[$ts] = ['version' => $ts, 'size' => 1, 'path' => 'f'];
		}

		[$toDelete, $size] = self::callGetAutoExpireList($now, $versions);
		$retained = array_diff(array_keys($versions), array_keys($toDelete));

		// Expect ~54-60 retained (24 hours hourly + 29 daily + bucket overlap)
		$this->assertGreaterThanOrEqual(54, count($retained));
		$this->assertLessThanOrEqual(60, count($retained));
	}

	/**
	 * @since 32.0.0
	 */
	public function testYearOfVersionsEveryTenMinutes() {
		$now = time();
		$versions = [];

		// Create one version every 10 minutes for 365 days
		for ($i = 0; $i < (365 * 24 * 6); $i++) {
			$ts = $now - ($i * 600);
			$versions[$ts] = ['version' => $ts, 'size' => 1, 'path' => 'f'];
		}

		[$toDelete, $size] = self::callGetAutoExpireList($now, $versions);
		$retained = array_diff(array_keys($versions), array_keys($toDelete));

		// Expect ~100-140 retained due to buckets (minute, hour, day, week, month)
		$this->assertGreaterThanOrEqual(100, count($retained));
		$this->assertLessThanOrEqual(140, count($retained));
	}

	/**
	 * @since 32.0.0
	 */
	public function testMoreThanAYearOfVersionsEveryTenMinutesWithDeletion() {
		$now = time();
		$versions = [];

		// Define bucket steps (same as retention logic)
		$buckets = [
			1 => ['intervalEndsAfter' => 10,      'step' => 2],
			2 => ['intervalEndsAfter' => 60,      'step' => 10],
			3 => ['intervalEndsAfter' => 3600,    'step' => 60],
			4 => ['intervalEndsAfter' => 86400,   'step' => 3600],
			5 => ['intervalEndsAfter' => 2592000, 'step' => 86400],
			6 => ['intervalEndsAfter' => -1,      'step' => 604800],
		];

		$lastBoundary = 0;
		foreach ($buckets as $bucket) {
			$intervalEnd = $bucket['intervalEndsAfter'] > 0 ? $bucket['intervalEndsAfter'] : 500 * 86400;
			$step = $bucket['step'];

			for ($age = $lastBoundary; $age <= $intervalEnd; $age += $step) {
				// Add multiple versions per step (3 versions spaced evenly within step)
				for ($i = 0; $i < 3; $i++) {
					$ts = $now - ($age + $i * floor($step / 3));
					$versions[$ts] = ['version' => $ts, 'size' => 1, 'path' => 'f'];
				}
			}

			$lastBoundary = $intervalEnd;
		}

		[$toDelete, $size] = self::callGetAutoExpireList($now, $versions);
		$retained = array_diff(array_keys($versions), array_keys($toDelete));

		$lastBoundary = 0;
		foreach ($buckets as $bucket) {
			$intervalEnd = $bucket['intervalEndsAfter'] > 0 ? $bucket['intervalEndsAfter'] : PHP_INT_MAX;

			$bucketRetained = array_filter($retained, function ($ts) use ($now, $lastBoundary, $intervalEnd) {
				$age = $now - $ts;
				return $age >= $lastBoundary && $age <= $intervalEnd;
			});

			$this->assertGreaterThanOrEqual(
				1,
				count($bucketRetained),
				"Bucket ending at $intervalEnd seconds has " . count($bucketRetained) . ' retained, expected at least 1'
			);

			$lastBoundary = $intervalEnd;
		}

	}

}
