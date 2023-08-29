<?php
/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Preview;

use OC\Preview\BackgroundCleanupJob;
use OC\Preview\Storage\Root;
use OC\PreviewManager;
use OC\SystemConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
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

	/** @var string */
	private $userId;

	/** @var bool */
	private $trashEnabled;

	/** @var IDBConnection */
	private $connection;

	/** @var PreviewManager */
	private $previewManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IMimeTypeLoader */
	private $mimeTypeLoader;

	private ITimeFactory $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->userId = $this->getUniqueID();
		$user = $this->createUser($this->userId, $this->userId);

		$storage = new \OC\Files\Storage\Temporary([]);
		$this->registerMount($this->userId, $storage, '');

		$this->loginAsUser($this->userId);
		$this->logout();
		$this->loginAsUser($this->userId);

		$appManager = \OC::$server->getAppManager();
		$this->trashEnabled = $appManager->isEnabledForUser('files_trashbin', $user);
		$appManager->disableApp('files_trashbin');

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->previewManager = \OC::$server->getPreviewManager();
		$this->rootFolder = \OC::$server->getRootFolder();
		$this->mimeTypeLoader = \OC::$server->getMimeTypeLoader();
		$this->timeFactory = \OCP\Server::get(ITimeFactory::class);
	}

	protected function tearDown(): void {
		if ($this->trashEnabled) {
			$appManager = \OC::$server->getAppManager();
			$appManager->enableApp('files_trashbin');
		}

		$this->logout();

		parent::tearDown();
	}

	private function getRoot(): Root {
		return new Root(
			\OC::$server->getRootFolder(),
			\OC::$server->get(SystemConfig::class)
		);
	}

	private function setup11Previews(): array {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		$files = [];
		for ($i = 0; $i < 11; $i++) {
			$file = $userFolder->newFile($i.'.txt');
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

	public function testCleanupSystemCron() {
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

	public function testCleanupAjax() {
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

	public function testOldPreviews() {
		$appdata = \OC::$server->getAppDataDir('preview');

		$f1 = $appdata->newFolder('123456781');
		$f1->newFile('foo.jpg', 'foo');
		$f2 = $appdata->newFolder('123456782');
		$f2->newFile('foo.jpg', 'foo');

		$appdata = \OC::$server->getAppDataDir('preview');
		$this->assertSame(2, count($appdata->getDirectoryListing()));

		$job = new BackgroundCleanupJob($this->timeFactory, $this->connection, $this->getRoot(), $this->mimeTypeLoader, true);
		$job->run([]);

		$appdata = \OC::$server->getAppDataDir('preview');
		$this->assertSame(0, count($appdata->getDirectoryListing()));
	}
}
