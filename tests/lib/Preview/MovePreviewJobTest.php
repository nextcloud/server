<?php

namespace lib\Preview;

use OC\Core\BackgroundJobs\MovePreviewJob;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\StorageFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
#[CoversClass(MovePreviewJob::class)]
class MovePreviewJobTest extends TestCase {
	private IAppData $previewAppData;
	private PreviewMapper $previewMapper;
	private IAppConfig&MockObject $appConfig;
	private StorageFactory $storageFactory;

	public function setUp(): void {
		parent::setUp();
		$this->previewAppData = Server::get(IAppDataFactory::class)->get('preview');
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->expects($this->any())
			->method('getValueBool')
			->willReturn(false);
		$this->storageFactory = Server::get(StorageFactory::class);
	}

	public function tearDown(): void {
		foreach ($this->previewMapper->getAvailablePreviewForFile(5) as $preview) {
			$this->storageFactory->deletePreview($preview);
			$this->previewMapper->delete($preview);
		}

		foreach ($this->previewAppData->getDirectoryListing() as $folder) {
			$folder->delete();
		}
	}

	#[TestDox("Test the migration from the legacy flat hierarchy to the new database format")]
	function testMigrationLegacyPath(): void {
		$folder = $this->previewAppData->newFolder(5);
		$folder->newFile('64-64-crop.jpg', 'abcdefg');
		$folder->newFile('128-128-crop.png', 'abcdefg');
		$this->assertEquals(1, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewForFile(5))));

		$job = new MovePreviewJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->previewMapper,
			$this->storageFactory,
			Server::get(IDBConnection::class),
			Server::get(IAppDataFactory::class)
		);
		$this->invokePrivate($job, 'run', [[]]);
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count(iterator_to_array($this->previewMapper->getAvailablePreviewForFile(5))));
	}

	private static function getInternalFolder(string $name): string {
		return implode('/', str_split(substr(md5($name), 0, 7))) . '/' . $name;
	}

	#[TestDox("Test the migration from the 'new' nested hierarchy to the database format")]
	function testMigrationPath(): void {
		$folder = $this->previewAppData->newFolder(self::getInternalFolder(5));
		$folder->newFile('64-64-crop.jpg', 'abcdefg');
		$folder->newFile('128-128-crop.png', 'abcdefg');

		$folder = $this->previewAppData->getFolder(self::getInternalFolder(5));
		$this->assertEquals(2, count($folder->getDirectoryListing()));
		$this->assertEquals(0, count(iterator_to_array($this->previewMapper->getAvailablePreviewForFile(5))));

		$job = new MovePreviewJob(
			Server::get(ITimeFactory::class),
			$this->appConfig,
			$this->previewMapper,
			$this->storageFactory,
			Server::get(IDBConnection::class),
			Server::get(IAppDataFactory::class)
		);
		$this->invokePrivate($job, 'run', [[]]);
		$this->assertEquals(0, count($this->previewAppData->getDirectoryListing()));
		$this->assertEquals(2, count(iterator_to_array($this->previewMapper->getAvailablePreviewForFile(5))));
	}
}
