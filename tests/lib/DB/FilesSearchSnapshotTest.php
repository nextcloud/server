<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Josh Duffy <joshduff.jd@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use OC\DB\ConnectionFactory;
use OC\DB\FilesSearchSnapshot;
use OC\DB\Middleware\ConnectionActivityNotifier;
use OC\SystemConfig;
use Test\TestCase;

class FilesSearchSnapshotTest extends TestCase {
	public function testAcceptsTheAscendingIndexUsedForReverseRecentScan(): void {
		$snapshot = new FilesSearchSnapshot(
			$this->createMock(SystemConfig::class),
			$this->createMock(ConnectionFactory::class),
		);
		$index = [
			'fs_storage_mtime_fileid_name' => [
				$this->indexColumn(1, 'storage', 'A'),
				$this->indexColumn(2, 'mtime', 'A'),
				$this->indexColumn(3, 'fileid', 'A'),
				$this->indexColumn(4, 'name', 'A'),
			],
		];

		$this->assertTrue(self::invokePrivate(
			$snapshot,
			'hasExactIndex',
			[$index, ['storage', 'mtime', 'fileid', 'name'], true, null, ['A', 'A', 'A', 'A']],
		));
	}

	public function testRejectsMixedDirectionIndexForReverseRecentScan(): void {
		$snapshot = new FilesSearchSnapshot(
			$this->createMock(SystemConfig::class),
			$this->createMock(ConnectionFactory::class),
		);
		$index = [
			'fs_storage_mtime_fileid_name' => [
				$this->indexColumn(1, 'storage', 'A'),
				$this->indexColumn(2, 'mtime', 'D'),
				$this->indexColumn(3, 'fileid', 'A'),
				$this->indexColumn(4, 'name', 'A'),
			],
		];

		$this->assertFalse(self::invokePrivate(
			$snapshot,
			'hasExactIndex',
			[$index, ['storage', 'mtime', 'fileid', 'name'], true, null, ['A', 'A', 'A', 'A']],
		));
	}

	public function testAcceptsOnlyTheFactoryOwnedActivityNotifierDifference(): void {
		$snapshot = $this->newSnapshot();
		$expected = ['host' => 'database', 'dbname' => 'nextcloud'];
		$actual = $expected;
		$actual['activity_notifier'] = new ConnectionActivityNotifier();

		self::assertTrue(self::invokePrivate($snapshot, 'hasFactoryOwnedActivityNotifier', [$actual]));
		self::assertTrue(self::invokePrivate($snapshot, 'connectionParamsMatch', [$actual, $expected]));
	}

	public function testRejectsUnknownConnectionParameterDifference(): void {
		$snapshot = $this->newSnapshot();
		$expected = ['host' => 'database', 'dbname' => 'nextcloud'];
		$actual = $expected;
		$actual['activity_notifier'] = new ConnectionActivityNotifier();
		$actual['unknown_routing_parameter'] = 'different';

		self::assertTrue(self::invokePrivate($snapshot, 'hasFactoryOwnedActivityNotifier', [$actual]));
		self::assertFalse(self::invokePrivate($snapshot, 'connectionParamsMatch', [$actual, $expected]));
	}

	private function newSnapshot(): FilesSearchSnapshot {
		return new FilesSearchSnapshot(
			$this->createMock(SystemConfig::class),
			$this->createMock(ConnectionFactory::class),
		);
	}

	/** @return array<string,int|string|null> */
	private function indexColumn(int $position, string $name, string $direction): array {
		return [
			'NON_UNIQUE' => 1,
			'INDEX_TYPE' => 'BTREE',
			'SEQ_IN_INDEX' => $position,
			'COLUMN_NAME' => $name,
			'SUB_PART' => null,
			'IGNORED' => 'NO',
			'COLLATION' => $direction,
		];
	}
}
