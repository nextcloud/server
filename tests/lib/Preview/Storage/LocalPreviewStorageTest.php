<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview\Storage;

use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\LocalPreviewStorage;
use OCP\DB\Exception as DBException;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\ITypedQueryBuilder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LocalPreviewStorageTest extends TestCase {
	private IConfig&MockObject $config;
	private PreviewMapper&MockObject $previewMapper;
	private IAppConfig&MockObject $appConfig;
	private IDBConnection&MockObject $connection;
	private IMimeTypeDetector&MockObject $mimeTypeDetector;
	private LoggerInterface&MockObject $logger;
	private IMimeTypeLoader&MockObject $mimeTypeLoader;
	private IRootFolder&MockObject $rootFolder;
	private string $tmpDir;
	private LocalPreviewStorage $storage;

	/** File ID used across the single-file tests. */
	private const FILE_ID = 1;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = sys_get_temp_dir() . '/nc_preview_test_' . uniqid();
		mkdir($this->tmpDir, 0777, true);

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValueString')
			->with('datadirectory', $this->anything())
			->willReturn($this->tmpDir);

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getAppDataDirectoryName')->willReturn('appdata_test');

		$this->previewMapper = $this->createMock(PreviewMapper::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mimeTypeLoader = $this->createMock(IMimeTypeLoader::class);

		$this->mimeTypeDetector->method('detectPath')->willReturn('image/jpeg');
		$this->mimeTypeLoader->method('getMimetypeById')->willReturn('image/jpeg');

		$this->storage = new LocalPreviewStorage(
			$this->config,
			$this->previewMapper,
			$this->appConfig,
			$this->connection,
			$this->mimeTypeDetector,
			$this->logger,
			$this->mimeTypeLoader,
			$this->rootFolder,
		);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->removeDir($this->tmpDir);
		parent::tearDown();
	}

	private function removeDir(string $path): void {
		if (!is_dir($path)) {
			return;
		}
		foreach (scandir($path) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$full = $path . '/' . $entry;
			is_dir($full) ? $this->removeDir($full) : unlink($full);
		}
		rmdir($path);
	}

	/**
	 * Create a preview file in the legacy flat directory format so the scan
	 * code will attempt to move it to the new nested path.
	 * Returns the absolute path to the created file.
	 */
	private function createFlatPreviewFile(int $fileId, string $previewName): string {
		$dir = $this->tmpDir . '/appdata_test/preview/' . $fileId;
		mkdir($dir, 0777, true);
		$path = $dir . '/' . $previewName;
		file_put_contents($path, 'fake preview data');
		return $path;
	}

	/**
	 * Build a mock IQueryBuilder chain and configure it to return the given
	 * rows from executeQuery()->fetchAssociative().
	 */
	private function buildQueryBuilderMock(array $rows): IQueryBuilder&MockObject {
		$exprMock = $this->createMock(IExpressionBuilder::class);
		$exprMock->method('in')->willReturn('1=1');

		$callIndex = 0;
		$resultMock = $this->createMock(IResult::class);
		$resultMock->method('fetchAssociative')
			->willReturnCallback(static function () use ($rows, &$callIndex) {
				return $rows[$callIndex++] ?? false;
			});

		$qbMock = $this->createMock(ITypedQueryBuilder::class);
		$qbMock->method('selectColumns')->willReturnSelf();
		$qbMock->method('from')->willReturnSelf();
		$qbMock->method('andWhere')->willReturnSelf();
		$qbMock->method('runAcrossAllShards')->willReturnSelf();
		$qbMock->method('executeQuery')->willReturn($resultMock);
		$qbMock->method('expr')->willReturn($exprMock);
		$qbMock->method('createNamedParameter')->willReturn(':param');

		return $qbMock;
	}

	/**
	 * Configure appConfig so migration is considered done, meaning
	 * checkForFileCache = false (no legacy path-hash queries).
	 */
	private function setMigrationDone(): void {
		$this->appConfig->method('getValueBool')
			->with('core', 'previewMovedDone')
			->willReturn(true);
	}

	/**
	 * When fewer previews than SCAN_BATCH_SIZE exist, scan() must still open
	 * and commit a transaction for the tail batch.
	 *
	 * Before the fix: commit() was never called for the tail batch, leaving the
	 * transaction open.
	 */
	public function testScanCommitsFinalBatch(): void {
		$this->setMigrationDone();
		$this->createFlatPreviewFile(self::FILE_ID, '1024-1024.jpg');

		$filecacheRow = [
			'fileid' => (string)self::FILE_ID,
			'storage' => '42',
			'etag' => 'abc',
			'mimetype' => '6',
		];
		$this->connection->method('getTypedQueryBuilder')
			->willReturn($this->buildQueryBuilderMock([$filecacheRow]));

		// Outer batch transaction + one inner savepoint for the insert.
		$this->connection->expects($this->exactly(2))->method('beginTransaction');
		$this->connection->expects($this->exactly(2))->method('commit');
		$this->connection->expects($this->never())->method('rollBack');

		$count = $this->storage->scan();

		$this->assertSame(1, $count);
	}

	/**
	 * When previewMapper->insert() throws a unique-constraint violation, scan()
	 * must roll back only the inner savepoint and continue, leaving the outer
	 * transaction intact so its final commit() succeeds.
	 *
	 * Before the fix: the plain catch swallowed the PHP exception but left the
	 * PostgreSQL transaction in an aborted state, so all subsequent queries
	 * (including commit()) failed with "current transaction is aborted".
	 */
	public function testScanHandlesUniqueConstraintViolation(): void {
		$this->setMigrationDone();
		$this->createFlatPreviewFile(self::FILE_ID, '1024-1024.jpg');

		$filecacheRow = [
			'fileid' => (string)self::FILE_ID,
			'storage' => '42',
			'etag' => 'abc',
			'mimetype' => '6',
		];
		$this->connection->method('getTypedQueryBuilder')
			->willReturn($this->buildQueryBuilderMock([$filecacheRow]));

		$ucvException = new class('duplicate key') extends DBException {
			#[\Override]
			public function getReason(): int {
				return self::REASON_UNIQUE_CONSTRAINT_VIOLATION;
			}
		};
		$this->previewMapper->method('insert')->willThrowException($ucvException);

		// Inner savepoint is rolled back; outer batch transaction is committed.
		$this->connection->expects($this->exactly(2))->method('beginTransaction');
		$this->connection->expects($this->once())->method('commit');
		$this->connection->expects($this->exactly(1))->method('rollBack');

		$count = $this->storage->scan();

		// Even when the DB row already exists the preview file still counts.
		$this->assertSame(1, $count);
	}

	/**
	 * A non-UCE exception from previewMapper->insert() must be re-thrown after
	 * rolling back both the inner savepoint and the outer batch transaction.
	 */
	public function testScanRethrowsUnexpectedInsertException(): void {
		$this->setMigrationDone();
		$this->createFlatPreviewFile(self::FILE_ID, '1024-1024.jpg');

		$filecacheRow = [
			'fileid' => (string)self::FILE_ID,
			'storage' => '42',
			'etag' => 'abc',
			'mimetype' => '6',
		];
		$this->connection->method('getTypedQueryBuilder')
			->willReturn($this->buildQueryBuilderMock([$filecacheRow]));

		$driverException = new class('some driver error') extends DBException {
			#[\Override]
			public function getReason(): int {
				return self::REASON_DRIVER;
			}
		};
		$this->previewMapper->method('insert')->willThrowException($driverException);

		// Inner savepoint rolled back; outer batch also rolled back via rethrow.
		$this->connection->expects($this->exactly(2))->method('beginTransaction');
		$this->connection->expects($this->never())->method('commit');
		$this->connection->expects($this->exactly(2))->method('rollBack');

		$this->expectException(DBException::class);
		$this->storage->scan();
	}

	/**
	 * fetchFilecacheByFileIds() must return a row for every file ID returned by
	 * the query, not just one.  Before the fix, the foreach loop iterated over
	 * the key-value pairs of the first row, so previews for all but the first
	 * file ID were silently deleted (filecache row not found → unlink).
	 */
	public function testScanFetchesAllFilecacheRows(): void {
		$this->setMigrationDone();

		$fileIds = [1, 2, 3];
		foreach ($fileIds as $id) {
			$this->createFlatPreviewFile($id, '1024-1024.jpg');
		}

		$filecacheRows = array_map(static fn (int $id) => [
			'fileid' => (string)$id,
			'storage' => '42',
			'etag' => 'abc',
			'mimetype' => '6',
		], $fileIds);

		$this->connection->method('getTypedQueryBuilder')
			->willReturn($this->buildQueryBuilderMock($filecacheRows));

		// 1 outer batch transaction + 3 inner savepoints (one per preview insert).
		$this->connection->expects($this->exactly(4))->method('beginTransaction');
		$this->connection->expects($this->exactly(4))->method('commit');
		$this->connection->expects($this->never())->method('rollBack');

		$count = $this->storage->scan();

		$this->assertSame(3, $count);
	}
}
