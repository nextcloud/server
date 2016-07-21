<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Files_Sharing\Tests;

/**
 * Class ShareTest
 *
 * @group DB
 */
class ShareTest extends TestCase {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	private static $tempStorage;

	protected function setUp() {
		parent::setUp();

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder  = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder.$this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
	}

	protected function tearDown() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->filename);
		$this->view->deleteAll($this->folder);

		self::$tempStorage = null;

		// clear database table
		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*share`');
		$query->execute();

		parent::tearDown();
	}

	public function testUnshareFromSelf() {

		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		$share1 = $this->share(
			\OCP\Share::SHARE_TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE
		);

		$share2 = $this->share(
			\OCP\Share::SHARE_TYPE_GROUP,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			'testGroup',
			\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		\OC\Files\Filesystem::unlink($this->filename);
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		// both group share and user share should be gone
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));

		// for user3 nothing should change
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	/**
	 * @param \OC\Files\FileInfo[] $content
	 * @param string[] $expected
	 */
	public function verifyDirContent($content, $expected) {
		foreach ($content as $c) {
			if (!in_array($c['name'], $expected)) {
				$this->assertTrue(false, "folder should only contain '" . implode(',', $expected) . "', found: " .$c['name']);
			}
		}
	}

	public function testShareWithDifferentShareFolder() {

		$fileinfo = $this->view->getFileInfo($this->filename);
		$folderinfo = $this->view->getFileInfo($this->folder);

		$share = $this->share(
			\OCP\Share::SHARE_TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE
		);

		\OCA\Files_Sharing\Helper::setShareFolder('/Shared/subfolder');

		$share = $this->share(
			\OCP\Share::SHARE_TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			\OCP\Constants::PERMISSION_ALL
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertTrue(\OC\Files\Filesystem::file_exists('/Shared/subfolder/' . $this->folder));

		//cleanup
		\OC::$server->getConfig()->deleteSystemValue('share_folder');
	}

	public function testShareWithGroupUniqueName() {
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OC\Files\Filesystem::file_put_contents('test.txt', 'test');

		$share = $this->share(
			\OCP\Share::SHARE_TYPE_GROUP,
			'test.txt',
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_GROUP1,
			\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, \OCP\Share::SHARE_TYPE_GROUP);
		$share = $shares[0];
		$this->assertSame('/test.txt' ,$share->getTarget());
		$this->assertSame(19, $share->getPermissions());
		
		\OC\Files\Filesystem::rename('test.txt', 'new test.txt');

		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, \OCP\Share::SHARE_TYPE_GROUP);
		$share = $shares[0];
		$this->assertSame('/new test.txt' ,$share->getTarget());
		$this->assertSame(19, $share->getPermissions());
		
		$share->setPermissions(\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE);
		$this->shareManager->updateShare($share);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, \OCP\Share::SHARE_TYPE_GROUP);
		$share = $shares[0];

		$this->assertSame('/new test.txt' ,$share->getTarget());
		$this->assertSame(3, $share->getPermissions());
	}

	/**
	 * shared files should never have delete permissions
	 * @dataProvider dataProviderTestFileSharePermissions
	 */
	public function testFileSharePermissions($permission, $expectedvalid) {

		$pass = true;
		try {
			$this->share(
				\OCP\Share::SHARE_TYPE_USER,
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

	public function dataProviderTestFileSharePermissions() {
		$permission1 = \OCP\Constants::PERMISSION_ALL;
		$permission3 = \OCP\Constants::PERMISSION_READ;
		$permission4 = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE;
		$permission5 = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_DELETE;
		$permission6 = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE;

		return array(
			array($permission1, false),
			array($permission3, true),
			array($permission4, true),
			array($permission5, false),
			array($permission6, false),
		);
	}

	public function testFileOwner() {

		$this->share(
			\OCP\Share::SHARE_TYPE_USER,
			$this->filename,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			\OCP\Constants::PERMISSION_READ
		);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$info = \OC\Files\Filesystem::getFileInfo($this->filename);

		$this->assertSame(self::TEST_FILES_SHARING_API_USER1, $info->getOwner()->getUID());
	}
}
