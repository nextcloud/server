<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Preview;

use OC\Core\BackgroundJobs\MovePreviewJob;
use OC\Preview\Db\PreviewMapper;
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
use OCP\Snowflake\IGenerator;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class MovePreviewJobTest extends TestCase {
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

	public function tearDown(): void {
		foreach ($this->previewAppData->getDirectoryListing() as $folder) {
			$folder->delete();
		}
		$this->previewService->deleteAll();

		$qb = $this->db->getQueryBuilder();
		$qb->delete('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter(5)))
			->executeStatement();
	}

	#[TestDox('Test the migration from the legacy flat hierarchy to the new database format')]
	public function testMigrationLegacyPath(): void {
		$folder = $this->previewAppData->newFolder('5');
		$folder->newFile('64-64-crop.jpg', 'abcdefg');
		$folder->newFile('128-128-crop.png', 'abcdefg');
		$this->assertEquals(1, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));

		$job = new MovePreviewJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			$this->previewMapper,
			$this->storageFactory,
			Server::get(IDBConnection::class),
			Server::get(IRootFolder::class),
			$this->mimeTypeDetector,
			$this->mimeTypeLoader,
			$this->logger,
			Server::get(IGenerator::class),
			Server::get(IAppDataFactory::class),
		);
		$this->invokePrivate($job, 'run', [[]]);
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));
	}

	private static function getInternalFolder(string $name): string {
		return implode('/', str_split(substr(md5($name), 0, 7))) . '/' . $name;
	}

	#[TestDox("Test the migration from the 'new' nested hierarchy to the database format")]
	public function testMigrationPath(): void {
		$folder = $this->previewAppData->newFolder(self::getInternalFolder((string)5));
		$folder->newFile('64-64-crop.jpg', 'abcdefg');
		$folder->newFile('128-128-crop.png', 'abcdefg');

		$folder = $this->previewAppData->getFolder(self::getInternalFolder((string)5));
		$this->assertEquals(2, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewsForFile(5))));

		$job = new MovePreviewJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			$this->previewMapper,
			$this->storageFactory,
			Server::get(IDBConnection::class),
			Server::get(IRootFolder::class),
			$this->mimeTypeDetector,
			$this->mimeTypeLoader,
			$this->logger,
			Server::get(IGenerator::class),
			Server::get(IAppDataFactory::class)
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

		$job = new MovePreviewJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->config,
			$this->previewMapper,
			$this->storageFactory,
			Server::get(IDBConnection::class),
			Server::get(IRootFolder::class),
			$this->mimeTypeDetector,
			$this->mimeTypeLoader,
			$this->logger,
			Server::get(IGenerator::class),
			Server::get(IAppDataFactory::class)
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
