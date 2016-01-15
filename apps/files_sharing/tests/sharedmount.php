<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

/**
 * Class Test_Files_Sharing_Api
 *
 * @group DB
 */
class Test_Files_Sharing_Mount extends OCA\Files_sharing\Tests\TestCase {

	protected function setUp() {
		parent::setUp();

		$this->folder = '/folder_share_storage_test';

		$this->filename = '/share-api-storage.txt';


		$this->view->mkdir($this->folder);

		// save file with content
		$this->view->file_put_contents($this->filename, "root file");
		$this->view->file_put_contents($this->folder . $this->filename, "file in subfolder");
	}

	protected function tearDown() {
		if ($this->view) {
			$this->view->unlink($this->folder);
			$this->view->unlink($this->filename);
		}

		parent::tearDown();
	}

	/**
	 * test if the mount point moves up if the parent folder no longer exists
	 */
	function testShareMountLoseParentFolder() {

		// share to user
		$fileinfo = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		$statement = "UPDATE `*PREFIX*share` SET `file_target` = ? where `share_with` = ?";
		$query = \OCP\DB::prepare($statement);
		$arguments = array('/foo/bar' . $this->folder, self::TEST_FILES_SHARING_API_USER2);
		$query->execute($arguments);

		$query = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $query->execute();

		$shares = $result->fetchAll();

		$this->assertSame(1, count($shares));

		$share = reset($shares);
		$this->assertSame('/foo/bar' . $this->folder, $share['file_target']);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// share should have moved up

		$query = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $query->execute();

		$shares = $result->fetchAll();

		$this->assertSame(1, count($shares));

		$share = reset($shares);
		$this->assertSame($this->folder, $share['file_target']);

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$this->view->unlink($this->folder);
	}

	/**
	 * @medium
	 */
	function testDeleteParentOfMountPoint() {

		// share to user
		$fileinfo = $this->view->getFileInfo($this->folder);
		$result = \OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$user2View = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$this->assertTrue($user2View->file_exists($this->folder));

		// create a local folder
		$result = $user2View->mkdir('localfolder');
		$this->assertTrue($result);

		// move mount point to local folder
		$result = $user2View->rename($this->folder, '/localfolder/' . $this->folder);
		$this->assertTrue($result);

		// mount point in the root folder should no longer exist
		$this->assertFalse($user2View->is_dir($this->folder));

		// delete the local folder
		$result = $user2View->unlink('/localfolder');
		$this->assertTrue($result);

		//enforce reload of the mount points
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		//mount point should be back at the root
		$this->assertTrue($user2View->is_dir($this->folder));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->view->unlink($this->folder);
	}

	function testMoveSharedFile() {
		$fileinfo = $this->view->getFileInfo($this->filename);
		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			self::TEST_FILES_SHARING_API_USER2, 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		\OC\Files\Filesystem::rename($this->filename, $this->filename . '_renamed');

		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename . '_renamed'));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename . '_renamed'));

		// rename back to original name
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		\OC\Files\Filesystem::rename($this->filename . '_renamed', $this->filename);
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename . '_renamed'));
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		//cleanup
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
	}

	/**
	 * share file with a group if a user renames the file the filename should not change
	 * for the other users
	 */
	function testMoveGroupShare () {
		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		$fileinfo = $this->view->getFileInfo($this->filename);
		$result = \OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,
			"testGroup", 31);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		\OC\Files\Filesystem::rename($this->filename, "newFileName");

		$this->assertTrue(\OC\Files\Filesystem::file_exists('newFileName'));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->filename));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists("newFileName"));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));
		$this->assertFalse(\OC\Files\Filesystem::file_exists("newFileName"));

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');
	}

	/**
	 * @dataProvider dataProviderTestStripUserFilesPath
	 * @param string $path
	 * @param string $expectedResult
	 * @param bool $exception if a exception is expected
	 */
	function testStripUserFilesPath($path, $expectedResult, $exception) {
		$testClass = new DummyTestClassSharedMount(null, null);
		try {
			$result = $testClass->stripUserFilesPathDummy($path);
			$this->assertSame($expectedResult, $result);
		} catch (\Exception $e) {
			if ($exception) {
				$this->assertSame(10, $e->getCode());
			} else {
				$this->assertTrue(false, "Exception catched, but expected: " . $expectedResult);
			}
		}
	}

	function dataProviderTestStripUserFilesPath() {
		return array(
			array('/user/files/foo.txt', '/foo.txt', false),
			array('/user/files/folder/foo.txt', '/folder/foo.txt', false),
			array('/data/user/files/foo.txt', null, true),
			array('/data/user/files/', null, true),
			array('/files/foo.txt', null, true),
			array('/foo.txt', null, true),
		);
	}

	function dataPermissionMovedGroupShare() {
		$data = [];

		$powerset = function($permissions) {
			$results = [\OCP\Constants::PERMISSION_READ];

			foreach ($permissions as $permission) {
				foreach ($results as $combination) {
					$results[] = $permission | $combination;
				}
			}
			return $results;
		};

		//Generate file permissions
		$permissions = [
			\OCP\Constants::PERMISSION_UPDATE,
			\OCP\Constants::PERMISSION_CREATE,
			\OCP\Constants::PERMISSION_SHARE,
		];

		$allPermissions = $powerset($permissions);

		foreach ($allPermissions as $before) {
			foreach ($allPermissions as $after) {
				if ($before === $after) { continue; }

				$data[] = [
					'file', 
					$before,
					$after,
				];
			}
		}

		//Generate folder permissions
		$permissions = [
			\OCP\Constants::PERMISSION_UPDATE,
			\OCP\Constants::PERMISSION_CREATE,
			\OCP\Constants::PERMISSION_SHARE,
			\OCP\Constants::PERMISSION_DELETE,
		];

		$allPermissions = $powerset($permissions);

		foreach ($allPermissions as $before) {
			foreach ($allPermissions as $after) {
				if ($before === $after) { continue; }

				$data[] = [
					'folder',
					$before,
					$after,
				];
			}
		}

		return $data;
	}



	/**
	 * moved mountpoints of a group share should keep the same permission as their parent group share.
	 * See #15253
	 *
	 * @dataProvider dataPermissionMovedGroupShare
	 */
	function testPermissionMovedGroupShare($type, $beforePerm, $afterPerm) {

		if ($type === 'file') {
			$path = $this->filename;
		} else if ($type === 'folder') {
			$path = $this->folder;
		}

		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		// Share item with group
		$fileinfo = $this->view->getFileInfo($path);
		$this->assertTrue(
			\OCP\Share::shareItem($type, $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,	"testGroup", $beforePerm)
		);

		// Login as user 2 and verify the item exists
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($path));
		$result = \OCP\Share::getItemSharedWithBySource($type, $fileinfo['fileid']);
		$this->assertNotEmpty($result);
		$this->assertEquals($beforePerm, $result['permissions']);

		// Now move the item forcing a new entry in the share table
		\OC\Files\Filesystem::rename($path, "newPath");
		$this->assertTrue(\OC\Files\Filesystem::file_exists('newPath'));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($path));

		// Login as user 1 again and change permissions
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue(
			\OCP\Share::setPermissions($type, $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, "testGroup", $afterPerm)
		);

		// Login as user 3 and verify that the permissions are changed
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);
		$result = \OCP\Share::getItemSharedWithBySource($type, $fileinfo['fileid']);
		$this->assertNotEmpty($result);
		$this->assertEquals($afterPerm, $result['permissions']);
		$groupShareId = $result['id'];

		// Login as user 2 and verify that the permissions are changed
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$result = \OCP\Share::getItemSharedWithBySource($type, $fileinfo['fileid']);
		$this->assertNotEmpty($result);
		$this->assertEquals($afterPerm, $result['permissions']);
		$this->assertNotEquals($groupShareId, $result['id']);

		// Also verify in the DB
		$statement = "SELECT `permissions` FROM `*PREFIX*share` WHERE `id`=?";
		$query = \OCP\DB::prepare($statement);
		$result = $query->execute([$result['id']]);
		$shares = $result->fetchAll();
		$this->assertCount(1, $shares);
		$this->assertEquals($afterPerm, $shares[0]['permissions']);

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare($type, $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');
	}

	/**
	 * If the permissions on a group share are upgraded be sure to still respect 
	 * removed shares by a member of that group
	 */
	function testPermissionUpgradeOnUserDeletedGroupShare() {
		\OC_Group::createGroup('testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::addToGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');

		$connection = \OC::$server->getDatabaseConnection();

		// Share item with group
		$fileinfo = $this->view->getFileInfo($this->folder);
		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP,	"testGroup", \OCP\Constants::PERMISSION_READ)
		);

		// Login as user 2 and verify the item exists
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->folder));
		$result = \OCP\Share::getItemSharedWithBySource('folder', $fileinfo['fileid']);
		$this->assertNotEmpty($result);
		$this->assertEquals(\OCP\Constants::PERMISSION_READ, $result['permissions']);

		// Delete the share
		$this->assertTrue(\OC\Files\Filesystem::rmdir($this->folder));
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->folder));

		// Verify we do not get a share
		$result = \OCP\Share::getItemSharedWithBySource('folder', $fileinfo['fileid']);
		$this->assertEmpty($result);

		// Verify that the permission is correct in the DB
		$qb = $connection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('file_source', $qb->createParameter('fileSource')))
			->andWhere($qb->expr()->eq('share_type', $qb->createParameter('shareType')))
			->setParameter(':fileSource', $fileinfo['fileid'])
			->setParameter(':shareType', 2);
		$res = $qb->execute()->fetchAll();

		$this->assertCount(1, $res);
		$this->assertEquals(0, $res[0]['permissions']);

		// Login as user 1 again and change permissions
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue(
			\OCP\Share::setPermissions('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, "testGroup", \OCP\Constants::PERMISSION_ALL)
		);

		// Login as user 2 and verify 
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->assertFalse(\OC\Files\Filesystem::file_exists($this->folder));
		$result = \OCP\Share::getItemSharedWithBySource('folder', $fileinfo['fileid']);
		$this->assertEmpty($result);

		$connection = \OC::$server->getDatabaseConnection();
		$qb = $connection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('file_source', $qb->createParameter('fileSource')))
			->andWhere($qb->expr()->eq('share_type', $qb->createParameter('shareType')))
			->setParameter(':fileSource', $fileinfo['fileid'])
			->setParameter(':shareType', 2);
		$res = $qb->execute()->fetchAll();

		$this->assertCount(1, $res);
		$this->assertEquals(0, $res[0]['permissions']);

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER1, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER2, 'testGroup');
		\OC_Group::removeFromGroup(self::TEST_FILES_SHARING_API_USER3, 'testGroup');
	}

}

class DummyTestClassSharedMount extends \OCA\Files_Sharing\SharedMount {
	public function __construct($storage, $mountpoint, $arguments = null, $loader = null){
		// noop
	}

	public function stripUserFilesPathDummy($path) {
		return $this->stripUserFilesPath($path);
	}
}
