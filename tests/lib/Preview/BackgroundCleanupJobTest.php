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

use OC\Files\AppData\Factory;
use OC\Preview\BackgroundCleanupJob;
use OC\PreviewManager;
use OC\SystemConfig;
use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCP\Files\Folder;
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

	/** @var Factory */
	private $appDataFactory;

	/** @var IDBConnection */
	private $connection;

	/** @var PreviewManager */
	private $previewManager;

	/** @var IRootFolder */
	private $rootFolder;

	public function setUp() {
		parent::setUp();

		$this->userId = $this->getUniqueID();
		$this->createUser($this->userId, $this->userId);

		$storage = new \OC\Files\Storage\Temporary([]);
		$this->registerMount($this->userId, $storage, '');

		$this->loginAsUser($this->userId);
		$this->logout();
		$this->loginAsUser($this->userId);

		$appManager = \OC::$server->getAppManager();
		$this->trashEnabled = $appManager->isEnabledForUser('files_trashbin', $this->userId);
		$appManager->disableApp('files_trashbin');

		$this->appDataFactory = new Factory(
			\OC::$server->getRootFolder(),
			\OC::$server->getSystemConfig()
		);
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->previewManager = \OC::$server->getPreviewManager();
		$this->rootFolder = \OC::$server->getRootFolder();
	}

	public function tearDown() {
		if ($this->trashEnabled) {
			$appManager = \OC::$server->getAppManager();
			$appManager->enableApp('files_trashbin');
		}

		$this->logout();

		return parent::tearDown();
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

	private function countPreviews() {
		$root = \OC::$server->getRootFolder();
		$config = \OC::$server->getSystemConfig();
		$preview = $root->get('appdata_' . $config->getValue('instanceid') . '/preview');

		$folders = $preview->getDirectoryListing();
		$num_previews = 0;

		while (!empty($folders)) {
			$curr_node = array_pop($folders);

			$new_nodes = $curr_node->getDirectoryListing();
			foreach ($new_nodes as $new_node) {
				if ($new_node instanceof Folder) {
					$folders[] = $new_node;
				} else {
					$num_previews++;
					break;
				}
			}
		}

		return $num_previews;
	}

	public function testCleanupSystemCron() {
		$files = $this->setup11Previews();

		$previews = $this->countPreviews();
		$this->assertEquals(11, $previews);

		$job = new BackgroundCleanupJob($this->connection, $this->appDataFactory, true);
		$job->run([]);

		foreach ($files as $file) {
			$file->delete();
		}

		$this->assertEquals(11, $previews);
		$job->run([]);

		$previews = $this->countPreviews();
		$this->assertEquals(0, $previews);
	}

	public function testCleanupAjax() {
		$files = $this->setup11Previews();

		$previews = $this->countPreviews();
		$this->assertEquals(11, $previews);

		$job = new BackgroundCleanupJob($this->connection, $this->appDataFactory, false);
		$job->run([]);

		foreach ($files as $file) {
			$file->delete();
		}

		$this->assertEquals(11, $previews);
		$job->run([]);

		$previews = $this->countPreviews();
		$this->assertEquals(1, $previews);

		$job->run([]);

		$previews = $this->countPreviews();
		$this->assertEquals(0, $previews);
	}
}
