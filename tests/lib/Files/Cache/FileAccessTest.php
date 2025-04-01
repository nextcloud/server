<?php
/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Cache\CacheEntry;
use OC\Files\Cache\FileAccess;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class FileAccessTest extends TestCase {
	/** @var IDBConnection */
	private $dbConnection;

	/** @var FileAccess */
	private $fileAccess;

	protected function setUp(): void {
		parent::setUp();

		// Setup the actual database connection (assume the database is configured properly in PHPUnit setup)
		$this->dbConnection = \OC::$server->get(IDBConnection::class);

		// Ensure FileAccess is instantiated with the real connection
		$this->fileAccess = new FileAccess(
			$this->dbConnection,
			\OC::$server->get(\OC\SystemConfig::class),
			\OC::$server->get(LoggerInterface::class),
			\OC::$server->get(\OC\FilesMetadata\FilesMetadataManager::class),
			\OC::$server->get(\OCP\Files\IMimeTypeLoader::class)
		);

		// Clear and prepare `filecache` table for tests
		$queryBuilder = $this->dbConnection->getQueryBuilder();
		$queryBuilder->delete('filecache')->executeStatement();

		// Clean up potential leftovers from other tests
		$queryBuilder = $this->dbConnection->getQueryBuilder();
		$queryBuilder->delete('mounts')->executeStatement();


		$this->setUpTestDatabaseForGetDistinctMounts();
		$this->setUpTestDatabaseForGetByAncestorInStorage();
	}

	private function setUpTestDatabaseForGetDistinctMounts(): void {
		$queryBuilder = $this->dbConnection->getQueryBuilder();

		// Insert test data
		$queryBuilder->insert('mounts')
			->values([
				'storage_id' => $queryBuilder->createNamedParameter(1, IQueryBuilder::PARAM_INT),
				'root_id' => $queryBuilder->createNamedParameter(10, IQueryBuilder::PARAM_INT),
				'mount_provider_class' => $queryBuilder->createNamedParameter('TestProviderClass1'),
				'mount_point' => $queryBuilder->createNamedParameter('/files'),
				'user_id' => $queryBuilder->createNamedParameter('test'),
			])
			->executeStatement();

		$queryBuilder->insert('mounts')
			->values([
				'storage_id' => $queryBuilder->createNamedParameter(2, IQueryBuilder::PARAM_INT),
				'root_id' => $queryBuilder->createNamedParameter(20, IQueryBuilder::PARAM_INT),
				'mount_provider_class' => $queryBuilder->createNamedParameter('TestProviderClass2'),
				'mount_point' => $queryBuilder->createNamedParameter('/cache'),
				'user_id' => $queryBuilder->createNamedParameter('test'),
			])
			->executeStatement();

		$queryBuilder->insert('mounts')
			->values([
				'storage_id' => $queryBuilder->createNamedParameter(3, IQueryBuilder::PARAM_INT),
				'root_id' => $queryBuilder->createNamedParameter(30, IQueryBuilder::PARAM_INT),
				'mount_provider_class' => $queryBuilder->createNamedParameter('TestProviderClass1'),
				'mount_point' => $queryBuilder->createNamedParameter('/trashbin'),
				'user_id' => $queryBuilder->createNamedParameter('test'),
			])
			->executeStatement();
	}

	/**
	 * Test that getDistinctMounts returns all mounts without filters
	 */
	public function testGetDistinctMountsWithoutFilters(): void {
		$result = iterator_to_array($this->fileAccess->getDistinctMounts());

		$this->assertCount(3, $result);

		$this->assertEquals([
			'storage_id' => 1,
			'root_id' => 10,
			'override_root' => 10,
		], $result[0]);

		$this->assertEquals([
			'storage_id' => 2,
			'root_id' => 20,
			'override_root' => 20,
		], $result[1]);

		$this->assertEquals([
			'storage_id' => 3,
			'root_id' => 30,
			'override_root' => 30,
		], $result[2]);
	}

	/**
	 * Test that getDistinctMounts applies filtering by mount providers
	 */
	public function testGetDistinctMountsWithMountProviderFilter(): void {
		$result = iterator_to_array($this->fileAccess->getDistinctMounts(['TestProviderClass1']));

		$this->assertCount(2, $result);

		$this->assertEquals([
			'storage_id' => 1,
			'root_id' => 10,
			'override_root' => 10,
		], $result[0]);

		$this->assertEquals([
			'storage_id' => 3,
			'root_id' => 30,
			'override_root' => 30,
		], $result[1]);
	}

	/**
	 * Test that getDistinctMounts excludes certain mount points
	 */
	public function testGetDistinctMountsWithExclusionFilter(): void {
		$result = iterator_to_array($this->fileAccess->getDistinctMounts([], '/cache'));

		$this->assertCount(2, $result);

		$this->assertEquals([
			'storage_id' => 1,
			'root_id' => 10,
			'override_root' => 10,
		], $result[0]);

		$this->assertEquals([
			'storage_id' => 3,
			'root_id' => 30,
			'override_root' => 30,
		], $result[1]);
	}

	/**
	 * Test that getDistinctMounts rewrites home directory paths
	 */
	public function testGetDistinctMountsWithRewriteHomeDirectories(): void {
		// Add additional test data for a home directory mount
		$queryBuilder = $this->dbConnection->getQueryBuilder();
		$queryBuilder->insert('mounts')
			->values([
				'storage_id' => $queryBuilder->createNamedParameter(4, IQueryBuilder::PARAM_INT),
				'root_id' => $queryBuilder->createNamedParameter(40, IQueryBuilder::PARAM_INT),
				'mount_provider_class' => $queryBuilder->createNamedParameter(\OC\Files\Mount\LocalHomeMountProvider::class),
				'mount_point' => $queryBuilder->createNamedParameter('/home/user'),
				'user_id' => $queryBuilder->createNamedParameter('test'),
			])
			->executeStatement();

		// Simulate adding a "files" directory to the filecache table
		$queryBuilder->delete('filecache')->executeStatement();
		$queryBuilder->insert('filecache')
			->values([
				'fileid' => $queryBuilder->createNamedParameter(99, IQueryBuilder::PARAM_INT),
				'storage' => $queryBuilder->createNamedParameter(4, IQueryBuilder::PARAM_INT),
				'path' => $queryBuilder->createNamedParameter('files'),
			])
			->executeStatement();

		$result = iterator_to_array($this->fileAccess->getDistinctMounts());

		$this->assertEquals([
			'storage_id' => 4,
			'root_id' => 40,
			'override_root' => 99,
		], end($result));
	}

	private function setUpTestDatabaseForGetByAncestorInStorage(): void {
		// prepare `filecache` table for tests
		$queryBuilder = $this->dbConnection->getQueryBuilder();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 1,
				'parent' => 0,
				'path' => $queryBuilder->createNamedParameter('files'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files')),
				'storage' => $queryBuilder->createNamedParameter(1),
				'name' => $queryBuilder->createNamedParameter('files'),
				'mimetype' => 1,
				'encrypted' => 0,
			])
			->executeStatement();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 2,
				'parent' => 1,
				'path' => $queryBuilder->createNamedParameter('files/documents'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files/documents')),
				'storage' => $queryBuilder->createNamedParameter(1),
				'name' => $queryBuilder->createNamedParameter('documents'),
				'mimetype' => 2,
				'encrypted' => 1,
			])
			->executeStatement();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 3,
				'parent' => 1,
				'path' => $queryBuilder->createNamedParameter('files/photos'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files/photos')),
				'storage' => $queryBuilder->createNamedParameter(1),
				'name' => $queryBuilder->createNamedParameter('photos'),
				'mimetype' => 3,
				'encrypted' => 1,
			])
			->executeStatement();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 4,
				'parent' => 3,
				'path' => $queryBuilder->createNamedParameter('files/photos/endtoendencrypted'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files/photos/endtoendencrypted')),
				'storage' => $queryBuilder->createNamedParameter(1),
				'name' => $queryBuilder->createNamedParameter('endtoendencrypted'),
				'mimetype' => 4,
				'encrypted' => 0,
			])
			->executeStatement();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 5,
				'parent' => 0,
				'path' => $queryBuilder->createNamedParameter('files/serversideencrypted'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files/serversideencrypted')),
				'storage' => $queryBuilder->createNamedParameter(1),
				'name' => $queryBuilder->createNamedParameter('serversideencrypted'),
				'mimetype' => 4,
				'encrypted' => 1,
			])
			->executeStatement();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 6,
				'parent' => 0,
				'path' => $queryBuilder->createNamedParameter('files/storage2'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files/storage2')),
				'storage' => $queryBuilder->createNamedParameter(2),
				'name' => $queryBuilder->createNamedParameter('storage2'),
				'mimetype' => 5,
				'encrypted' => 0,
			])
			->executeStatement();

		$queryBuilder->insert('filecache')
			->values([
				'fileid' => 7,
				'parent' => 6,
				'path' => $queryBuilder->createNamedParameter('files/storage2/file'),
				'path_hash' => $queryBuilder->createNamedParameter(md5('files/storage2/file')),
				'storage' => $queryBuilder->createNamedParameter(2),
				'name' => $queryBuilder->createNamedParameter('file'),
				'mimetype' => 6,
				'encrypted' => 0,
			])
			->executeStatement();
	}

	/**
	 * Test fetching files by ancestor in storage.
	 */
	public function testGetByAncestorInStorage(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			1, // storageId
			1, // rootId
			0, // lastFileId
			[], // mimeTypes
			true, // include end-to-end encrypted files
			true, // include server-side encrypted files
			10 // maxResults
		);

		$result = iterator_to_array($generator);

		$this->assertCount(4, $result);

		$paths = array_map(fn(CacheEntry $entry) => $entry->getPath(), $result);
		$this->assertEquals([
			'files/documents',
			'files/photos',
			'files/photos/endtoendencrypted',
			'files/serversideencrypted',
		], $paths);
	}

	/**
	 * Test filtering by mime types.
	 */
	public function testGetByAncestorInStorageWithMimeTypes(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			1,
			1,
			0,
			[2], // Only include documents (mimetype=2)
			true,
			true,
			10
		);

		$result = iterator_to_array($generator);

		$this->assertCount(1, $result);
		$this->assertEquals('files/documents', $result[0]->getPath());
	}

	/**
	 * Test excluding end-to-end encrypted files.
	 */
	public function testGetByAncestorInStorageWithoutEndToEndEncrypted(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			1,
			1,
			0,
			[],
			false, // exclude end-to-end encrypted files
			true,
			10
		);

		$result = iterator_to_array($generator);

		var_dump($result);

		$this->assertCount(1, $result);
		$this->assertEquals('files/serversideencrypted', $result[0]->getPath());
	}

	/**
	 * Test excluding server-side encrypted files.
	 */
	public function testGetByAncestorInStorageWithoutServerSideEncrypted(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			1,
			1,
			0,
			[],
			true,
			false, // exclude server-side encrypted files
			10
		);

		$result = iterator_to_array($generator);

		$this->assertCount(1, $result);
		$this->assertEquals('files/photos/endtoendencrypted', $result[0]->getPath());
	}

	/**
	 * Test max result limits.
	 */
	public function testGetByAncestorInStorageWithMaxResults(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			1,
			1,
			0,
			[],
			true,
			true,
			1 // Limit to 1 result
		);

		$result = iterator_to_array($generator);

		$this->assertCount(1, $result);
		$this->assertEquals('files/documents', $result[0]->getPath());
	}

	/**
	 * Test rootId filter
	 */
	public function testGetByAncestorInStorageWithRootIdFilter(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			1,
			3, // Filter by rootId
			0,
			[],
			true,
			true,
			10
		);

		$result = iterator_to_array($generator);

		$this->assertCount(1, $result);
		$this->assertEquals('files/photos/endtoendencrypted', $result[0]->getPath());
	}

	/**
	 * Test rootId filter
	 */
	public function testGetByAncestorInStorageWithStorageFilter(): void {
		$generator = $this->fileAccess->getByAncestorInStorage(
			2, // Filter by storage
			6, // and by rootId
			0,
			[],
			true,
			true,
			10
		);

		$result = iterator_to_array($generator);

		$this->assertCount(1, $result);
		$this->assertEquals('files/storage2/file', $result[0]->getPath());
	}
}
