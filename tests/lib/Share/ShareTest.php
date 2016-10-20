<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Test\Share;

/**
 * Class Test_Share
 *
 * @group DB
 */
class ShareTest extends \Test\TestCase {

	protected $itemType;
	protected $userBackend;
	protected $user1;
	protected $user2;
	protected $user3;
	protected $user4;
	protected $user5;
	protected $user6;
	protected $groupAndUser;
	protected $groupBackend;
	protected $group1;
	protected $group2;
	protected $resharing;
	protected $dateInFuture;
	protected $dateInPast;

	protected function setUp() {
		parent::setUp();

		\OC_User::clearBackends();
		\OC_User::useBackend('dummy');
		$this->user1 = $this->getUniqueID('user1_');
		$this->user2 = $this->getUniqueID('user2_');
		$this->user3 = $this->getUniqueID('user3_');
		$this->user4 = $this->getUniqueID('user4_');
		$this->user5 = $this->getUniqueID('user5_');
		$this->user6 = $this->getUniqueID('user6_');
		$this->groupAndUser = $this->getUniqueID('groupAndUser_');
		\OC::$server->getUserManager()->createUser($this->user1, 'pass');
		\OC::$server->getUserManager()->createUser($this->user2, 'pass');
		\OC::$server->getUserManager()->createUser($this->user3, 'pass');
		\OC::$server->getUserManager()->createUser($this->user4, 'pass');
		\OC::$server->getUserManager()->createUser($this->user5, 'pass');
		\OC::$server->getUserManager()->createUser($this->user6, 'pass'); // no group
		\OC::$server->getUserManager()->createUser($this->groupAndUser, 'pass');
		\OC_User::setUserId($this->user1);
		\OC_Group::clearBackends();
		\OC_Group::useBackend(new \Test\Util\Group\Dummy());
		$this->group1 = $this->getUniqueID('group1_');
		$this->group2 = $this->getUniqueID('group2_');
		\OC_Group::createGroup($this->group1);
		\OC_Group::createGroup($this->group2);
		\OC_Group::createGroup($this->groupAndUser);
		\OC_Group::addToGroup($this->user1, $this->group1);
		\OC_Group::addToGroup($this->user2, $this->group1);
		\OC_Group::addToGroup($this->user3, $this->group1);
		\OC_Group::addToGroup($this->user2, $this->group2);
		\OC_Group::addToGroup($this->user4, $this->group2);
		\OC_Group::addToGroup($this->user2, $this->groupAndUser);
		\OC_Group::addToGroup($this->user3, $this->groupAndUser);
		\OCP\Share::registerBackend('test', 'Test\Share\Backend');
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();
		$this->resharing = \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_resharing', 'yes');
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_allow_resharing', 'yes');

		// 20 Minutes in the past, 20 minutes in the future.
		$now = time();
		$dateFormat = 'Y-m-d H:i:s';
		$this->dateInPast = date($dateFormat, $now - 20 * 60);
		$this->dateInFuture = date($dateFormat, $now + 20 * 60);
	}

	protected function tearDown() {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `item_type` = ?');
		$query->execute(array('test'));
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_allow_resharing', $this->resharing);

		$user = \OC::$server->getUserManager()->get($this->user1);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get($this->user2);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get($this->user3);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get($this->user4);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get($this->user5);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get($this->user6);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get($this->groupAndUser);
		if ($user !== null) { $user->delete(); }

		\OC_Group::deleteGroup($this->group1);
		\OC_Group::deleteGroup($this->group2);
		\OC_Group::deleteGroup($this->groupAndUser);

		$this->logout();
		parent::tearDown();
	}

	public function testShareInvalidShareType() {
		$message = 'Share type foobar is not valid for test.txt';
		try {
			\OCP\Share::shareItem('test', 'test.txt', 'foobar', $this->user2, \OCP\Constants::PERMISSION_READ);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
	}

	public function testInvalidItemType() {
		$message = 'Sharing backend for foobar not found';
		try {
			\OCP\Share::shareItem('foobar', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			\OCP\Share::getItemsSharedWith('foobar');
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			\OCP\Share::getItemSharedWith('foobar', 'test.txt');
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			\OCP\Share::getItemSharedWithBySource('foobar', 'test.txt');
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			\OCP\Share::getItemShared('foobar', 'test.txt');
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			\OCP\Share::unshare('foobar', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			\OCP\Share::setPermissions('foobar', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_UPDATE);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
	}

	protected function shareUserOneTestFileWithUserTwo() {
		\OC_User::setUserId($this->user1);
		$this->assertTrue(
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared text.txt with user 2.'
		);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemShared('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of user 1.'
		);

		\OC_User::setUserId($this->user2);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that user 2 has access to test.txt after initial sharing.'
		);
	}

	protected function shareUserTestFileAsLink() {
		\OC_User::setUserId($this->user1);
		$result = \OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
		$this->assertTrue(is_string($result));
	}

	/**
	 * @param string $sharer
	 * @param string $receiver
	 */
	protected function shareUserTestFileWithUser($sharer, $receiver) {
		\OC_User::setUserId($sharer);
		$this->assertTrue(
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $receiver, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE),
			'Failed asserting that ' . $sharer . ' successfully shared text.txt with ' . $receiver . '.'
		);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemShared('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of ' . $sharer . '.'
		);

		\OC_User::setUserId($receiver);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that ' . $receiver . ' has access to test.txt after initial sharing.'
		);
	}

	public function testShareWithUser() {
		// Invalid shares
		$message = 'Sharing test.txt failed, because you can not share with yourself';
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user1, \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		$message = 'Sharing test.txt failed, because the user foobar does not exist';
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, 'foobar', \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		$message = 'Sharing foobar failed, because the sharing backend for test could not find its source';
		try {
			\OCP\Share::shareItem('test', 'foobar', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Valid share
		$this->shareUserOneTestFileWithUserTwo();

		// Attempt to share again
		\OC_User::setUserId($this->user1);
		$message = 'Sharing test.txt failed, because this item is already shared with '.$this->user2;
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Attempt to share back
		\OC_User::setUserId($this->user2);
		$message = 'Sharing failed, because the user '.$this->user1.' is the original sharer';
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user1, \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Unshare
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::unshare('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2));

		// Attempt reshare without share permission
		$this->assertTrue(\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ));
		\OC_User::setUserId($this->user2);
		$message = 'Sharing test.txt failed, because resharing is not allowed';
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user3, \OCP\Constants::PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Owner grants share and update permission
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::setPermissions('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE));

		// Attempt reshare with escalated permissions
		\OC_User::setUserId($this->user2);
		$message = 'Sharing test.txt failed, because the permissions exceed permissions granted to '.$this->user2;
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user3, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_DELETE);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Valid reshare
		$this->assertTrue(\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user3, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE));
		$this->assertEquals(array('test.txt'), \OCP\Share::getItemShared('test', 'test.txt', Backend::FORMAT_SOURCE));
		\OC_User::setUserId($this->user3);
		$this->assertEquals(array('test.txt'), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE));
		$this->assertEquals(array(\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_PERMISSIONS));

		// Attempt to escalate permissions
		\OC_User::setUserId($this->user2);
		$message = 'Setting permissions for test.txt failed, because the permissions exceed permissions granted to '.$this->user2;
		try {
			\OCP\Share::setPermissions('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user3, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_DELETE);
			$this->fail('Exception was expected: '.$message);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Remove update permission
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::setPermissions('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE));
		\OC_User::setUserId($this->user2);
		$this->assertEquals(array(\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_PERMISSIONS));
		\OC_User::setUserId($this->user3);
		$this->assertEquals(array(\OCP\Constants::PERMISSION_READ), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_PERMISSIONS));

		// Remove share permission
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::setPermissions('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ));
		\OC_User::setUserId($this->user2);
		$this->assertEquals(array(\OCP\Constants::PERMISSION_READ), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_PERMISSIONS));
		\OC_User::setUserId($this->user3);
		$this->assertSame(array(), \OCP\Share::getItemSharedWith('test', 'test.txt'));

		// Reshare again, and then have owner unshare
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::setPermissions('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE));
		\OC_User::setUserId($this->user2);
		$this->assertTrue(\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user3, \OCP\Constants::PERMISSION_READ));
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::unshare('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2));
		\OC_User::setUserId($this->user2);
		$this->assertSame(array(), \OCP\Share::getItemSharedWith('test', 'test.txt'));
		\OC_User::setUserId($this->user3);
		$this->assertSame(array(), \OCP\Share::getItemSharedWith('test', 'test.txt'));

		// Attempt target conflict
		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ));
		\OC_User::setUserId($this->user3);
		$this->assertTrue(\OCP\Share::shareItem('test', 'share.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ));

		\OC_User::setUserId($this->user2);
		$to_test = \OCP\Share::getItemsSharedWith('test', Backend::FORMAT_TARGET);
		$this->assertEquals(2, count($to_test));
		$this->assertTrue(in_array('test.txt', $to_test));
		$this->assertTrue(in_array('test1.txt', $to_test));

		// Unshare from self
		$this->assertTrue(\OCP\Share::unshareFromSelf('test', 'test.txt'));
		$this->assertEquals(array('test1.txt'), \OCP\Share::getItemsSharedWith('test', Backend::FORMAT_TARGET));

		// Unshare from self via source
		$this->assertTrue(\OCP\Share::unshareFromSelf('test', 'share.txt', true));
		$this->assertEquals(array(), \OCP\Share::getItemsSharedWith('test', Backend::FORMAT_TARGET));

		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ));
		\OC_User::setUserId($this->user3);
		$this->assertTrue(\OCP\Share::shareItem('test', 'share.txt', \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ));

		\OC_User::setUserId($this->user2);
		$to_test = \OCP\Share::getItemsSharedWith('test', Backend::FORMAT_TARGET);
		$this->assertEquals(2, count($to_test));
		$this->assertTrue(in_array('test.txt', $to_test));
		$this->assertTrue(in_array('test1.txt', $to_test));

		// Remove user
		\OC_User::setUserId($this->user1);
		$user = \OC::$server->getUserManager()->get($this->user1);
		if ($user !== null) { $user->delete(); }
		\OC_User::setUserId($this->user2);
		$this->assertEquals(array('test1.txt'), \OCP\Share::getItemsSharedWith('test', Backend::FORMAT_TARGET));
	}

	public function testShareWithUserExpirationExpired() {
		\OC_User::setUserId($this->user1);
		$this->shareUserOneTestFileWithUserTwo();
		$this->shareUserTestFileAsLink();

		// manipulate share table and set expire date to the past
		$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `expiration` = ? WHERE `item_type` = ? AND `item_source` = ?  AND `uid_owner` = ? AND `share_type` = ?');
		$query->bindValue(1, new \DateTime($this->dateInPast), 'datetime');
		$query->bindValue(2, 'test');
		$query->bindValue(3, 'test.txt');
		$query->bindValue(4, $this->user1);
		$query->bindValue(5, \OCP\Share::SHARE_TYPE_LINK);
		$query->execute();

		$shares = \OCP\Share::getItemsShared('test');
		$this->assertSame(1, count($shares));
		$share = reset($shares);
		$this->assertSame(\OCP\Share::SHARE_TYPE_USER, $share['share_type']);
	}

	public function testGetShareFromOutsideFilesFolder() {
		\OC_User::setUserId($this->user1);
		$view = new \OC\Files\View('/' . $this->user1 . '/');
		$view->mkdir('files/test');
		$view->mkdir('files/test/sub');

		$view->mkdir('files_trashbin');
		$view->mkdir('files_trashbin/files');

		$fileInfo = $view->getFileInfo('files/test/sub');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared "test/sub" with user 2.'
		);

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertNotEmpty($result);

		$result = \OCP\Share::getItemSharedWithUser('folder', $fileId, $this->user2);
		$this->assertNotEmpty($result);

		$result = \OCP\Share::getItemsSharedWithUser('folder', $this->user2);
		$this->assertNotEmpty($result);

		// move to trash (keeps file id)
		$view->rename('files/test', 'files_trashbin/files/test');

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertEmpty($result, 'Share must not be returned for files outside of "files"');

		$result = \OCP\Share::getItemSharedWithUser('folder', $fileId, $this->user2);
		$this->assertEmpty($result, 'Share must not be returned for files outside of "files"');

		$result = \OCP\Share::getItemsSharedWithUser('folder', $this->user2);
		$this->assertEmpty($result, 'Share must not be returned for files outside of "files"');
	}

	public function testSetExpireDateInPast() {
		\OC_User::setUserId($this->user1);
		$this->shareUserOneTestFileWithUserTwo();
		$this->shareUserTestFileAsLink();

		$setExpireDateFailed = false;
		try {
			$this->assertTrue(
					\OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInPast, ''),
					'Failed asserting that user 1 successfully set an expiration date for the test.txt share.'
			);
		} catch (\Exception $e) {
			$setExpireDateFailed = true;
		}

		$this->assertTrue($setExpireDateFailed);
	}

	public function testShareWithUserExpirationValid() {
		\OC_User::setUserId($this->user1);
		$this->shareUserOneTestFileWithUserTwo();
		$this->shareUserTestFileAsLink();


		$this->assertTrue(
			\OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInFuture, ''),
			'Failed asserting that user 1 successfully set an expiration date for the test.txt share.'
		);

		$shares = \OCP\Share::getItemsShared('test');
		$this->assertSame(2, count($shares));

	}

	/*
	 * if user is in a group excluded from resharing, then the share permission should
	 * be removed
	 */
	public function testShareWithUserAndUserIsExcludedFromResharing() {

		\OC_User::setUserId($this->user1);
		$this->assertTrue(
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_USER, $this->user4, \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared text.txt with user 4.'
		);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemShared('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of user 1.'
		);

		// exclude group2 from sharing
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups_list', $this->group2);
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups', "yes");

		\OC_User::setUserId($this->user4);

		$share = \OCP\Share::getItemSharedWith('test', 'test.txt');

		$this->assertSame(\OCP\Constants::PERMISSION_ALL & ~\OCP\Constants::PERMISSION_SHARE, $share['permissions'],
				'Failed asserting that user 4 is excluded from re-sharing');

		\OC::$server->getAppConfig()->deleteKey('core', 'shareapi_exclude_groups_list');
		\OC::$server->getAppConfig()->deleteKey('core', 'shareapi_exclude_groups');

	}

	public function testSharingAFolderThatIsSharedWithAGroupOfTheOwner() {
		\OC_User::setUserId($this->user1);
		$view = new \OC\Files\View('/' . $this->user1 . '/');
		$view->mkdir('files/test');
		$view->mkdir('files/test/sub1');
		$view->mkdir('files/test/sub1/sub2');

		$fileInfo = $view->getFileInfo('files/test/sub1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_GROUP, $this->group1, \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_CREATE),
			'Failed asserting that user 1 successfully shared "test/sub1" with group 1.'
		);

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertNotEmpty($result);
		$this->assertEquals(\OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_CREATE, $result['permissions']);

		$fileInfo = $view->getFileInfo('files/test/sub1/sub2');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user4, \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared "test/sub1/sub2" with user 4.'
		);

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertNotEmpty($result);
		$this->assertEquals(\OCP\Constants::PERMISSION_READ, $result['permissions']);
	}

	public function testSharingAFileInsideAFolderThatIsAlreadyShared() {
		\OC_User::setUserId($this->user1);
		$view = new \OC\Files\View('/' . $this->user1 . '/');
		$view->mkdir('files/test');
		$view->mkdir('files/test/sub1');
		$view->file_put_contents('files/test/sub1/file.txt', 'abc');

		$folderInfo = $view->getFileInfo('files/test/sub1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);
		$folderId = $folderInfo->getId();

		$fileInfo = $view->getFileInfo('files/test/sub1/file.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $folderId, \OCP\Share::SHARE_TYPE_GROUP, $this->group2, \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_UPDATE),
			'Failed asserting that user 1 successfully shared "test/sub1" with group 2.'
		);

		$this->assertTrue(
			\OCP\Share::shareItem('file', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared "test/sub1/file.txt" with user 2.'
		);

		$result = \OCP\Share::getItemsSharedWithUser('file', $this->user2);
		$this->assertCount(2, $result);

		foreach ($result as $share) {
			$itemName = substr($share['path'], strrpos($share['path'], '/'));
			$this->assertSame($itemName, $share['file_target'], 'Asserting that the file_target is the last segment of the path');
			$this->assertSame($share['item_target'], '/' . $share['item_source'], 'Asserting that the item is the item that was shared');
		}
	}

	protected function shareUserOneTestFileWithGroupOne() {
		\OC_User::setUserId($this->user1);
		$this->assertTrue(
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_GROUP, $this->group1, \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared text.txt with group 1.'
		);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemShared('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of user 1.'
		);

		\OC_User::setUserId($this->user2);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that user 2 has access to test.txt after initial sharing.'
		);

		\OC_User::setUserId($this->user3);
		$this->assertContains(
			'test.txt',
			\OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
			'Failed asserting that user 3 has access to test.txt after initial sharing.'
		);
	}

	/**
	 * Test that unsharing from group will also delete all
	 * child entries
	 */
	public function testShareWithGroupThenUnshare() {
		\OC_User::setUserId($this->user5);
		\OCP\Share::shareItem(
			'test',
			'test.txt',
			\OCP\Share::SHARE_TYPE_GROUP,
			$this->group1,
			\OCP\Constants::PERMISSION_ALL
		);

		$targetUsers = array($this->user1, $this->user2, $this->user3);

		foreach($targetUsers as $targetUser) {
			\OC_User::setUserId($targetUser);
			$items = \OCP\Share::getItemsSharedWithUser(
				'test',
				$targetUser,
				Backend::FORMAT_TARGET
			);
			$this->assertEquals(1, count($items));
		}

		\OC_User::setUserId($this->user5);
		\OCP\Share::unshare(
			'test',
			'test.txt',
			\OCP\Share::SHARE_TYPE_GROUP,
			$this->group1
		);

		// verify that all were deleted
		foreach($targetUsers as $targetUser) {
			\OC_User::setUserId($targetUser);
			$items = \OCP\Share::getItemsSharedWithUser(
				'test',
				$targetUser,
				Backend::FORMAT_TARGET
			);
			$this->assertEquals(0, count($items));
		}
	}

	public function testShareWithGroupAndUserBothHaveTheSameId() {

		$this->shareUserTestFileWithUser($this->user1, $this->groupAndUser);

		\OC_User::setUserId($this->groupAndUser);

		$this->assertEquals(array('test.txt'), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
				'"groupAndUser"-User does not see the file but it was shared with him');

		\OC_User::setUserId($this->user2);
		$this->assertEquals(array(), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
				'User2 sees test.txt but it was only shared with the user "groupAndUser" and not with group');

		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::unshareAll('test', 'test.txt'));

		$this->assertTrue(
				\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_GROUP, $this->groupAndUser, \OCP\Constants::PERMISSION_READ),
				'Failed asserting that user 1 successfully shared text.txt with group 1.'
		);

		\OC_User::setUserId($this->groupAndUser);
		$this->assertEquals(array(), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
				'"groupAndUser"-User sees test.txt but it was only shared with the group "groupAndUser" and not with the user');

		\OC_User::setUserId($this->user2);
		$this->assertEquals(array('test.txt'), \OCP\Share::getItemSharedWith('test', 'test.txt', Backend::FORMAT_SOURCE),
				'User2 does not see test.txt but it was shared with the group "groupAndUser"');

		\OC_User::setUserId($this->user1);
		$this->assertTrue(\OCP\Share::unshareAll('test', 'test.txt'));

	}

	/**
	 * @param boolean|string $token
	 * @return array
	 */
	protected function getShareByValidToken($token) {
		$row = \OCP\Share::getShareByToken($token);
		$this->assertInternalType(
			'array',
			$row,
			"Failed asserting that a share for token $token exists."
		);
		return $row;
	}

	public function testGetItemSharedWithUser() {
		\OC_User::setUserId($this->user1);

		//add dummy values to the share table
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`) VALUES (?,?,?,?,?,?)');
		$args = array('test', 99, 'target1', \OCP\Share::SHARE_TYPE_USER, $this->user2, $this->user1);
		$query->execute($args);
		$args = array('test', 99, 'target2', \OCP\Share::SHARE_TYPE_USER, $this->user4, $this->user1);
		$query->execute($args);
		$args = array('test', 99, 'target3', \OCP\Share::SHARE_TYPE_USER, $this->user3, $this->user2);
		$query->execute($args);
		$args = array('test', 99, 'target4', \OCP\Share::SHARE_TYPE_USER, $this->user3, $this->user4);
		$query->execute($args);
		$args = array('test', 99, 'target4', \OCP\Share::SHARE_TYPE_USER, $this->user6, $this->user4);
		$query->execute($args);


		$result1 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user2, $this->user1);
		$this->assertSame(1, count($result1));
		$this->verifyResult($result1, array('target1'));

		$result2 = \OCP\Share::getItemSharedWithUser('test', 99, null, $this->user1);
		$this->assertSame(2, count($result2));
		$this->verifyResult($result2, array('target1', 'target2'));

		$result3 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user3);
		$this->assertSame(2, count($result3));
		$this->verifyResult($result3, array('target3', 'target4'));

		$result4 = \OCP\Share::getItemSharedWithUser('test', 99, null, null);
		$this->assertSame(5, count($result4)); // 5 because target4 appears twice
		$this->verifyResult($result4, array('target1', 'target2', 'target3', 'target4'));

		$result6 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user6, null);
		$this->assertSame(1, count($result6));
		$this->verifyResult($result6, array('target4'));
	}

	public function testGetItemSharedWithUserFromGroupShare() {
		\OC_User::setUserId($this->user1);

		//add dummy values to the share table
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`) VALUES (?,?,?,?,?,?)');
		$args = array('test', 99, 'target1', \OCP\Share::SHARE_TYPE_GROUP, $this->group1, $this->user1);
		$query->execute($args);
		$args = array('test', 99, 'target2', \OCP\Share::SHARE_TYPE_GROUP, $this->group2, $this->user1);
		$query->execute($args);
		$args = array('test', 99, 'target3', \OCP\Share::SHARE_TYPE_GROUP, $this->group1, $this->user2);
		$query->execute($args);
		$args = array('test', 99, 'target4', \OCP\Share::SHARE_TYPE_GROUP, $this->group1, $this->user4);
		$query->execute($args);

		// user2 is in group1 and group2
		$result1 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user2, $this->user1);
		$this->assertSame(2, count($result1));
		$this->verifyResult($result1, array('target1', 'target2'));

		$result2 = \OCP\Share::getItemSharedWithUser('test', 99, null, $this->user1);
		$this->assertSame(2, count($result2));
		$this->verifyResult($result2, array('target1', 'target2'));

		// user3 is in group1 and group2
		$result3 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user3);
		$this->assertSame(3, count($result3));
		$this->verifyResult($result3, array('target1', 'target3', 'target4'));

		$result4 = \OCP\Share::getItemSharedWithUser('test', 99, null, null);
		$this->assertSame(4, count($result4));
		$this->verifyResult($result4, array('target1', 'target2', 'target3', 'target4'));

		$result6 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user6, null);
		$this->assertSame(0, count($result6));
	}

	public function verifyResult($result, $expected) {
		foreach ($result as $r) {
			if (in_array($r['item_target'], $expected)) {
				$key = array_search($r['item_target'], $expected);
				unset($expected[$key]);
			}
		}
		$this->assertEmpty($expected, 'did not found all expected values');
	}

	public function testGetShareSubItemsWhenUserNotInGroup() {
		\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_GROUP, $this->group1, \OCP\Constants::PERMISSION_READ);

		$result = \OCP\Share::getItemsSharedWithUser('test', $this->user2);
		$this->assertCount(1, $result);

		$groupShareId = array_keys($result)[0];

		// remove user from group
		$userObject = \OC::$server->getUserManager()->get($this->user2);
		\OC::$server->getGroupManager()->get($this->group1)->removeUser($userObject);

		$result = \OCP\Share::getItemsSharedWithUser('test', $this->user2);
		$this->assertCount(0, $result);

		// test with buggy data
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(2), // group sub-share
				'share_with' => $qb->expr()->literal($this->user2),
				'parent' => $qb->expr()->literal($groupShareId),
				'uid_owner' => $qb->expr()->literal($this->user1),
				'item_type' => $qb->expr()->literal('test'),
				'item_source' => $qb->expr()->literal('test.txt'),
				'item_target' => $qb->expr()->literal('test.txt'),
				'file_target' => $qb->expr()->literal('test2.txt'),
				'permissions' => $qb->expr()->literal(1),
				'stime' => $qb->expr()->literal(time()),
			])->execute();

		$result = \OCP\Share::getItemsSharedWithUser('test', $this->user2);
		$this->assertCount(0, $result);

		$qb->delete('share')->execute();
	}

	public function testShareItemWithLink() {
		\OC_User::setUserId($this->user1);
		$token = \OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
		$this->assertInternalType(
			'string',
			$token,
			'Failed asserting that user 1 successfully shared text.txt as link with token.'
		);

		// testGetShareByTokenNoExpiration
		$row = $this->getShareByValidToken($token);
		$this->assertEmpty(
			$row['expiration'],
			'Failed asserting that the returned row does not have an expiration date.'
		);

		// testGetShareByTokenExpirationValid
		$this->assertTrue(
			\OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInFuture, ''),
			'Failed asserting that user 1 successfully set a future expiration date for the test.txt share.'
		);
		$row = $this->getShareByValidToken($token);
		$this->assertNotEmpty(
			$row['expiration'],
			'Failed asserting that the returned row has an expiration date.'
		);

		// manipulate share table and set expire date to the past
		$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `expiration` = ? WHERE `item_type` = ? AND `item_source` = ?  AND `uid_owner` = ? AND `share_type` = ?');
		$query->bindValue(1, new \DateTime($this->dateInPast), 'datetime');
		$query->bindValue(2, 'test');
		$query->bindValue(3, 'test.txt');
		$query->bindValue(4, $this->user1);
		$query->bindValue(5, \OCP\Share::SHARE_TYPE_LINK);
		$query->execute();

		$this->assertFalse(
			\OCP\Share::getShareByToken($token),
			'Failed asserting that an expired share could not be found.'
		);
	}

	public function testShareItemWithLinkAndDefaultExpireDate() {
		\OC_User::setUserId($this->user1);

		$config = \OC::$server->getConfig();

		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_expire_after_n_days', '2');

		$token = \OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
		$this->assertInternalType(
			'string',
			$token,
			'Failed asserting that user 1 successfully shared text.txt as link with token.'
		);

		// share should have default expire date

		$row = $this->getShareByValidToken($token);
		$this->assertNotEmpty(
			$row['expiration'],
			'Failed asserting that the returned row has an default expiration date.'
		);

		$config->deleteAppValue('core', 'shareapi_default_expire_date');
		$config->deleteAppValue('core', 'shareapi_expire_after_n_days');

	}

	public function dataShareWithRemoteUserAndRemoteIsInvalid() {
		return [
			// Invalid path
			array('user@'),

			// Invalid user
			array('@server'),
			array('us/er@server'),
			array('us:er@server'),

			// Invalid splitting
			array('user'),
			array(''),
			array('us/erserver'),
			array('us:erserver'),
		];
	}

	/**
	 * @dataProvider dataShareWithRemoteUserAndRemoteIsInvalid
	 *
	 * @param string $remoteId
	 * @expectedException \OC\HintException
	 */
	public function testShareWithRemoteUserAndRemoteIsInvalid($remoteId) {
		\OC_User::setUserId($this->user1);
		\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, $remoteId, \OCP\Constants::PERMISSION_ALL);
	}

	public function testUnshareAll() {
		$this->shareUserTestFileWithUser($this->user1, $this->user2);
		$this->shareUserTestFileWithUser($this->user2, $this->user3);
		$this->shareUserTestFileWithUser($this->user3, $this->user4);
		$this->shareUserOneTestFileWithGroupOne();

		\OC_User::setUserId($this->user1);
		$this->assertEquals(
			array('test.txt', 'test.txt'),
			\OCP\Share::getItemsShared('test', Backend::FORMAT_SOURCE),
			'Failed asserting that the test.txt file is shared exactly two times by user1.'
		);

		\OC_User::setUserId($this->user2);
		$this->assertEquals(
			array('test.txt'),
			\OCP\Share::getItemsShared('test', Backend::FORMAT_SOURCE),
			'Failed asserting that the test.txt file is shared exactly once by user2.'
		);

		\OC_User::setUserId($this->user3);
		$this->assertEquals(
			array('test.txt'),
			\OCP\Share::getItemsShared('test', Backend::FORMAT_SOURCE),
			'Failed asserting that the test.txt file is shared exactly once by user3.'
		);

		$this->assertTrue(
			\OCP\Share::unshareAll('test', 'test.txt'),
			'Failed asserting that user 3 successfully unshared all shares of the test.txt share.'
		);

		$this->assertEquals(
			array(),
			\OCP\Share::getItemsShared('test'),
			'Failed asserting that the share of the test.txt file by user 3 has been removed.'
		);

		\OC_User::setUserId($this->user1);
		$this->assertEquals(
			array(),
			\OCP\Share::getItemsShared('test'),
			'Failed asserting that both shares of the test.txt file by user 1 have been removed.'
		);

		\OC_User::setUserId($this->user2);
		$this->assertEquals(
			array(),
			\OCP\Share::getItemsShared('test'),
			'Failed asserting that the share of the test.txt file by user 2 has been removed.'
		);
	}

	/**
	 * @dataProvider checkPasswordProtectedShareDataProvider
	 * @param $expected
	 * @param $item
	 */
	public function testCheckPasswordProtectedShare($expected, $item) {
		\OC::$server->getSession()->set('public_link_authenticated', '100');
		$result = \OCP\Share::checkPasswordProtectedShare($item);
		$this->assertEquals($expected, $result);
	}

	function checkPasswordProtectedShareDataProvider() {
		return array(
			array(true, array()),
			array(true, array('share_with' => null)),
			array(true, array('share_with' => '')),
			array(true, array('share_with' => '1234567890', 'share_type' => '1')),
			array(true, array('share_with' => '1234567890', 'share_type' => 1)),
			array(true, array('share_with' => '1234567890', 'share_type' => '3', 'id' => '100')),
			array(true, array('share_with' => '1234567890', 'share_type' => 3, 'id' => '100')),
			array(true, array('share_with' => '1234567890', 'share_type' => '3', 'id' => 100)),
			array(true, array('share_with' => '1234567890', 'share_type' => 3, 'id' => 100)),
			array(false, array('share_with' => '1234567890', 'share_type' => '3', 'id' => '101')),
			array(false, array('share_with' => '1234567890', 'share_type' => 3, 'id' => '101')),
			array(false, array('share_with' => '1234567890', 'share_type' => '3', 'id' => 101)),
			array(false, array('share_with' => '1234567890', 'share_type' => 3, 'id' => 101)),
		);
	}

	/**
	 * @dataProvider urls
	 */
	function testRemoveProtocolFromUrl($url, $expectedResult) {
		$share = new \OC\Share\Share();
		$result = self::invokePrivate($share, 'removeProtocolFromUrl', array($url));
		$this->assertSame($expectedResult, $result);
	}

	function urls() {
		return array(
			array('http://owncloud.org', 'owncloud.org'),
			array('https://owncloud.org', 'owncloud.org'),
			array('owncloud.org', 'owncloud.org'),
		);
	}

	public function dataRemoteShareUrlCalls() {
		return [
			['admin@localhost', 'localhost'],
			['admin@https://localhost', 'localhost'],
			['admin@http://localhost', 'localhost'],
			['admin@localhost/subFolder', 'localhost/subFolder'],
		];
	}

	/**
	 * @dataProvider dataRemoteShareUrlCalls
	 *
	 * @param string $shareWith
	 * @param string $urlHost
	 */
	public function testRemoteShareUrlCalls($shareWith, $urlHost) {
		$httpHelperMock = $this->getMockBuilder('OC\HTTPHelper')
			->disableOriginalConstructor()
			->getMock();
		$this->overwriteService('HTTPHelper', $httpHelperMock);

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://' . $urlHost . '/ocs/v1.php/cloud/shares'), $this->anything())
			->willReturn(['success' => false, 'result' => 'Exception']);
		$httpHelperMock->expects($this->at(1))
			->method('post')
			->with($this->stringStartsWith('http://' . $urlHost . '/ocs/v1.php/cloud/shares'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, $shareWith, \OCP\Constants::PERMISSION_READ);
		$shares = \OCP\Share::getItemShared('test', 'test.txt');
		$share = array_shift($shares);

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://' . $urlHost . '/ocs/v1.php/cloud/shares/' . $share['id'] . '/unshare'), $this->anything())
			->willReturn(['success' => false, 'result' => 'Exception']);
		$httpHelperMock->expects($this->at(1))
			->method('post')
			->with($this->stringStartsWith('http://' . $urlHost . '/ocs/v1.php/cloud/shares/' . $share['id'] . '/unshare'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OCP\Share::unshare('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, $shareWith);
		$this->restoreService('HTTPHelper');
	}

	/**
	 * @dataProvider dataProviderTestGroupItems
	 * @param array $ungrouped
	 * @param array $grouped
	 */
	function testGroupItems($ungrouped, $grouped) {

		$result = DummyShareClass::groupItemsTest($ungrouped);

		$this->compareArrays($grouped, $result);

	}

	function compareArrays($result, $expectedResult) {
		foreach ($expectedResult as $key => $value) {
			if (is_array($value)) {
				$this->compareArrays($result[$key], $value);
			} else {
				$this->assertSame($value, $result[$key]);
			}
		}
	}

	function dataProviderTestGroupItems() {
		return array(
			// one array with one share
			array(
				array( // input
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_ALL, 'item_target' => 't1')),
				array( // expected result
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_ALL, 'item_target' => 't1'))),
			// two shares both point to the same source
			array(
				array( // input
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ, 'item_target' => 't1'),
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't1'),
					),
				array( // expected result
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't1',
						'grouped' => array(
							array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ, 'item_target' => 't1'),
							array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't1'),
							)
						),
					)
				),
			// two shares both point to the same source but with different targets
			array(
				array( // input
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ, 'item_target' => 't1'),
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't2'),
					),
				array( // expected result
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ, 'item_target' => 't1'),
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't2'),
					)
				),
			// three shares two point to the same source
			array(
				array( // input
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ, 'item_target' => 't1'),
					array('item_source' => 2, 'permissions' => \OCP\Constants::PERMISSION_CREATE, 'item_target' => 't2'),
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't1'),
					),
				array( // expected result
					array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't1',
						'grouped' => array(
							array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_READ, 'item_target' => 't1'),
							array('item_source' => 1, 'permissions' => \OCP\Constants::PERMISSION_UPDATE, 'item_target' => 't1'),
							)
						),
					array('item_source' => 2, 'permissions' => \OCP\Constants::PERMISSION_CREATE, 'item_target' => 't2'),
					)
				),
		);
	}

	/**
	 * Ensure that we do not allow removing a an expiration date from a link share if this
	 * is enforced by the settings.
	 */
	public function testClearExpireDateWhileEnforced() {
		\OC_User::setUserId($this->user1);

		\OC::$server->getAppConfig()->setValue('core', 'shareapi_default_expire_date', 'yes');
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_expire_after_n_days', '2');
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_enforce_expire_date', 'yes');

		$token = \OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
		$this->assertInternalType(
			'string',
			$token,
			'Failed asserting that user 1 successfully shared text.txt as link with token.'
		);

		$setExpireDateFailed = false;
		try {
			$this->assertTrue(
					\OCP\Share::setExpirationDate('test', 'test.txt', '', ''),
					'Failed asserting that user 1 successfully set an expiration date for the test.txt share.'
			);
		} catch (\Exception $e) {
			$setExpireDateFailed = true;
		}

		$this->assertTrue($setExpireDateFailed);

		\OC::$server->getAppConfig()->deleteKey('core', 'shareapi_default_expire_date');
		\OC::$server->getAppConfig()->deleteKey('core', 'shareapi_expire_after_n_days');
		\OC::$server->getAppConfig()->deleteKey('core', 'shareapi_enforce_expire_date');
	}

	/**
	 * Cannot set password is there is no user
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage User not logged in
	 */
	public function testSetPasswordNoUser() {
		$userSession = $this->getMockBuilder('\OCP\IUserSession')
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$connection  = $this->getMockBuilder('\OC\DB\Connection')
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$config = $this->getMockBuilder('\OCP\IConfig')
		               ->disableOriginalConstructor()
		               ->getMock();

		\OC\Share\Share::setPassword($userSession, $connection, $config, 1, 'pass');
	}

	public function testPasswords() {
		$pass = 'secret';

		$this->shareUserTestFileAsLink();

		$userSession = \OC::$server->getUserSession();
		$connection = \OC::$server->getDatabaseConnection();
		$config = $this->getMockBuilder('\OCP\IConfig')
		               ->disableOriginalConstructor()
		               ->getMock();

		// Find the share ID in the db
		$qb = $connection->getQueryBuilder();
		$qb->select('id')
		   ->from('share')
		   ->where($qb->expr()->eq('item_type', $qb->createParameter('type')))
		   ->andWhere($qb->expr()->eq('item_source', $qb->createParameter('source')))
		   ->andWhere($qb->expr()->eq('uid_owner', $qb->createParameter('owner')))
		   ->andWhere($qb->expr()->eq('share_type', $qb->createParameter('share_type')))
		   ->setParameter('type', 'test')
		   ->setParameter('source', 'test.txt')
		   ->setParameter('owner', $this->user1)
		   ->setParameter('share_type', \OCP\Share::SHARE_TYPE_LINK);

		$res = $qb->execute()->fetchAll();
		$this->assertCount(1, $res);
		$id = $res[0]['id'];

		// Set password on share
		$res = \OC\Share\Share::setPassword($userSession, $connection, $config, $id, $pass);
		$this->assertTrue($res);

		// Fetch the hash from the database
		$qb = $connection->getQueryBuilder();
		$qb->select('share_with')
		   ->from('share')
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
		   ->setParameter('id', $id);
		$hash = $qb->execute()->fetch()['share_with'];

		$hasher = \OC::$server->getHasher();

		// Verify hash
		$this->assertTrue($hasher->verify($pass, $hash));
	}

	/**
	 * Test setting a password when everything is fine
	 */
	public function testSetPassword() {
		$user = $this->getMockBuilder('\OCP\IUser')
		             ->disableOriginalConstructor()
		             ->getMock();
		$user->method('getUID')->willReturn('user');

		$userSession = $this->getMockBuilder('\OCP\IUserSession')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$userSession->method('getUser')->willReturn($user);


		$ex = $this->getMockBuilder('\OC\DB\QueryBuilder\ExpressionBuilder\ExpressionBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb = $this->getMockBuilder('\OC\DB\QueryBuilder\QueryBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb->method('update')->will($this->returnSelf());
		$qb->method('set')->will($this->returnSelf());
		$qb->method('where')->will($this->returnSelf());
		$qb->method('andWhere')->will($this->returnSelf());
		$qb->method('select')->will($this->returnSelf());
		$qb->method('from')->will($this->returnSelf());
		$qb->method('setParameter')->will($this->returnSelf());
		$qb->method('expr')->willReturn($ex);

		$ret = $this->getMockBuilder('\Doctrine\DBAL\Driver\ResultStatement')
		            ->disableOriginalConstructor()
					->getMock();
		$ret->method('fetch')->willReturn(['uid_owner' => 'user']);
		$qb->method('execute')->willReturn($ret);


		$connection  = $this->getMockBuilder('\OC\DB\Connection')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$connection->method('getQueryBuilder')->willReturn($qb);

		$config = $this->getMockBuilder('\OCP\IConfig')
		               ->disableOriginalConstructor()
		               ->getMock();


		$res = \OC\Share\Share::setPassword($userSession, $connection, $config, 1, 'pass');

		$this->assertTrue($res);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Cannot remove password
	 *
	 * Test removing a password when password is enforced
	 */
	public function testSetPasswordRemove() {
		$user = $this->getMockBuilder('\OCP\IUser')
		             ->disableOriginalConstructor()
		             ->getMock();
		$user->method('getUID')->willReturn('user');

		$userSession = $this->getMockBuilder('\OCP\IUserSession')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$userSession->method('getUser')->willReturn($user);


		$ex = $this->getMockBuilder('\OC\DB\QueryBuilder\ExpressionBuilder\ExpressionBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb = $this->getMockBuilder('\OC\DB\QueryBuilder\QueryBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb->method('update')->will($this->returnSelf());
		$qb->method('select')->will($this->returnSelf());
		$qb->method('from')->will($this->returnSelf());
		$qb->method('set')->will($this->returnSelf());
		$qb->method('where')->will($this->returnSelf());
		$qb->method('andWhere')->will($this->returnSelf());
		$qb->method('setParameter')->will($this->returnSelf());
		$qb->method('expr')->willReturn($ex);

		$ret = $this->getMockBuilder('\Doctrine\DBAL\Driver\ResultStatement')
		            ->disableOriginalConstructor()
					->getMock();
		$ret->method('fetch')->willReturn(['uid_owner' => 'user']);
		$qb->method('execute')->willReturn($ret);


		$connection  = $this->getMockBuilder('\OC\DB\Connection')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$connection->method('getQueryBuilder')->willReturn($qb);

		$config = $this->getMockBuilder('\OCP\IConfig')
		               ->disableOriginalConstructor()
		               ->getMock();
		$config->method('getAppValue')->willReturn('yes');

		\OC\Share\Share::setPassword($userSession, $connection, $config, 1, '');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Share not found
	 *
	 * Test modification of invaid share
	 */
	public function testSetPasswordInvalidShare() {
		$user = $this->getMockBuilder('\OCP\IUser')
		             ->disableOriginalConstructor()
		             ->getMock();
		$user->method('getUID')->willReturn('user');

		$userSession = $this->getMockBuilder('\OCP\IUserSession')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$userSession->method('getUser')->willReturn($user);


		$ex = $this->getMockBuilder('\OC\DB\QueryBuilder\ExpressionBuilder\ExpressionBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb = $this->getMockBuilder('\OC\DB\QueryBuilder\QueryBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb->method('update')->will($this->returnSelf());
		$qb->method('set')->will($this->returnSelf());
		$qb->method('where')->will($this->returnSelf());
		$qb->method('andWhere')->will($this->returnSelf());
		$qb->method('select')->will($this->returnSelf());
		$qb->method('from')->will($this->returnSelf());
		$qb->method('setParameter')->will($this->returnSelf());
		$qb->method('expr')->willReturn($ex);

		$ret = $this->getMockBuilder('\Doctrine\DBAL\Driver\ResultStatement')
		            ->disableOriginalConstructor()
					->getMock();
		$ret->method('fetch')->willReturn([]);
		$qb->method('execute')->willReturn($ret);


		$connection  = $this->getMockBuilder('\OC\DB\Connection')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$connection->method('getQueryBuilder')->willReturn($qb);

		$config = $this->getMockBuilder('\OCP\IConfig')
		               ->disableOriginalConstructor()
		               ->getMock();


		\OC\Share\Share::setPassword($userSession, $connection, $config, 1, 'pass');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Cannot update share of a different user
	 *
	 * Test modification of share of another user
	 */
	public function testSetPasswordShareOtherUser() {
		$user = $this->getMockBuilder('\OCP\IUser')
		             ->disableOriginalConstructor()
		             ->getMock();
		$user->method('getUID')->willReturn('user');

		$userSession = $this->getMockBuilder('\OCP\IUserSession')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$userSession->method('getUser')->willReturn($user);


		$ex = $this->getMockBuilder('\OC\DB\QueryBuilder\ExpressionBuilder\ExpressionBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb = $this->getMockBuilder('\OC\DB\QueryBuilder\QueryBuilder')
		           ->disableOriginalConstructor()
		           ->getMock();
		$qb->method('update')->will($this->returnSelf());
		$qb->method('set')->will($this->returnSelf());
		$qb->method('where')->will($this->returnSelf());
		$qb->method('andWhere')->will($this->returnSelf());
		$qb->method('select')->will($this->returnSelf());
		$qb->method('from')->will($this->returnSelf());
		$qb->method('setParameter')->will($this->returnSelf());
		$qb->method('expr')->willReturn($ex);

		$ret = $this->getMockBuilder('\Doctrine\DBAL\Driver\ResultStatement')
		            ->disableOriginalConstructor()
					->getMock();
		$ret->method('fetch')->willReturn(['uid_owner' => 'user2']);
		$qb->method('execute')->willReturn($ret);


		$connection  = $this->getMockBuilder('\OC\DB\Connection')
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$connection->method('getQueryBuilder')->willReturn($qb);

		$config = $this->getMockBuilder('\OCP\IConfig')
		               ->disableOriginalConstructor()
		               ->getMock();


		\OC\Share\Share::setPassword($userSession, $connection, $config, 1, 'pass');
	}

	/**
	 * Make sure that a user cannot have multiple identical shares to remote users
	 */
	public function testOnlyOneRemoteShare() {
		$httpHelperMock = $this->getMockBuilder('OC\HTTPHelper')
			->disableOriginalConstructor()
			->getMock();
		$this->overwriteService('HTTPHelper', $httpHelperMock);

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://localhost/ocs/v1.php/cloud/shares'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, 'foo@localhost', \OCP\Constants::PERMISSION_READ);
		$shares = \OCP\Share::getItemShared('test', 'test.txt');
		$share = array_shift($shares);

		//Try share again
		try {
			\OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, 'foo@localhost', \OCP\Constants::PERMISSION_READ);
			$this->fail('Identical remote shares are not allowed');
		} catch (\Exception $e) {
			$this->assertEquals('Sharing test.txt failed, because this item is already shared with foo@localhost', $e->getMessage());
		}

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://localhost/ocs/v1.php/cloud/shares/' . $share['id'] . '/unshare'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OCP\Share::unshare('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, 'foo@localhost');
		$this->restoreService('HTTPHelper');
	}

	/**
	 * Test case for #19119
	 */
	public function testReshareWithLinkDefaultExpirationDate() {
		$config = \OC::$server->getConfig();
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_expire_after_n_days', '2');

		// Expiration date
		$expireAt = time() + 2 * 24*60*60;
		$date = new \DateTime();
		$date->setTimestamp($expireAt);
		$date->setTime(0, 0, 0);

		//Share a file from user 1 to user 2
		$this->shareUserTestFileWithUser($this->user1, $this->user2);

		//User 2 shares as link
		\OC_User::setUserId($this->user2);
		$result = \OCP\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
		$this->assertTrue(is_string($result));

		//Check if expire date is correct
		$result = \OCP\Share::getItemShared('test', 'test.txt');
		$this->assertCount(1, $result);
		$result = reset($result);
		$this->assertNotEmpty($result['expiration']);
		$expireDate = new \DateTime($result['expiration']);
		$this->assertEquals($date, $expireDate);

		//Unshare
		$this->assertTrue(\OCP\Share::unshareAll('test', 'test.txt'));

		//Reset config
		$config->deleteAppValue('core', 'shareapi_default_expire_date');
		$config->deleteAppValue('core', 'shareapi_expire_after_n_days');
	}

	/**
	 * Test case for #17560
	 */
	public function testAccesToSharedSubFolder() {
		\OC_User::setUserId($this->user1);
		$view = new \OC\Files\View('/' . $this->user1 . '/');
		$view->mkdir('files/folder1');

		$fileInfo = $view->getFileInfo('files/folder1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 2.'
		);
		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user3, \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 3.'
		);

		$view->mkdir('files/folder1/folder2');

		$fileInfo = $view->getFileInfo('files/folder1/folder2');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user4, \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 4.'
		);

		$res = \OCP\Share::getItemShared(
			'folder',
			$fileId,
			\OCP\Share::FORMAT_NONE,
			null,
			true
		);
		$this->assertCount(3, $res);

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user5, \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 5.'
		);

		$res = \OCP\Share::getItemShared(
			'folder',
			$fileId,
			\OCP\Share::FORMAT_NONE,
			null,
			true
		);
		$this->assertCount(4, $res);
	}

	public function testShareWithSelfError() {
		\OC_User::setUserId($this->user1);
		$view = new \OC\Files\View('/' . $this->user1 . '/');
		$view->mkdir('files/folder1');

		$fileInfo = $view->getFileInfo('files/folder1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		try {
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user1, \OCP\Constants::PERMISSION_ALL);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing /folder1 failed, because you can not share with yourself', $e->getMessage());
		}
	}


	public function testShareWithOwnerError() {
		\OC_User::setUserId($this->user1);
		$view = new \OC\Files\View('/' . $this->user1 . '/');
		$view->mkdir('files/folder1');

		$fileInfo = $view->getFileInfo('files/folder1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2, \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 2.'
		);

		\OC_User::setUserId($this->user2);
		try {
			\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user1, \OCP\Constants::PERMISSION_ALL);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing failed, because the user ' . $this->user1 . ' is the original sharer', $e->getMessage());
		}
	}
}

class DummyShareClass extends \OC\Share\Share {
	public static function groupItemsTest($items) {
		return parent::groupItems($items, 'test');
	}
}

class DummyHookListener {
	static $shareType = null;

	public static function listen($params) {
		self::$shareType = $params['shareType'];
	}
}
