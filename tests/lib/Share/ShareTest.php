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
use OC\Share\Share;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class Test_Share
 *
 * @group DB
 */
class ShareTest extends \Test\TestCase {

	protected $itemType;

	/** @var IUser */
	protected $user1;
	/** @var IUser */
	protected $user2;
	/** @var IUser */
	protected $user3;
	/** @var IUser */
	protected $user4;
	/** @var IUser */
	protected $user5;
	/** @var IUser */
	protected $user6;
	/** @var IUser */
	protected $groupAndUser_user;

	/** @var IGroup */
	protected $group1;
	/** @var IGroup */
	protected $group2;
	/** @var IGroup */
	protected $groupAndUser_group;

	protected $resharing;
	protected $dateInFuture;
	protected $dateInPast;

	/** @var IGroupManager */
	protected $groupManager;
	/** @var IUserManager */
	protected $userManager;

	protected function setUp() {
		parent::setUp();

		$this->groupManager = \OC::$server->getGroupManager();
		$this->userManager = \OC::$server->getUserManager();

		$this->userManager->clearBackends();
		$this->userManager->registerBackend(new \Test\Util\User\Dummy());

		$this->user1 = $this->userManager->createUser($this->getUniqueID('user1_'), 'pass');
		$this->user2 = $this->userManager->createUser($this->getUniqueID('user2_'), 'pass');
		$this->user3 = $this->userManager->createUser($this->getUniqueID('user3_'), 'pass');
		$this->user4 = $this->userManager->createUser($this->getUniqueID('user4_'), 'pass');
		$this->user5 = $this->userManager->createUser($this->getUniqueID('user5_'), 'pass');
		$this->user6 = $this->userManager->createUser($this->getUniqueID('user6_'), 'pass');
		$groupAndUserId = $this->getUniqueID('groupAndUser_');
		$this->groupAndUser_user = $this->userManager->createUser($groupAndUserId, 'pass');
		\OC_User::setUserId($this->user1->getUID());

		$this->groupManager->clearBackends();
		$this->groupManager->addBackend(new \Test\Util\Group\Dummy());
		$this->group1 = $this->groupManager->createGroup($this->getUniqueID('group1_'));
		$this->group2 = $this->groupManager->createGroup($this->getUniqueID('group2_'));
		$this->groupAndUser_group = $this->groupManager->createGroup($groupAndUserId);

		$this->group1->addUser($this->user1);
		$this->group1->addUser($this->user2);
		$this->group1->addUser($this->user3);
		$this->group2->addUser($this->user2);
		$this->group2->addUser($this->user4);
		$this->groupAndUser_group->addUser($this->user2);
		$this->groupAndUser_group->addUser($this->user3);

		\OC\Share\Share::registerBackend('test', 'Test\Share\Backend');
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

		$this->user1->delete();
		$this->user2->delete();
		$this->user3->delete();
		$this->user4->delete();
		$this->user5->delete();
		$this->user6->delete();
		$this->groupAndUser_user->delete();

		$this->group1->delete();
		$this->group2->delete();
		$this->groupAndUser_group->delete();

		$this->logout();
		parent::tearDown();
	}

	public function testShareInvalidShareType() {
		$message = 'Share type foobar is not valid for test.txt';
		try {
			\OC\Share\Share::shareItem('test', 'test.txt', 'foobar', $this->user2, \OCP\Constants::PERMISSION_READ);
		} catch (\Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
	}

	public function testGetShareFromOutsideFilesFolder() {
		\OC_User::setUserId($this->user1->getUID());
		$view = new \OC\Files\View('/' . $this->user1->getUID() . '/');
		$view->mkdir('files/test');
		$view->mkdir('files/test/sub');

		$view->mkdir('files_trashbin');
		$view->mkdir('files_trashbin/files');

		$fileInfo = $view->getFileInfo('files/test/sub');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2->getUID(), \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared "test/sub" with user 2.'
		);

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertNotEmpty($result);

		$result = \OC\Share\Share::getItemSharedWithUser('folder', $fileId, $this->user2->getUID());
		$this->assertNotEmpty($result);

		$result = \OC\Share\Share::getItemsSharedWithUser('folder', $this->user2->getUID());
		$this->assertNotEmpty($result);

		// move to trash (keeps file id)
		$view->rename('files/test', 'files_trashbin/files/test');

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertEmpty($result, 'Share must not be returned for files outside of "files"');

		$result = \OC\Share\Share::getItemSharedWithUser('folder', $fileId, $this->user2->getUID());
		$this->assertEmpty($result, 'Share must not be returned for files outside of "files"');

		$result = \OC\Share\Share::getItemsSharedWithUser('folder', $this->user2->getUID());
		$this->assertEmpty($result, 'Share must not be returned for files outside of "files"');
	}

	public function testSharingAFolderThatIsSharedWithAGroupOfTheOwner() {
		\OC_User::setUserId($this->user1->getUID());
		$view = new \OC\Files\View('/' . $this->user1->getUID() . '/');
		$view->mkdir('files/test');
		$view->mkdir('files/test/sub1');
		$view->mkdir('files/test/sub1/sub2');

		$fileInfo = $view->getFileInfo('files/test/sub1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_GROUP, $this->group1->getGID(), \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_CREATE),
			'Failed asserting that user 1 successfully shared "test/sub1" with group 1.'
		);

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertNotEmpty($result);
		$this->assertEquals(\OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_CREATE, $result['permissions']);

		$fileInfo = $view->getFileInfo('files/test/sub1/sub2');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user4->getUID(), \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared "test/sub1/sub2" with user 4.'
		);

		$result = \OCP\Share::getItemShared('folder', $fileId, Backend::FORMAT_SOURCE);
		$this->assertNotEmpty($result);
		$this->assertEquals(\OCP\Constants::PERMISSION_READ, $result['permissions']);
	}

	public function testSharingAFileInsideAFolderThatIsAlreadyShared() {
		\OC_User::setUserId($this->user1->getUID());
		$view = new \OC\Files\View('/' . $this->user1->getUID() . '/');
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
			\OC\Share\Share::shareItem('folder', $folderId, \OCP\Share::SHARE_TYPE_GROUP, $this->group2->getGID(), \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_UPDATE),
			'Failed asserting that user 1 successfully shared "test/sub1" with group 2.'
		);

		$this->assertTrue(
			\OC\Share\Share::shareItem('file', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2->getUID(), \OCP\Constants::PERMISSION_READ),
			'Failed asserting that user 1 successfully shared "test/sub1/file.txt" with user 2.'
		);

		$result = \OC\Share\Share::getItemsSharedWithUser('file', $this->user2->getUID());
		$this->assertCount(2, $result);

		foreach ($result as $share) {
			$itemName = substr($share['path'], strrpos($share['path'], '/'));
			$this->assertSame($itemName, $share['file_target'], 'Asserting that the file_target is the last segment of the path');
			$this->assertSame($share['item_target'], '/' . $share['item_source'], 'Asserting that the item is the item that was shared');
		}
	}

	/**
	 * Test that unsharing from group will also delete all
	 * child entries
	 */
	public function testShareWithGroupThenUnshare() {
		\OC_User::setUserId($this->user5->getUID());
		\OC\Share\Share::shareItem(
			'test',
			'test.txt',
			\OCP\Share::SHARE_TYPE_GROUP,
			$this->group1->getGID(),
			\OCP\Constants::PERMISSION_ALL
		);

		$targetUsers = array($this->user1->getUID(), $this->user2->getUID(), $this->user3->getUID());

		foreach($targetUsers as $targetUser) {
			\OC_User::setUserId($targetUser);
			$items = \OC\Share\Share::getItemsSharedWithUser(
				'test',
				$targetUser,
				Backend::FORMAT_TARGET
			);
			$this->assertEquals(1, count($items));
		}

		\OC_User::setUserId($this->user5->getUID());
		\OC\Share\Share::unshare(
			'test',
			'test.txt',
			\OCP\Share::SHARE_TYPE_GROUP,
			$this->group1->getGID()
		);

		// verify that all were deleted
		foreach($targetUsers as $targetUser) {
			\OC_User::setUserId($targetUser);
			$items = \OC\Share\Share::getItemsSharedWithUser(
				'test',
				$targetUser,
				Backend::FORMAT_TARGET
			);
			$this->assertEquals(0, count($items));
		}
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
		\OC_User::setUserId($this->user1->getUID());

		//add dummy values to the share table
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`) VALUES (?,?,?,?,?,?)');
		$args = array('test', 99, 'target1', \OCP\Share::SHARE_TYPE_USER, $this->user2->getUID(), $this->user1->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target2', \OCP\Share::SHARE_TYPE_USER, $this->user4->getUID(), $this->user1->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target3', \OCP\Share::SHARE_TYPE_USER, $this->user3->getUID(), $this->user2->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target4', \OCP\Share::SHARE_TYPE_USER, $this->user3->getUID(), $this->user4->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target4', \OCP\Share::SHARE_TYPE_USER, $this->user6->getUID(), $this->user4->getUID());
		$query->execute($args);


		$result1 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user2->getUID(), $this->user1->getUID());
		$this->assertSame(1, count($result1));
		$this->verifyResult($result1, array('target1'));

		$result2 = \OCP\Share::getItemSharedWithUser('test', 99, null, $this->user1->getUID());
		$this->assertSame(2, count($result2));
		$this->verifyResult($result2, array('target1', 'target2'));

		$result3 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user3->getUID());
		$this->assertSame(2, count($result3));
		$this->verifyResult($result3, array('target3', 'target4'));

		$result4 = \OCP\Share::getItemSharedWithUser('test', 99, null, null);
		$this->assertSame(5, count($result4)); // 5 because target4 appears twice
		$this->verifyResult($result4, array('target1', 'target2', 'target3', 'target4'));

		$result6 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user6->getUID(), null);
		$this->assertSame(1, count($result6));
		$this->verifyResult($result6, array('target4'));
	}

	public function testGetItemSharedWithUserFromGroupShare() {
		\OC_User::setUserId($this->user1->getUID());

		//add dummy values to the share table
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`) VALUES (?,?,?,?,?,?)');
		$args = array('test', 99, 'target1', \OCP\Share::SHARE_TYPE_GROUP, $this->group1->getGID(), $this->user1->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target2', \OCP\Share::SHARE_TYPE_GROUP, $this->group2->getGID(), $this->user1->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target3', \OCP\Share::SHARE_TYPE_GROUP, $this->group1->getGID(), $this->user2->getUID());
		$query->execute($args);
		$args = array('test', 99, 'target4', \OCP\Share::SHARE_TYPE_GROUP, $this->group1->getGID(), $this->user4->getUID());
		$query->execute($args);

		// user2 is in group1 and group2
		$result1 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user2->getUID(), $this->user1->getUID());
		$this->assertSame(2, count($result1));
		$this->verifyResult($result1, array('target1', 'target2'));

		$result2 = \OCP\Share::getItemSharedWithUser('test', 99, null, $this->user1->getUID());
		$this->assertSame(2, count($result2));
		$this->verifyResult($result2, array('target1', 'target2'));

		// user3 is in group1 and group2
		$result3 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user3->getUID());
		$this->assertSame(3, count($result3));
		$this->verifyResult($result3, array('target1', 'target3', 'target4'));

		$result4 = \OCP\Share::getItemSharedWithUser('test', 99, null, null);
		$this->assertSame(4, count($result4));
		$this->verifyResult($result4, array('target1', 'target2', 'target3', 'target4'));

		$result6 = \OCP\Share::getItemSharedWithUser('test', 99, $this->user6->getUID(), null);
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
		\OC\Share\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_GROUP, $this->group1->getGID(), \OCP\Constants::PERMISSION_READ);

		$result = \OC\Share\Share::getItemsSharedWithUser('test', $this->user2->getUID());
		$this->assertCount(1, $result);

		$groupShareId = array_keys($result)[0];

		// remove user from group
		$userObject = \OC::$server->getUserManager()->get($this->user2->getUID());
		\OC::$server->getGroupManager()->get($this->group1->getGID())->removeUser($userObject);

		$result = \OC\Share\Share::getItemsSharedWithUser('test', $this->user2->getUID());
		$this->assertCount(0, $result);

		// test with buggy data
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(2), // group sub-share
				'share_with' => $qb->expr()->literal($this->user2->getUID()),
				'parent' => $qb->expr()->literal($groupShareId),
				'uid_owner' => $qb->expr()->literal($this->user1->getUID()),
				'item_type' => $qb->expr()->literal('test'),
				'item_source' => $qb->expr()->literal('test.txt'),
				'item_target' => $qb->expr()->literal('test.txt'),
				'file_target' => $qb->expr()->literal('test2.txt'),
				'permissions' => $qb->expr()->literal(1),
				'stime' => $qb->expr()->literal(time()),
			])->execute();

		$result = \OC\Share\Share::getItemsSharedWithUser('test', $this->user2->getUID());
		$this->assertCount(0, $result);

		$qb->delete('share')->execute();
	}

	public function testShareItemWithLinkAndDefaultExpireDate() {
		\OC_User::setUserId($this->user1->getUID());

		$config = \OC::$server->getConfig();

		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_expire_after_n_days', '2');

		$token = \OC\Share\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
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
		\OC_User::setUserId($this->user1->getUID());
		\OC\Share\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, $remoteId, \OCP\Constants::PERMISSION_ALL);
	}

	/**
	 * @dataProvider checkPasswordProtectedShareDataProvider
	 * @param $expected
	 * @param $item
	 */
	public function testCheckPasswordProtectedShare($expected, $item) {
		\OC::$server->getSession()->set('public_link_authenticated', '100');
		$result = \OC\Share\Share::checkPasswordProtectedShare($item);
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
	 * @param string $url
	 * @param string $expectedResult
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
			->with($this->stringStartsWith('https://' . $urlHost . '/ocs/v2.php/cloud/shares'), $this->anything())
			->willReturn(['success' => false, 'result' => 'Exception']);
		$httpHelperMock->expects($this->at(1))
			->method('post')
			->with($this->stringStartsWith('http://' . $urlHost . '/ocs/v2.php/cloud/shares'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OC\Share\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, $shareWith, \OCP\Constants::PERMISSION_READ);
		$shares = \OCP\Share::getItemShared('test', 'test.txt');
		$share = array_shift($shares);

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://' . $urlHost . '/ocs/v2.php/cloud/shares/' . $share['id'] . '/unshare'), $this->anything())
			->willReturn(['success' => false, 'result' => 'Exception']);
		$httpHelperMock->expects($this->at(1))
			->method('post')
			->with($this->stringStartsWith('http://' . $urlHost . '/ocs/v2.php/cloud/shares/' . $share['id'] . '/unshare'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OC\Share\Share::unshare('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, $shareWith);
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
	 * Make sure that a user cannot have multiple identical shares to remote users
	 */
	public function testOnlyOneRemoteShare() {
		$httpHelperMock = $this->getMockBuilder('OC\HTTPHelper')
			->disableOriginalConstructor()
			->getMock();
		$this->overwriteService('HTTPHelper', $httpHelperMock);

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://localhost/ocs/v2.php/cloud/shares'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OC\Share\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, 'foo@localhost', \OCP\Constants::PERMISSION_READ);
		$shares = \OCP\Share::getItemShared('test', 'test.txt');
		$share = array_shift($shares);

		//Try share again
		try {
			\OC\Share\Share::shareItem('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, 'foo@localhost', \OCP\Constants::PERMISSION_READ);
			$this->fail('Identical remote shares are not allowed');
		} catch (\Exception $e) {
			$this->assertEquals('Sharing test.txt failed, because this item is already shared with foo@localhost', $e->getMessage());
		}

		$httpHelperMock->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('https://localhost/ocs/v2.php/cloud/shares/' . $share['id'] . '/unshare'), $this->anything())
			->willReturn(['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])]);

		\OC\Share\Share::unshare('test', 'test.txt', \OCP\Share::SHARE_TYPE_REMOTE, 'foo@localhost');
		$this->restoreService('HTTPHelper');
	}

	/**
	 * Test case for #17560
	 */
	public function testAccesToSharedSubFolder() {
		\OC_User::setUserId($this->user1->getUID());
		$view = new \OC\Files\View('/' . $this->user1->getUID() . '/');
		$view->mkdir('files/folder1');

		$fileInfo = $view->getFileInfo('files/folder1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2->getUID(), \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 2.'
		);
		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user3->getUID(), \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 3.'
		);

		$view->mkdir('files/folder1/folder2');

		$fileInfo = $view->getFileInfo('files/folder1/folder2');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user4->getUID(), \OCP\Constants::PERMISSION_ALL),
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
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user5->getUID(), \OCP\Constants::PERMISSION_ALL),
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
		\OC_User::setUserId($this->user1->getUID());
		$view = new \OC\Files\View('/' . $this->user1->getUID() . '/');
		$view->mkdir('files/folder1');

		$fileInfo = $view->getFileInfo('files/folder1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		try {
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user1->getUID(), \OCP\Constants::PERMISSION_ALL);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing /folder1 failed, because you can not share with yourself', $e->getMessage());
		}
	}


	public function testShareWithOwnerError() {
		\OC_User::setUserId($this->user1->getUID());
		$view = new \OC\Files\View('/' . $this->user1->getUID() . '/');
		$view->mkdir('files/folder1');

		$fileInfo = $view->getFileInfo('files/folder1');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$fileId = $fileInfo->getId();

		$this->assertTrue(
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user2->getUID(), \OCP\Constants::PERMISSION_ALL),
			'Failed asserting that user 1 successfully shared "test" with user 2.'
		);

		\OC_User::setUserId($this->user2->getUID());
		try {
			\OC\Share\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, $this->user1->getUID(), \OCP\Constants::PERMISSION_ALL);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertEquals('Sharing failed, because the user ' . $this->user1->getUID() . ' is the original sharer', $e->getMessage());
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
