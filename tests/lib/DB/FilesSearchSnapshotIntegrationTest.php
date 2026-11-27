<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Josh Duffy <joshduff.jd@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\DB\ConnectionFactory;
use OC\DB\FilesSearchSnapshot;
use OC\DB\SchemaWrapper;
use OC\Files\Storage\Temporary;
use OC\SystemConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class FilesSearchSnapshotIntegrationTest extends TestCase {
	private IDBConnection $shared;
	private FilesSearchSnapshot $snapshot;
	private array $createdIndexes = [];

	protected function setUp(): void {
		parent::setUp();
		$this->shared = Server::get(IDBConnection::class);
		if ($this->shared->getDatabaseProvider(true) !== IDBConnection::PLATFORM_MARIADB) {
			$this->markTestSkipped('The bounded search capability targets MariaDB 10.11.');
		}
		$result = $this->shared->executeQuery('SELECT VERSION()');
		try {
			$version = (string)$result->fetchOne();
		} finally {
			$result->closeCursor();
		}
		if (preg_match('/^10\.11\.\d+(?:-|$)/', $version) !== 1) {
			$this->markTestSkipped('The bounded search capability targets MariaDB 10.11.');
		}
		$this->overwriteSystemConfig('files_search_bounded_name', true);
		$this->overwriteSystemConfig('files_search_bounded_name_limits', [
			'statement_seconds' => 2,
			'lock_seconds' => 1,
			'total_seconds' => 5,
		]);
		$schema = new SchemaWrapper(Server::get(Connection::class));
		$table = $schema->getTable('filecache');
		foreach ([
			'fs_storage_name_mtime_fileid' => ['storage', 'name', 'mtime', 'fileid'],
			'fs_storage_mtime_fileid_name' => ['storage', 'mtime', 'fileid', 'name'],
		] as $name => $columns) {
			if (!$table->hasIndex($name)) {
				$table->addIndex($columns, $name);
				$this->createdIndexes[] = $name;
			}
		}
		if ($this->createdIndexes !== []) {
			$this->shared->migrateToSchema($schema->getWrappedSchema());
		}
		$this->snapshot = new FilesSearchSnapshot(Server::get(SystemConfig::class), Server::get(ConnectionFactory::class));
	}

	protected function tearDown(): void {
		try {
			if ($this->createdIndexes !== []) {
				$schema = new SchemaWrapper(Server::get(Connection::class));
				$table = $schema->getTable('filecache');
				foreach ($this->createdIndexes as $name) {
					$table->dropIndex($name);
				}
				$this->shared->migrateToSchema($schema->getWrappedSchema());
			}
		} finally {
			parent::tearDown();
		}
	}

	public function testOwnedReadOnlySnapshotDoesNotChangeSharedSession(): void {
		$before = $this->sessionState($this->shared);
		$called = false;
		$actual = $this->snapshot->run($this->shared, function (IDBConnection $owned, callable $assertActive, callable $verifyOneToOne) use ($before, &$called): array {
			$called = true;
			self::assertInstanceOf(ConnectionAdapter::class, $owned);
			$state = $this->sessionState($owned);
			self::assertNotSame($before['session_id'], $state['session_id']);
			self::assertSame('REPEATABLE-READ', $state['isolation_level']);
			self::assertSame('1', (string)$state['read_only']);
			self::assertSame('1', (string)$state['active']);
			$assertActive();
			self::assertTrue($verifyOneToOne());
			return ['native-snapshot'];
		});
		self::assertTrue($called, 'The capability must be admitted, not silently fall back.');
		self::assertSame(['native-snapshot'], $actual);
		self::assertSame($before, $this->sessionState($this->shared));
		self::assertFalse($this->shared->inTransaction());
	}

	public function testAdmittedExceptionKeepsIdentityAndCapabilityCanBeReused(): void {
		$failure = new \DomainException('snapshot fixture failure');
		try {
			$this->snapshot->run($this->shared, static function () use ($failure): array {
				throw $failure;
			});
			self::fail('An admitted error must not become native fallback or an empty result.');
		} catch (\DomainException $actual) {
			self::assertSame($failure, $actual);
		}
		self::assertFalse($this->shared->inTransaction());
		self::assertSame([], $this->snapshot->run($this->shared, static fn (): array => []));
	}

	public function testClosedOwnedConnectionCannotSilentlyReconnect(): void {
		$before = $this->sessionState($this->shared);
		try {
			$this->snapshot->run($this->shared, static function (IDBConnection $owned, callable $assertActive): array {
				self::assertInstanceOf(ConnectionAdapter::class, $owned);
				$owned->getInner()->close();
				$assertActive();
				return [];
			});
			self::fail('A lost owned snapshot must remain an error.');
		} catch (\RuntimeException $actual) {
			self::assertSame('Files search snapshot is no longer active', $actual->getMessage());
		}
		self::assertSame($before, $this->sessionState($this->shared));
	}

	public function testReentrantCallDeclinesWithoutInvokingItsBody(): void {
		$actual = $this->snapshot->run($this->shared, function (): array {
			self::assertNull($this->snapshot->run($this->shared, static function (): array {
				self::fail('A busy capability must decline before dispatching a second body.');
			}));
			return ['outer'];
		});
		self::assertSame(['outer'], $actual);
	}

	public function testRecentCandidatesUseDeterministicDescendingTies(): void {
		$storage = new Temporary([]);
		$cache = $storage->getCache();
		$storage->getScanner()->scan('');
		$ids = [];
		try {
			for ($i = 0; $i < 3; $i++) {
				$ids[] = $cache->put('snapshot-fixture-' . $i, [
					'size' => 1,
					'mtime' => 100,
					'mimetype' => 'text/plain',
				]);
			}
			$expected = array_reverse($ids);
			$actual = $this->snapshot->run($this->shared, static function (IDBConnection $owned, callable $assertActive, callable $verifyOneToOne, callable $recentNameIds) use ($cache): array {
				self::assertTrue($verifyOneToOne());
				$recent = $recentNameIds($cache->getNumericStorageId(), '%snapshot-fixture%', 2, false);
				self::assertIsArray($recent, 'The native recent route must be admitted.');
				$assertActive();
				return array_map(static fn (array $row): int => (int)$row['fileid'], $recent);
			});
			self::assertSame(array_slice($expected, 0, 2), $actual);
		} finally {
			$cache->clear();
			$storage->cleanUp();
		}
	}

	private function sessionState(IDBConnection $connection): array {
		$result = $connection->executeQuery('SELECT CONNECTION_ID() AS session_id, @@SESSION.tx_isolation AS isolation_level, @@SESSION.tx_read_only AS read_only, @@SESSION.in_transaction AS active, @@SESSION.max_statement_time AS statement_limit, @@SESSION.lock_wait_timeout AS lock_limit, @@SESSION.innodb_lock_wait_timeout AS innodb_limit');
		try {
			return $result->fetchAssociative();
		} finally {
			$result->closeCursor();
		}
	}
}
