<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Files\Storage\Temporary;
use OC\Preview\BackgroundCleanupJob;
use OC\Preview\Storage\Root;
use OC\PreviewManager;
use OC\SystemConfig;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\Server;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class BackgroundCleanupJobTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class BackgroundCleanupJobTest extends \Test\TestCase {
	use MountProviderTrait;
	use UserTrait;
	private string $userId;
	private bool $trashEnabled;
	private IDBConnection $connection;
	private PreviewManager $previewManager;
	private IRootFolder $rootFolder;
	private IMimeTypeLoader $mimeTypeLoader;
	private ITimeFactory $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->userId = $this->getUniqueID();
		$user = $this->createUser($this->userId, $this->userId);

		$storage = new Temporary([]);
		$this->registerMount($this->userId, $storage, '');

		$this->loginAsUser($this->userId);
		$this->logout();
		$this->loginAsUser($this->userId);

		$appManager = Server::get(IAppManager::class);
		$this->trashEnabled = $appManager->isEnabledForUser('files_trashbin', $user);
		$appManager->disableApp('files_trashbin');

		$this->connection = Server::get(IDBConnection::class);
		$this->previewManager = Server::get(IPreview::class);
		$this->rootFolder = Server::get(IRootFolder::class);
		$this->mimeTypeLoader = Server::get(IMimeTypeLoader::class);
		$this->timeFactory = Server::get(ITimeFactory::class);
	}

	protected function tearDown(): void {
		if ($this->trashEnabled) {
			$appManager = Server::get(IAppManager::class);
			$appManager->enableApp('files_trashbin');
		}

		$this->logout();

		parent::tearDown();
	}

	private function getRoot(): Root {
		return new Root(
			Server::get(IRootFolder::class),
			Server::get(SystemConfig::class)
		);
	}

	private function setup11Previews(): array {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		$files = [];
		for ($i = 0; $i < 11; $i++) {
			$file = $userFolder->newFile($i . '.txt');
			$file->putContent('hello world!');
			$this->previewManager->getPreview($file);
			$files[] = $file;
		}

		return $files;
	}

	private function countPreviews(Root $previewRoot, array $fileIds): int {
		$i = 0;

		foreach ($fileIds as $fileId) {
			try {
				$previewRoot->getFolder((string)$fileId);
			} catch (NotFoundException $e) {
				continue;
			}

			$i++;
		}

		return $i;
	}

	public function testCleanupSystemCron(): void {
		$files = $this->setup11Previews();
		$fileIds = array_map(function (File $f) {
			return $f->getId();
		}, $files);

		$root = $this->getRoot();

		$this->assertSame(11, $this->countPreviews($root, $fileIds));
		$job = new BackgroundCleanupJob($this->timeFactory, $this->connection, $root, $this->mimeTypeLoader, true);
		$job->run([]);

		foreach ($files as $file) {
			$file->delete();
		}

		$root = $this->getRoot();
		$this->assertSame(11, $this->countPreviews($root, $fileIds));
		$job->run([]);

		$root = $this->getRoot();
		$this->assertSame(0, $this->countPreviews($root, $fileIds));
	}

	public function testCleanupAjax(): void {
		if ($this->connection->getShardDefinition('filecache')) {
			$this->markTestSkipped('ajax cron is not supported for sharded setups');
			return;
		}
		$files = $this->setup11Previews();
		$fileIds = array_map(function (File $f) {
			return $f->getId();
		}, $files);

		$root = $this->getRoot();

		$this->assertSame(11, $this->countPreviews($root, $fileIds));
		$job = new BackgroundCleanupJob($this->timeFactory, $this->connection, $root, $this->mimeTypeLoader, false);
		$job->run([]);

		foreach ($files as $file) {
			$file->delete();
		}

		$root = $this->getRoot();
		$this->assertSame(11, $this->countPreviews($root, $fileIds));
		$job->run([]);

		$root = $this->getRoot();
		$this->assertSame(1, $this->countPreviews($root, $fileIds));
		$job->run([]);

		$root = $this->getRoot();
		$this->assertSame(0, $this->countPreviews($root, $fileIds));
	}

	public function testOldPreviews(): void {
		if ($this->connection->getShardDefinition('filecache')) {
			$this->markTestSkipped('old previews are not supported for sharded setups');
			return;
		}
		$appdata = Server::get(IAppDataFactory::class)->get('preview');

		$f1 = $appdata->newFolder('123456781');
		$f1->newFile('foo.jpg', 'foo');
		$f2 = $appdata->newFolder('123456782');
		$f2->newFile('foo.jpg', 'foo');
		$f2 = $appdata->newFolder((string)PHP_INT_MAX - 1);
		$f2->newFile('foo.jpg', 'foo');

		/*
		 * Cleanup of OldPreviewLocations should only remove numeric folders on AppData level,
		 * therefore these files should stay untouched.
		 */
		$appdata->getFolder('/')->newFile('not-a-directory', 'foo');
		$appdata->getFolder('/')->newFile('133742', 'bar');

		$appdata = Server::get(IAppDataFactory::class)->get('preview');
		// AppData::getDirectoryListing filters all non-folders
		$this->assertSame(3, count($appdata->getDirectoryListing()));
		try {
			$appdata->getFolder('/')->getFile('not-a-directory');
		} catch (NotFoundException) {
			$this->fail('Could not find file \'not-a-directory\'');
		}
		try {
			$appdata->getFolder('/')->getFile('133742');
		} catch (NotFoundException) {
			$this->fail('Could not find file \'133742\'');
		}

		$job = new BackgroundCleanupJob($this->timeFactory, $this->connection, $this->getRoot(), $this->mimeTypeLoader, true);
		$job->run([]);

		$appdata = Server::get(IAppDataFactory::class)->get('preview');

		// Check if the files created above are still present
		// Remember: AppData::getDirectoryListing filters all non-folders
		$this->assertSame(0, count($appdata->getDirectoryListing()));
		try {
			$appdata->getFolder('/')->getFile('not-a-directory');
		} catch (NotFoundException) {
			$this->fail('Could not find file \'not-a-directory\'');
		}
		try {
			$appdata->getFolder('/')->getFile('133742');
		} catch (NotFoundException) {
			$this->fail('Could not find file \'133742\'');
		}
	}
}
