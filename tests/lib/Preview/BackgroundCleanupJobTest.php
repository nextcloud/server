<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Files\Storage\Temporary;
use OC\Preview\BackgroundCleanupJob;
use OC\Preview\PreviewService;
use OC\PreviewManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\Server;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class BackgroundCleanupJobTest
 *
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
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
	private PreviewService $previewService;

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
		$this->previewService = Server::get(PreviewService::class);
	}

	protected function tearDown(): void {
		if ($this->trashEnabled) {
			$appManager = Server::get(IAppManager::class);
			$appManager->enableApp('files_trashbin');
		}

		$this->logout();

		foreach ($this->previewService->getAvailablePreviewsForFile(5) as $preview) {
			$this->previewService->deletePreview($preview);
		}

		parent::tearDown();
	}

	private function setup11Previews(): array {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		$files = [];
		foreach (range(0, 10) as $i) {
			$file = $userFolder->newFile($i . '.txt');
			$file->putContent('hello world!');
			$this->previewManager->getPreview($file);
			$files[] = $file;
		}

		return $files;
	}

	private function countPreviews(PreviewService $previewService, array $fileIds): int {
		$previews = $previewService->getAvailablePreviews($fileIds);
		return array_reduce($previews, fn (int $result, array $previews) => $result + count($previews), 0);
	}

	public function testCleanupSystemCron(): void {
		$files = $this->setup11Previews();
		$fileIds = array_map(fn (File $f): int => $f->getId(), $files);

		$this->assertSame(11, $this->countPreviews($this->previewService, $fileIds));
		$job = new BackgroundCleanupJob($this->timeFactory, $this->connection, $this->previewService, true);
		$job->run([]);

		foreach ($files as $file) {
			$file->delete();
		}

		$this->assertSame(11, $this->countPreviews($this->previewService, $fileIds));
		$job->run([]);

		$this->assertSame(0, $this->countPreviews($this->previewService, $fileIds));
	}

	public function testCleanupAjax(): void {
		if ($this->connection->getShardDefinition('filecache')) {
			$this->markTestSkipped('ajax cron is not supported for sharded setups');
		}
		$files = $this->setup11Previews();
		$fileIds = array_map(fn (File $f): int => $f->getId(), $files);

		$this->assertSame(11, $this->countPreviews($this->previewService, $fileIds));
		$job = new BackgroundCleanupJob($this->timeFactory, $this->connection, $this->previewService, false);
		$job->run([]);

		foreach ($files as $file) {
			$file->delete();
		}

		$this->assertSame(11, $this->countPreviews($this->previewService, $fileIds));
		$job->run([]);

		$this->assertSame(1, $this->countPreviews($this->previewService, $fileIds));
		$job->run([]);

		$this->assertSame(0, $this->countPreviews($this->previewService, $fileIds));
	}
}
