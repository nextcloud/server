<?php

declare(strict_types=1);
/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
				$this->atomic(function () {
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
