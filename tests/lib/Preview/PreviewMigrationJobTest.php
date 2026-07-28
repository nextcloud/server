<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Preview;

use OC\Core\BackgroundJobs\PreviewMigrationJob;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\PreviewMigrationService;
use OC\Preview\PreviewService;
use OC\Preview\Storage\StorageFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class PreviewMigrationJobTest extends TestCase {
	private IAppData $previewAppData;
	private PreviewMapper $previewMapper;
	private IAppConfig&MockObject $appConfig;
	private IConfig $config;
	private StorageFactory $storageFactory;
	private PreviewService $previewService;
	private IDBConnection $db;
	private IMimeTypeLoader&MockObject $mimeTypeLoader;
	private IMimeTypeDetector&MockObject $mimeTypeDetector;
	private LoggerInterface&MockObject $logger;

	#[\Override]
	public function setUp(): void {
		parent::setUp();
		$this->previewAppData = Server::get(IAppDataFactory::class)->get('preview');
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->config = Server::get(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->expects($this->any())
			->method('getValueBool')
			->willReturn(false);
		$this->appConfig->expects($this->any())
			->method('setValueBool')
			->willReturn(true);
		$this->storageFactory = Server::get(StorageFactory::class);
		$this->previewService = Server::get(PreviewService::class);
		$this->db = Server::get(IDBConnection::class);

		$qb = $this->db->getQueryBuilder();
		$qb->delete('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter(5)))
			->executeStatement();

		$qb = $this->db->getQueryBuilder();
		$qb->insert('filecache')
			->values([
				'fileid' => $qb->createNamedParameter(5),
				'storage' => $qb->createNamedParameter(1),
				'path' => $qb->createNamedParameter('test/abc'),
				'path_hash' => $qb->createNamedParameter(md5('test')),
				'parent' => $qb->createNamedParameter(0),
				'name' => $qb->createNamedParameter('abc'),
				'mimetype' => $qb->createNamedParameter(42),
				'size' => $qb->createNamedParameter(1000),
				'mtime' => $qb->createNamedParameter(1000),
				'storage_mtime' => $qb->createNamedParameter(1000),
				'encrypted' => $qb->createNamedParameter(0),
				'unencrypted_size' => $qb->createNamedParameter(0),
				'etag' => $qb->createNamedParameter('abcdefg'),
				'permissions' => $qb->createNamedParameter(0),
				'checksum' => $qb->createNamedParameter('abcdefg'),
			])->executeStatement();

		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->mimeTypeDetector->method('detectPath')->willReturn('image/png');
		$this->mimeTypeLoader = $this->createMock(IMimeTypeLoader::class);
		$this->mimeTypeLoader->method('getId')->with('image/png')->willReturn(42);
		$this->mimeTypeLoader->method('getMimetypeById')->with(42)->willReturn('image/png');
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	#[\Override]
	public function tearDown(): void {
		foreach ($this->previewAppData->getDirectoryListing() as $folder) {
			$folder->delete();
		}
		$this->previewService->deleteAll();

		$qb = $this->db->getQueryBuilder();
		$qb->delete('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter(5)))
			->executeStatement();
		parent::tearDown();
	}

	#[TestDox('Test the migration from the legacy flat hierarchy to the new database format')]
	public function testMigrationLegacyPath(): void {
		$folder = $this->previewAppData->newFolder('5');
		$folder->newFile('64-64-crop.jpg', 'abcdefg');
		$folder->newFile('128-128-crop.png', 'abcdefg');
		$this->assertEquals(1, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));

		$job = new PreviewMigrationJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			Server::get(IRootFolder::class),
			new PreviewMigrationService(
				$this->config,
				Server::get(IRootFolder::class),
				$this->logger,
				$this->mimeTypeDetector,
				$this->mimeTypeLoader,
				Server::get(IDBConnection::class),
				$this->previewMapper,
				$this->storageFactory,
				Server::get(IAppDataFactory::class),
			),
			$this->logger,
		);
		$this->invokePrivate($job, 'run', [[]]);
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));
	}

	private static function getInternalFolder(string $name): string {
		return implode('/', str_split(substr(md5($name), 0, 7))) . '/' . $name;
	}

	private function createJob(): PreviewMigrationJob {
		return new PreviewMigrationJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			Server::get(IRootFolder::class),
			new PreviewMigrationService(
				$this->config,
				Server::get(IRootFolder::class),
				$this->logger,
				$this->mimeTypeDetector,
				$this->mimeTypeLoader,
				Server::get(IDBConnection::class),
				$this->previewMapper,
				$this->storageFactory,
				Server::get(IAppDataFactory::class),
			),
			$this->logger,
		);
	}

	private function insertFilecacheRow(string $path, string $etag): int {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('filecache')
			->values([
				'storage' => $qb->createNamedParameter(1),
				'path' => $qb->createNamedParameter($path),
				'path_hash' => $qb->createNamedParameter(md5($path)),
				'parent' => $qb->createNamedParameter(0),
				'name' => $qb->createNamedParameter(basename($path)),
				'mimetype' => $qb->createNamedParameter(42),
				'size' => $qb->createNamedParameter(1000),
				'mtime' => $qb->createNamedParameter(1000),
				'storage_mtime' => $qb->createNamedParameter(1000),
				'encrypted' => $qb->createNamedParameter(0),
				'unencrypted_size' => $qb->createNamedParameter(0),
				'etag' => $qb->createNamedParameter($etag),
				'permissions' => $qb->createNamedParameter(0),
				'checksum' => $qb->createNamedParameter($etag),
			])->executeStatement();
		return $qb->getLastInsertId();
	}

	private function deleteFilecacheRow(int $fileId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->executeStatement();
	}

	#[TestDox('A single run must migrate multiple different fileids in one pass, mixing both folder structures')]
	public function testMigrationMultipleFileIds(): void {
		$otherFileId = $this->insertFilecacheRow('test/def', 'xyz123');

		try {
			$flatFolder = $this->previewAppData->newFolder('5');
			$flatFolder->newFile('64-64-crop.jpg', 'abcdefg');

			$hierFolder = $this->previewAppData->newFolder(self::getInternalFolder((string)$otherFileId));
			$hierFolder->newFile('128-128.png', 'abcdefg');

			$this->invokePrivate($this->createJob(), 'run', [[]]);

			$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
			$this->assertEquals(1, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));
			$this->assertEquals(1, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile($otherFileId))));
		} finally {
			$this->deleteFilecacheRow($otherFileId);
		}
	}

	#[TestDox('Re-migrating a preview that already exists must skip the insert and still clean up the stale filecache row')]
	public function testMigrationSkipsDuplicatePreview(): void {
		$folder = $this->previewAppData->newFolder('5');
		$folder->newFile('64-64-crop.jpg', 'abcdefg');

		// Simulate a preview that was already migrated by an earlier, interrupted run:
		// same fileid/width/height/mimetype/cropped/version as what this migration
		// would produce for the file created above, so the insert below hits the
		// `previews_file_uniq_idx` unique constraint.
		$existing = Preview::fromPath('5/64-64-crop.jpg', $this->mimeTypeDetector);
		$this->assertNotFalse($existing);
		$existing->setFileId(5);
		$existing->setStorageId(1);
		$existing->setSourceMimeType('image/png');
		$existing->setEtag('abcdefg');
		$existing->setSize(7);
		$existing->setMtime(1000);
		$existing->setEncrypted(false);
		$existing->generateId();
		$this->previewMapper->insert($existing);

		$this->invokePrivate($this->createJob(), 'run', [[]]);

		// No duplicate preview row was inserted, but the legacy folder and its stale
		// filecache row were still cleaned up.
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(1, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));
	}

	#[TestDox('An orphaned preview folder whose source file no longer exists must be deleted, not migrated')]
	public function testMigrationDeletesOrphanedPreview(): void {
		$orphanFileId = 9999998;
		$folder = $this->previewAppData->newFolder((string)$orphanFileId);
		$folder->newFile('64-64-crop.jpg', 'abcdefg');

		$this->invokePrivate($this->createJob(), 'run', [[]]);

		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile($orphanFileId))));
	}

	#[TestDox('run() must complete without error when there is nothing to migrate')]
	public function testMigrationWithoutAnyPreviews(): void {
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));

		$this->invokePrivate($this->createJob(), 'run', [[]]);

		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));
	}

	#[TestDox("Test the migration from the 'new' nested hierarchy to the database format")]
	public function testMigrationPath(): void {
		$folder = $this->previewAppData->newFolder(self::getInternalFolder((string)5));
		$folder->newFile('64-64-crop.jpg', 'abcdefg');
		$folder->newFile('128-128-crop.png', 'abcdefg');

		$folder = $this->previewAppData->getFolder(self::getInternalFolder((string)5));
		$this->assertEquals(2, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));

		$job = new PreviewMigrationJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			Server::get(IRootFolder::class),
			new PreviewMigrationService(
				$this->config,
				Server::get(IRootFolder::class),
				$this->logger,
				$this->mimeTypeDetector,
				$this->mimeTypeLoader,
				Server::get(IDBConnection::class),
				$this->previewMapper,
				$this->storageFactory,
				Server::get(IAppDataFactory::class),
			),
			$this->logger,
		);
		$this->invokePrivate($job, 'run', [[]]);
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));
	}

	#[TestDox("Test the migration from the 'new' nested hierarchy to the database format")]
	public function testMigrationPathWithVersion(): void {
		$folder = $this->previewAppData->newFolder(self::getInternalFolder((string)5));
		// No version
		$folder->newFile('128-128-crop.png', 'abcdefg');
		$folder->newFile('256-256-max.png', 'abcdefg');
		$folder->newFile('128-128.png', 'abcdefg');

		// Version 1000
		$folder->newFile('1000-128-128-crop.png', 'abcdefg');
		$folder->newFile('1000-256-256-max.png', 'abcdefg');
		$folder->newFile('1000-128-128.png', 'abcdefg');

		// Version 1001
		$folder->newFile('1001-128-128-crop.png', 'abcdefg');
		$folder->newFile('1001-256-256-max.png', 'abcdefg');
		$folder->newFile('1001-128-128.png', 'abcdefg');

		$folder = $this->previewAppData->getFolder(self::getInternalFolder((string)5));
		$this->assertEquals(9, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));

		$job = new PreviewMigrationJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			Server::get(IRootFolder::class),
			new PreviewMigrationService(
				$this->config,
				Server::get(IRootFolder::class),
				$this->logger,
				$this->mimeTypeDetector,
				$this->mimeTypeLoader,
				Server::get(IDBConnection::class),
				$this->previewMapper,
				$this->storageFactory,
				Server::get(IAppDataFactory::class),
			),
			$this->logger,
		);
		$this->invokePrivate($job, 'run', [[]]);
		$previews = iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5));
		$this->assertEquals(9, count($previews));
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));

		$nameVersionMapping = [];
		foreach ($previews as $preview) {
			$nameVersionMapping[$preview->getName($this->mimeTypeLoader)] = $preview->getVersion();
		}

		$this->assertEquals([
			'1000-128-128-crop.png' => 1000,
			'1000-128-128.png' => 1000,
			'1000-256-256-max.png' => 1000,
			'1001-128-128-crop.png' => 1001,
			'1001-128-128.png' => 1001,
			'1001-256-256-max.png' => 1001,
			'128-128-crop.png' => null,
			'128-128.png' => null,
			'256-256-max.png' => null,
		], $nameVersionMapping);
	}
}
