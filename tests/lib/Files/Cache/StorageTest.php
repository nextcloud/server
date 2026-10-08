<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Cache\Storage;
use OC\Files\Storage\Temporary;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICacheEntry;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class StorageTest extends TestCase {
	public function testCleanByMountIdRemovesExtendedAndMetadataEntries(): void {
		$storage = new Temporary([]);
		$cache = $storage->getCache();
		$rootId = $cache->put('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$fileId = $cache->put('foo.txt', ['size' => 1, 'mtime' => 1, 'mimetype' => 'text/plain', 'upload_time' => 25]);

		$metadataManager = Server::get(IFilesMetadataManager::class);
		$metadata = $metadataManager->getMetadata($fileId, true);
		$metadata->setString('test-key', 'value', true);
		$metadataManager->saveMetadata($metadata);

		$mountId = random_int(100000000, 999999999);
		$mountPoint = '/' . $this->getUniqueID('user') . '/files/mount/';
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->insert('mounts')
			->values([
				'storage_id' => $qb->createNamedParameter($cache->getNumericStorageId(), IQueryBuilder::PARAM_INT),
				'root_id' => $qb->createNamedParameter($rootId, IQueryBuilder::PARAM_INT),
				'user_id' => $qb->createNamedParameter('test'),
				'mount_point' => $qb->createNamedParameter($mountPoint),
				'mount_point_hash' => $qb->createNamedParameter(hash('xxh128', $mountPoint)),
				'mount_id' => $qb->createNamedParameter($mountId, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();

		$this->assertSame(1, $this->countRows('filecache_extended', 'fileid', $fileId));
		$this->assertSame(1, $this->countRows('files_metadata', 'file_id', $fileId));
		$this->assertSame(1, $this->countRows('files_metadata_index', 'file_id', $fileId));

		Storage::cleanByMountId($mountId);

		$this->assertSame(0, $this->countRows('filecache', 'fileid', $fileId));
		$this->assertSame(0, $this->countRows('filecache_extended', 'fileid', $fileId));
		$this->assertSame(0, $this->countRows('files_metadata', 'file_id', $fileId));
		$this->assertSame(0, $this->countRows('files_metadata_index', 'file_id', $fileId));
	}

	private function countRows(string $table, string $column, int $fileId): int {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->select($qb->func()->count())
			->from($table)
			->where($qb->expr()->eq($column, $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		return (int)$qb->executeQuery()->fetchOne();
	}
}
