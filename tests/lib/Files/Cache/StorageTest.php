<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Cache\Storage;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group('DB')]
class StorageTest extends \Test\TestCase {
	private IDBConnection $connection;
	private ?string $createdStorageId = null;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		Storage::getGlobalCache()->clearCache();
	}

	protected function tearDown(): void {
		Storage::getGlobalCache()->clearCache();

		if ($this->createdStorageId !== null) {
			$query = $this->connection->getQueryBuilder();
			$query->delete('storages')
				->where(
					$query->expr()->eq('id', $query->createNamedParameter($this->createdStorageId))
				)
				->executeStatement();

			$this->createdStorageId = null;
		}

		parent::tearDown();
	}

	public function testSetAvailabilityInvalidatesGlobalCacheForStringAndNumericLookups(): void {
		$storageId = 'test::availability-cache-' . uniqid('', true);
		$this->createdStorageId = $storageId;

		$storage = new Storage($storageId, true, $this->connection);
		$numericId = $storage->getNumericId();

		$globalCache = Storage::getGlobalCache();

		// Prime both process-local caches with the initial persisted state.
		$initialById = $globalCache->getStorageInfo($storageId);
		$initialByNumericId = $globalCache->getStorageInfoByNumericId($numericId);

		$this->assertIsArray($initialById);
		$this->assertIsArray($initialByNumericId);

		$this->assertSame($storageId, $initialById['id']);
		$this->assertSame($numericId, $initialById['numeric_id']);
		$this->assertTrue($initialById['available']);
		$this->assertSame(0, $initialById['last_checked']);

		$this->assertSame($storageId, $initialByNumericId['id']);
		$this->assertSame($numericId, $initialByNumericId['numeric_id']);
		$this->assertTrue($initialByNumericId['available']);
		$this->assertSame(0, $initialByNumericId['last_checked']);

		$before = time();
		$delay = 600;

		$storage->setAvailability(false, $delay);

		// Both lookup directions must now observe fresh persisted state rather than
		// stale data from the process-local cache.
		$updatedById = $globalCache->getStorageInfo($storageId);
		$updatedByNumericId = $globalCache->getStorageInfoByNumericId($numericId);

		$this->assertIsArray($updatedById);
		$this->assertIsArray($updatedByNumericId);

		$this->assertSame($storageId, $updatedById['id']);
		$this->assertSame($numericId, $updatedById['numeric_id']);
		$this->assertFalse($updatedById['available']);
		$this->assertGreaterThanOrEqual($before + $delay, $updatedById['last_checked']);
		$this->assertLessThanOrEqual($before + $delay + 1, $updatedById['last_checked']);

		$this->assertSame($storageId, $updatedByNumericId['id']);
		$this->assertSame($numericId, $updatedByNumericId['numeric_id']);
		$this->assertFalse($updatedByNumericId['available']);
		$this->assertSame($updatedById['last_checked'], $updatedByNumericId['last_checked']);
	}
}
