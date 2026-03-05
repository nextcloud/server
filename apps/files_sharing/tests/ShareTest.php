<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\FileInfo;
use OC\Files\Filesystem;
use OCA\Files_Sharing\Helper;
use OCP\Constants;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class ShareTest
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ShareTest extends TestCase {
	public const TEST_FOLDER_NAME = '/folder_share_api_test';

	private static $tempStorage;

	private string $subsubfolder = '';

	protected function setUp(): void {
		parent::setUp();

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder . $this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
	}

	protected function tearDown(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->filename);
		$this->view->deleteAll($this->folder);

		self::$tempStorage = null;

		parent::tearDown();
	}

	public function testUnshareFromSelf(): void {
		$groupManager = Server::get(IGroupManager::class);
		$userManager = Server::get(IUserManager::class);

		$testGroup = $groupManager->createGroup('testGroup');
		$user1 = $userManager->get(self::TEST_FILES_SHARING_API_USER2);
		$user2 = $userManager->get(self::TEST_FILES_SHARING_API_USER3);
		$testGroup->addUser($user1);
		$testGroup->addUser($user2);

		$share1 = $this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		$share2 = $this->share(
			IShare::TYPE_GROUP,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			'testGroup',
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);
		$this->shareManager->acceptShare($share2, self::TEST_FILES_SHARING_API_USER2);
		$this->shareManager->acceptShare($share2, self::TEST_FILES_SHARING_API_USER3);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::unlink($this->filename);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		// both group share and user share should be gone
		$this->assertFalse(Filesystem::file_exists($this->filename));

		// for user3 nothing should change
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(Filesystem::file_exists($this->filename));

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	/**
	 * @param FileInfo[] $content
	 * @param string[] $expected
	 */
	public function verifyDirContent($content, $expected) {
		foreach ($content as $c) {
			if (!in_array($c['name'], $expected)) {
				$this->assertTrue(false, "folder should only contain '" . implode(',', $expected) . "', found: " . $c['name']);
			}
		}
	}

	public function testShareWithDifferentShareFolder(): void {
		$fileinfo = $this->view->getFileInfo($this->filename);
		$folderinfo = $this->view->getFileInfo($this->folder);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		Helper::setShareFolder('/Shared/subfolder');

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(Filesystem::file_exists($this->filename));
		$this->assertTrue(Filesystem::file_exists('/Shared/subfolder/' . $this->folder));

		//cleanup
		Server::get(IConfig::class)->deleteSystemValue('share_folder');
	}

	public function testShareWithGroupUniqueName(): void {
		$this->markTestSkipped('TODO: Disable because fails on drone');

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('test.txt', 'test');

		$share = $this->share(
			IShare::TYPE_GROUP,
			'test.txt',
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_GROUP1,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_GROUP);
		$share = $shares[0];
		$this->assertSame('/test.txt', $share->getTarget());
		$this->assertSame(19, $share->getPermissions());

		Filesystem::rename('test.txt', 'new test.txt');

		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_GROUP);
		$share = $shares[0];
		$this->assertSame('/new test.txt', $share->getTarget());
		$this->assertSame(19, $share->getPermissions());

		$share->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);
		$this->shareManager->updateShare($share);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_GROUP);
		$share = $shares[0];

		$this->assertSame('/new test.txt', $share->getTarget());
		$this->assertSame(3, $share->getPermissions());
	}

	/**
	 * shared files should never have delete permissions
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataProviderTestFileSharePermissions')]
	public function testFileSharePermissions($permission, $expectedvalid): void {
		$pass = true;
		try {
			$this->share(
				IShare::TYPE_USER,
				$this->filename,
				self::TEST_FILES_SHARING_API_USER1,
				self::TEST_FILES_SHARING_API_USER2,
				$permission
			);
		} catch (\Exception $e) {
			$pass = false;
		}

		$this->assertEquals($expectedvalid, $pass);
	}

	public static function dataProviderTestFileSharePermissions() {
		$permission1 = Constants::PERMISSION_ALL;
		$permission3 = Constants::PERMISSION_READ;
		$permission4 = Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE;
		$permission5 = Constants::PERMISSION_READ | Constants::PERMISSION_DELETE;
		$permission6 = Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE;

		return [
			[$permission1, false],
			[$permission3, true],
			[$permission4, true],
			[$permission5, false],
			[$permission6, false],
		];
	}

	public function testFileOwner(): void {
		$this->share(
			IShare::TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_READ
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$info = Filesystem::getFileInfo($this->filename);

		$this->assertSame(self::TEST_FILES_SHARING_API_USER1, $info->getOwner()->getUID());
	}
}
