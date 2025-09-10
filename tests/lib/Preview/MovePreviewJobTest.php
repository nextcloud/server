<?php

namespace lib\Preview;

use OC\Core\BackgroundJobs\MovePreviewJob;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Test\TestCase;

/**
 * @group DB
 */
#[CoversClass(MovePreviewJob::class)]
class MovePreviewJobTest extends TestCase {
	private IAppData $previewAppData;

	public function setUp(): void {
		parent::setUp();
		$this->previewAppData = Server::get(IAppDataFactory::class)->get('preview');
	}

	#[TestDox("Test the migration from the legacy flat hierarchy to the new one")]
	function testMigrationLegacyPath(): void {
		$folder = $this->previewAppData->newFolder(5);
		$file = $folder->newFile('64-64-crop.png', 'abcdefg');
		$job = Server::get(MovePreviewJob::class);
		$this->invokePrivate($job, 'run', []);
	}
}
