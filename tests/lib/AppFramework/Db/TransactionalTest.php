<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Db;

use OCP\AppFramework\Db\TTransactional;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Test\TestCase;

class TransactionalTest extends TestCase {
	/** @var IDBConnection|MockObject */
	private IDBConnection $db;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(IDBConnection::class);
	}

	public function testAtomicRollback(): void {
		$test = new class($this->db) {
			use TTransactional;

			private IDBConnection $db;

			public function __construct(IDBConnection $db) {
				$this->db = $db;
			}

			public function fail(): void {
				$this->atomic(function (): void {
					throw new RuntimeException('nope');
				}, $this->db);
			}
		};
		$this->db->expects(self::once())
			->method('beginTransaction');
		$this->db->expects(self::once())
			->method('rollback');
		$this->db->expects(self::never())
			->method('commit');
		$this->expectException(RuntimeException::class);

		$test->fail();
	}

	public function testAtomicCommit(): void {
		$test = new class($this->db) {
			use TTransactional;

			private IDBConnection $db;

			public function __construct(IDBConnection $db) {
				$this->db = $db;
			}

			public function succeed(): int {
				return $this->atomic(function () {
					return 3;
				}, $this->db);
			}
		};
		$this->db->expects(self::once())
			->method('beginTransaction');
		$this->db->expects(self::never())
			->method('rollback');
		$this->db->expects(self::once())
			->method('commit');

		$result = $test->succeed();

		self::assertEquals(3, $result);
	}
}
