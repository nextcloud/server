<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Tests\Command;

use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Files\Command\DeleteOrphanedFiles;
use OCP\Console\IOutput;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IRootFolder;
use OCP\Files\StorageNotAvailableException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;

/**
 * Class DeleteOrphanedFilesTest
 *
 *
 * @package OCA\Files\Tests\Command
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class DeleteOrphanedFilesTest extends TestCase {

	private DeleteOrphanedFiles $command;
	private IDBConnection $connection;
	private string $user1;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);

		$this->user1 = $this->getUniqueID('user1_');

		$userManager = Server::get(IUserManager::class);
		$userManager->createUser($this->user1, 'pass');

		$this->command = new DeleteOrphanedFiles($this->connection);
	}

	protected function tearDown(): void {
		$userManager = Server::get(IUserManager::class);
		$user1 = $userManager->get($this->user1);
		if ($user1) {
			$user1->delete();
		}

		$this->logout();

		parent::tearDown();
	}

	protected function getFile(int $fileId): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId)));
		return $query->executeQuery()->fetchAllAssociative();
	}

	protected function getMountsCount(int $storageId): int {
		$query = $this->connection->getQueryBuilder();
		$query->select($query->func()->count())
			->from('mounts')
			->where($query->expr()->eq('storage_id', $query->createNamedParameter($storageId)));
		return (int)$query->executeQuery()->fetchOne();
	}

	/**
	 * @param list<int> $fileIds
	 */
	protected function countRows(string $table, string $column, array $fileIds): int {
		// selecting rows instead of COUNT(*), which a sharded query answers once per shard
		$query = $this->connection->getQueryBuilder();
		$query->select($column)
			->from($table)
			->where($query->expr()->in($column, $query->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));
		return count($query->executeQuery()->fetchFirstColumn());
	}

	/**
	 * @param list<string> $calls
	 */
	protected function expectOutput(IOutput&\PHPUnit\Framework\MockObject\MockObject $output, array $calls): void {
		$output
			->expects($this->exactly(count($calls)))
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertSame($expected, $message);
			});
	}

	/**
	 * Test clearing orphaned files
	 */
	public function testClearFiles(): void {
		$output = $this->createMock(IOutput::class);

		$rootFolder = Server::get(IRootFolder::class);

		// scan home storage so that mounts are properly setup
		$rootFolder->getUserFolder($this->user1)->getStorage()->getScanner()->scan('');

		$this->loginAsUser($this->user1);

		$view = new View('/' . $this->user1 . '/');
		$view->mkdir('files/test');

		$fileInfo = $view->getFileInfo('files/test');

		$storageId = $fileInfo->getStorage()->getId();
		$numericStorageId = $fileInfo->getStorage()->getCache()->getNumericStorageId();

		$this->assertCount(1, $this->getFile($fileInfo->getId()), 'Asserts that file is available');
		$this->assertEquals(1, $this->getMountsCount($numericStorageId), 'Asserts that mount is available');

		($this->command)($output);

		$this->assertCount(1, $this->getFile($fileInfo->getId()), 'Asserts that file is still available');
		$this->assertEquals(1, $this->getMountsCount($numericStorageId), 'Asserts that mount is still available');

		$qb = $this->connection->getQueryBuilder();
		$storageFileIds = array_map('intval', $qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->eq('storage', $qb->createNamedParameter($numericStorageId, IQueryBuilder::PARAM_INT)))
			->executeQuery()
			->fetchFirstColumn());
		$extendedEntries = $this->countRows('filecache_extended', 'fileid', $storageFileIds);
		$metadataEntries = $this->countRows('files_metadata', 'file_id', $storageFileIds);
		$metadataIndexEntries = $this->countRows('files_metadata_index', 'file_id', $storageFileIds);

		$qb = $this->connection->getQueryBuilder();
		$deletedRows = $qb->delete('storages')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($storageId)))
			->executeStatement();
		$this->assertNotNull($deletedRows, 'Asserts that storage got deleted');
		$this->assertSame(1, $deletedRows, 'Asserts that storage got deleted');

		// parent folder, `files`, ´test` and `welcome.txt` => 4 elements
		$this->expectOutput($output, [
			'3 orphaned file cache entries deleted',
			"$extendedEntries orphaned file cache extended entries deleted",
			"$metadataEntries orphaned file metadata entries deleted",
			"$metadataIndexEntries orphaned file metadata index entries deleted",
			'1 orphaned mount entries deleted',
		]);

		($this->command)($output);

		$this->assertCount(0, $this->getFile($fileInfo->getId()), 'Asserts that file gets cleaned up');
		$this->assertEquals(0, $this->getMountsCount($numericStorageId), 'Asserts that mount gets cleaned up');

		// Rescan folder to add back to cache before deleting
		$rootFolder->getUserFolder($this->user1)->getStorage()->getScanner()->scan('');
		// since we deleted the storage it might throw a (valid) StorageNotAvailableException
		try {
			$view->unlink('files/test');
		} catch (StorageNotAvailableException $e) {
		}
	}

	public function testClearEntriesWithoutFileCacheEntry(): void {
		// remove orphans left behind by other tests so that the counts below only cover this test
		($this->command)($this->createMock(IOutput::class));

		$storage = new Temporary([]);
		$cache = $storage->getCache();
		$cache->put('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$data = ['size' => 1, 'mtime' => 1, 'mimetype' => 'text/plain', 'upload_time' => 25];
		$orphanId = $cache->put('orphan.txt', $data);
		$keptId = $cache->put('kept.txt', $data);

		$metadataManager = Server::get(IFilesMetadataManager::class);
		foreach ([$orphanId, $keptId] as $fileId) {
			$metadata = $metadataManager->getMetadata($fileId, true);
			$metadata->setString('test-key', 'value', true);
			$metadataManager->saveMetadata($metadata);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($orphanId, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		$output = $this->createMock(IOutput::class);
		$this->expectOutput($output, [
			'0 orphaned file cache entries deleted',
			'1 orphaned file cache extended entries deleted',
			'1 orphaned file metadata entries deleted',
			'1 orphaned file metadata index entries deleted',
			'0 orphaned mount entries deleted',
		]);

		($this->command)($output);

		$this->assertSame(0, $this->countRows('filecache_extended', 'fileid', [$orphanId]));
		$this->assertSame(0, $this->countRows('files_metadata', 'file_id', [$orphanId]));
		$this->assertSame(0, $this->countRows('files_metadata_index', 'file_id', [$orphanId]));

		$this->assertSame(1, $this->countRows('filecache_extended', 'fileid', [$keptId]));
		$this->assertSame(1, $this->countRows('files_metadata', 'file_id', [$keptId]));
		$this->assertSame(1, $this->countRows('files_metadata_index', 'file_id', [$keptId]));

		$cache->clear();
	}
}
