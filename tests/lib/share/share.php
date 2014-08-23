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

class Test_Share extends PHPUnit_Framework_TestCase {

	protected $itemType;
	protected $userBackend;
	protected $user1;
	protected $user2;
	protected $user3;
	protected $user4;
	protected $groupBackend;
	protected $group1;
	protected $group2;
	protected $resharing;
	protected $dateInFuture;
	protected $dateInPast;

	public function setUp() {
		OC_User::clearBackends();
		OC_User::useBackend('dummy');
		$this->user1 = uniqid('user1_');
		$this->user2 = uniqid('user2_');
		$this->user3 = uniqid('user3_');
		$this->user4 = uniqid('user4_');
		OC_User::createUser($this->user1, 'pass');
		OC_User::createUser($this->user2, 'pass');
		OC_User::createUser($this->user3, 'pass');
		OC_User::createUser($this->user4, 'pass');
		OC_User::setUserId($this->user1);
		OC_Group::clearBackends();
		OC_Group::useBackend(new OC_Group_Dummy);
		$this->group1 = uniqid('group_');
		$this->group2 = uniqid('group_');
		OC_Group::createGroup($this->group1);
		OC_Group::createGroup($this->group2);
		OC_Group::addToGroup($this->user1, $this->group1);
		OC_Group::addToGroup($this->user2, $this->group1);
		OC_Group::addToGroup($this->user3, $this->group1);
		OC_Group::addToGroup($this->user2, $this->group2);
		OC_Group::addToGroup($this->user4, $this->group2);
		OCP\Share::registerBackend('test', 'Test_Share_Backend');
		OC_Hook::clear('OCP\\Share');
		OC::registerShareHooks();
		$this->resharing = OC_Appconfig::getValue('core', 'shareapi_allow_resharing', 'yes');
		OC_Appconfig::setValue('core', 'shareapi_allow_resharing', 'yes');

		// 20 Minutes in the past, 20 minutes in the future.
		$now = time();
		$dateFormat = 'Y-m-d H:i:s';
		$this->dateInPast = date($dateFormat, $now - 20 * 60);
		$this->dateInFuture = date($dateFormat, $now + 20 * 60);
	}

	public function tearDown() {
		$query = OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `item_type` = ?');
		$query->execute(array('test'));
		OC_Appconfig::setValue('core', 'shareapi_allow_resharing', $this->resharing);
	}

	public function testShareInvalidShareType() {
		$message = 'Share type foobar is not valid for test.txt';
		try {
			OCP\Share::shareItem('test', 'test.txt', 'foobar', $this->user2, OCP\PERMISSION_READ);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
	}

	public function testInvalidItemType() {
		$message = 'Sharing backend for foobar not found';
		try {
			OCP\Share::shareItem('foobar', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			OCP\Share::getItemsSharedWith('foobar');
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			OCP\Share::getItemSharedWith('foobar', 'test.txt');
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			OCP\Share::getItemSharedWithBySource('foobar', 'test.txt');
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			OCP\Share::getItemShared('foobar', 'test.txt');
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			OCP\Share::unshare('foobar', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		try {
			OCP\Share::setPermissions('foobar', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_UPDATE);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
	}

	protected function shareUserOneTestFileWithUserTwo() {
		OC_User::setUserId($this->user1);
		$this->assertTrue(
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ),
			'Failed asserting that user 1 successfully shared text.txt with user 2.'
		);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemShared('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of user 1.'
		);

		OC_User::setUserId($this->user2);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that user 2 has access to test.txt after initial sharing.'
		);
	}

	protected function shareUserTestFileAsLink() {
		OC_User::setUserId($this->user1);
		$result = OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_LINK, null, OCP\PERMISSION_READ);
		$this->assertTrue(is_string($result));
	}

	/**
	 * @param string $sharer
	 * @param string $receiver
	 */
	protected function shareUserTestFileWithUser($sharer, $receiver) {
		OC_User::setUserId($sharer);
		$this->assertTrue(
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $receiver, OCP\PERMISSION_READ | OCP\PERMISSION_SHARE),
			'Failed asserting that ' . $sharer . ' successfully shared text.txt with ' . $receiver . '.'
		);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemShared('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of ' . $sharer . '.'
		);

		OC_User::setUserId($receiver);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that ' . $receiver . ' has access to test.txt after initial sharing.'
		);
	}

	public function testShareWithUser() {
		// Invalid shares
		$message = 'Sharing test.txt failed, because the user '.$this->user1.' is the item owner';
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user1, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		$message = 'Sharing test.txt failed, because the user foobar does not exist';
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, 'foobar', OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		$message = 'Sharing foobar failed, because the sharing backend for test could not find its source';
		try {
			OCP\Share::shareItem('test', 'foobar', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Valid share
		$this->shareUserOneTestFileWithUserTwo();

		// Attempt to share again
		OC_User::setUserId($this->user1);
		$message = 'Sharing test.txt failed, because this item is already shared with '.$this->user2;
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Attempt to share back
		OC_User::setUserId($this->user2);
		$message = 'Sharing test.txt failed, because the user '.$this->user1.' is the original sharer';
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user1, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Unshare
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::unshare('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2));

		// Attempt reshare without share permission
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ));
		OC_User::setUserId($this->user2);
		$message = 'Sharing test.txt failed, because resharing is not allowed';
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user3, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Owner grants share and update permission
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::setPermissions('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE | OCP\PERMISSION_SHARE));

		// Attempt reshare with escalated permissions
		OC_User::setUserId($this->user2);
		$message = 'Sharing test.txt failed, because the permissions exceed permissions granted to '.$this->user2;
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user3, OCP\PERMISSION_READ | OCP\PERMISSION_DELETE);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Valid reshare
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user3, OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE));
		$this->assertEquals(array('test.txt'), OCP\Share::getItemShared('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE));
		OC_User::setUserId($this->user3);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE));
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));

		// Attempt to escalate permissions
		OC_User::setUserId($this->user2);
		$message = 'Setting permissions for test.txt failed, because the permissions exceed permissions granted to '.$this->user2;
		try {
			OCP\Share::setPermissions('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user3, OCP\PERMISSION_READ | OCP\PERMISSION_DELETE);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Remove update permission
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::setPermissions('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ | OCP\PERMISSION_SHARE));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_SHARE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));
		OC_User::setUserId($this->user3);
		$this->assertEquals(array(OCP\PERMISSION_READ), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));

		// Remove share permission
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::setPermissions('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array(OCP\PERMISSION_READ), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));
		OC_User::setUserId($this->user3);
		$this->assertSame(array(), OCP\Share::getItemSharedWith('test', 'test.txt'));

		// Reshare again, and then have owner unshare
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::setPermissions('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ | OCP\PERMISSION_SHARE));
		OC_User::setUserId($this->user2);
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user3, OCP\PERMISSION_READ));
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::unshare('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2));
		OC_User::setUserId($this->user2);
		$this->assertSame(array(), OCP\Share::getItemSharedWith('test', 'test.txt'));
		OC_User::setUserId($this->user3);
		$this->assertSame(array(), OCP\Share::getItemSharedWith('test', 'test.txt'));

		// Attempt target conflict
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ));
		OC_User::setUserId($this->user3);
		$this->assertTrue(OCP\Share::shareItem('test', 'share.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ));

		OC_User::setUserId($this->user2);
		$to_test = OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET);
		$this->assertEquals(2, count($to_test));
		$this->assertTrue(in_array('test.txt', $to_test));
		$this->assertTrue(in_array('test1.txt', $to_test));

		// Unshare from self
		$this->assertTrue(OCP\Share::unshareFromSelf('test', 'test.txt'));
		$this->assertEquals(array('test1.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Unshare from self via source
		$this->assertTrue(OCP\Share::unshareFromSelf('test', 'share.txt', true));
		$this->assertEquals(array(), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ));
		OC_User::setUserId($this->user3);
		$this->assertTrue(OCP\Share::shareItem('test', 'share.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ));

		OC_User::setUserId($this->user2);
		$to_test = OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET);
		$this->assertEquals(2, count($to_test));
		$this->assertTrue(in_array('test.txt', $to_test));
		$this->assertTrue(in_array('test1.txt', $to_test));

		// Remove user
		OC_User::setUserId($this->user1);
		OC_User::deleteUser($this->user1);
		OC_User::setUserId($this->user2);
		$this->assertEquals(array('test1.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
	}

	public function testShareWithUserExpirationExpired() {
		OC_User::setUserId($this->user1);
		$this->shareUserOneTestFileWithUserTwo();
		$this->shareUserTestFileAsLink();

		$this->assertTrue(
			OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInPast),
			'Failed asserting that user 1 successfully set an expiration date for the test.txt share.'
		);

		$shares = OCP\Share::getItemsShared('test');
		$this->assertSame(1, count($shares));
		$share = reset($shares);
		$this->assertSame(\OCP\Share::SHARE_TYPE_USER, $share['share_type']);
	}

	public function testShareWithUserExpirationValid() {
		OC_User::setUserId($this->user1);
		$this->shareUserOneTestFileWithUserTwo();
		$this->shareUserTestFileAsLink();


		$this->assertTrue(
			OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInFuture),
			'Failed asserting that user 1 successfully set an expiration date for the test.txt share.'
		);

		$shares = OCP\Share::getItemsShared('test');
		$this->assertSame(2, count($shares));

	}

	protected function shareUserOneTestFileWithGroupOne() {
		OC_User::setUserId($this->user1);
		$this->assertTrue(
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1, OCP\PERMISSION_READ),
			'Failed asserting that user 1 successfully shared text.txt with group 1.'
		);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemShared('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that test.txt is a shared file of user 1.'
		);

		OC_User::setUserId($this->user2);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that user 2 has access to test.txt after initial sharing.'
		);

		OC_User::setUserId($this->user3);
		$this->assertContains(
			'test.txt',
			OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that user 3 has access to test.txt after initial sharing.'
		);
	}

	public function testShareWithGroup() {
		// Invalid shares
		$message = 'Sharing test.txt failed, because the group foobar does not exist';
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, 'foobar', OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		$policy = OC_Appconfig::getValue('core', 'shareapi_only_share_with_group_members', 'no');
		OC_Appconfig::setValue('core', 'shareapi_only_share_with_group_members', 'yes');
		$message = 'Sharing test.txt failed, because '.$this->user1.' is not a member of the group '.$this->group2;
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group2, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}
		OC_Appconfig::setValue('core', 'shareapi_only_share_with_group_members', $policy);

		// Valid share
		$this->shareUserOneTestFileWithGroupOne();

		// Attempt to share again
		OC_User::setUserId($this->user1);
		$message = 'Sharing test.txt failed, because this item is already shared with '.$this->group1;
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Attempt to share back to owner of group share
		OC_User::setUserId($this->user2);
		$message = 'Sharing test.txt failed, because the user '.$this->user1.' is the original sharer';
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user1, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Attempt to share back to group
		$message = 'Sharing test.txt failed, because this item is already shared with '.$this->group1;
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Attempt to share back to member of group
		$message ='Sharing test.txt failed, because this item is already shared with '.$this->user3;
		try {
			OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user3, OCP\PERMISSION_READ);
			$this->fail('Exception was expected: '.$message);
		} catch (Exception $exception) {
			$this->assertEquals($message, $exception->getMessage());
		}

		// Unshare
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::unshare('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1));

		// Valid share with same person - user then group
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ | OCP\PERMISSION_DELETE | OCP\PERMISSION_SHARE));
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1, OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE | OCP\PERMISSION_DELETE | OCP\PERMISSION_SHARE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));
		OC_User::setUserId($this->user3);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));

		// Valid reshare
		OC_User::setUserId($this->user2);
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user4, OCP\PERMISSION_READ));
		OC_User::setUserId($this->user4);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Unshare from user only
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::unshare('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));
		OC_User::setUserId($this->user4);
		$this->assertEquals(array(), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Valid share with same person - group then user
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_USER, $this->user2, OCP\PERMISSION_READ | OCP\PERMISSION_DELETE));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE | OCP\PERMISSION_DELETE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));

		// Unshare from group only
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::unshare('test', 'test.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array(OCP\PERMISSION_READ | OCP\PERMISSION_DELETE), OCP\Share::getItemSharedWith('test', 'test.txt', Test_Share_Backend::FORMAT_PERMISSIONS));

		// Attempt user specific target conflict
		OC_User::setUserId($this->user3);
		$this->assertTrue(OCP\Share::shareItem('test', 'share.txt', OCP\Share::SHARE_TYPE_GROUP, $this->group1, OCP\PERMISSION_READ | OCP\PERMISSION_SHARE));
		OC_User::setUserId($this->user2);
		$to_test = OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET);
		$this->assertEquals(2, count($to_test));
		$this->assertTrue(in_array('test.txt', $to_test));
		$this->assertTrue(in_array('test1.txt', $to_test));

		// Valid reshare
		$this->assertTrue(OCP\Share::shareItem('test', 'share.txt', OCP\Share::SHARE_TYPE_USER, $this->user4, OCP\PERMISSION_READ | OCP\PERMISSION_SHARE));
		OC_User::setUserId($this->user4);
		$this->assertEquals(array('test1.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Remove user from group
		OC_Group::removeFromGroup($this->user2, $this->group1);
		OC_User::setUserId($this->user2);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
		OC_User::setUserId($this->user4);
		$this->assertEquals(array(), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Add user to group
		OC_Group::addToGroup($this->user4, $this->group1);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Unshare from self
		$this->assertTrue(OCP\Share::unshareFromSelf('test', 'test.txt'));
		$this->assertEquals(array(), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
		OC_User::setUserId($this->user2);
		$this->assertEquals(array('test.txt'), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Unshare from self via source
		OC_User::setUserId($this->user1);
		$this->assertTrue(OCP\Share::unshareFromSelf('test', 'share.txt', true));
		$this->assertEquals(array(), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));

		// Remove group
		OC_Group::deleteGroup($this->group1);
		OC_User::setUserId($this->user4);
		$this->assertEquals(array(), OCP\Share::getItemsSharedWith('test', Test_Share_Backend::FORMAT_TARGET));
		OC_User::setUserId($this->user3);
		$this->assertEquals(array(), OCP\Share::getItemsShared('test'));
	}

	/**
	 * @param boolean|string $token
	 */
	protected function getShareByValidToken($token) {
		$row = OCP\Share::getShareByToken($token);
		$this->assertInternalType(
			'array',
			$row,
			"Failed asserting that a share for token $token exists."
		);
		return $row;
	}

	public function testShareItemWithLink() {
		OC_User::setUserId($this->user1);
		$token = OCP\Share::shareItem('test', 'test.txt', OCP\Share::SHARE_TYPE_LINK, null, OCP\PERMISSION_READ);
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
			OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInFuture),
			'Failed asserting that user 1 successfully set a future expiration date for the test.txt share.'
		);
		$row = $this->getShareByValidToken($token);
		$this->assertNotEmpty(
			$row['expiration'],
			'Failed asserting that the returned row has an expiration date.'
		);

		// testGetShareByTokenExpirationExpired
		$this->assertTrue(
			OCP\Share::setExpirationDate('test', 'test.txt', $this->dateInPast),
			'Failed asserting that user 1 successfully set a past expiration date for the test.txt share.'
		);
		$this->assertFalse(
			OCP\Share::getShareByToken($token),
			'Failed asserting that an expired share could not be found.'
		);
	}

	public function testUnshareAll() {
		$this->shareUserTestFileWithUser($this->user1, $this->user2);
		$this->shareUserTestFileWithUser($this->user2, $this->user3);
		$this->shareUserTestFileWithUser($this->user3, $this->user4);
		$this->shareUserOneTestFileWithGroupOne();

		OC_User::setUserId($this->user1);
		$this->assertEquals(
			array('test.txt', 'test.txt'),
			OCP\Share::getItemsShared('test', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that the test.txt file is shared exactly two times by user1.'
		);

		OC_User::setUserId($this->user2);
		$this->assertEquals(
			array('test.txt'),
			OCP\Share::getItemsShared('test', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that the test.txt file is shared exactly once by user2.'
		);

		OC_User::setUserId($this->user3);
		$this->assertEquals(
			array('test.txt'),
			OCP\Share::getItemsShared('test', Test_Share_Backend::FORMAT_SOURCE),
			'Failed asserting that the test.txt file is shared exactly once by user3.'
		);

		$this->assertTrue(
			OCP\Share::unshareAll('test', 'test.txt'),
			'Failed asserting that user 3 successfully unshared all shares of the test.txt share.'
		);

		$this->assertEquals(
			array(),
			OCP\Share::getItemsShared('test'),
			'Failed asserting that the share of the test.txt file by user 3 has been removed.'
		);

		OC_User::setUserId($this->user1);
		$this->assertEquals(
			array(),
			OCP\Share::getItemsShared('test'),
			'Failed asserting that both shares of the test.txt file by user 1 have been removed.'
		);

		OC_User::setUserId($this->user2);
		$this->assertEquals(
			array(),
			OCP\Share::getItemsShared('test'),
			'Failed asserting that the share of the test.txt file by user 2 has been removed.'
		);
	}

	/**
	 * @dataProvider checkPasswordProtectedShareDataProvider
	 * @param $expected
	 * @param $item
	 */
	public function testCheckPasswordProtectedShare($expected, $item) {
		\OC::$session->set('public_link_authenticated', 100);
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
			array(true, array('share_with' => '1234567890', 'share_type' => '3', 'id' => 100)),
			array(true, array('share_with' => '1234567890', 'share_type' => 3, 'id' => 100)),
			array(false, array('share_with' => '1234567890', 'share_type' => '3', 'id' => 101)),
			array(false, array('share_with' => '1234567890', 'share_type' => 3, 'id' => 101)),
		);

		/*
		if (!isset($linkItem['share_with'])) {
			return true;
		}

		if ($linkItem['share_type'] != \OCP\Share::SHARE_TYPE_LINK) {
			return true;
		}

		if ( \OC::$session->exists('public_link_authenticated')
			&& \OC::$session->get('public_link_authenticated') === $linkItem['id'] ) {
			return true;
		}
		 * */
	}
}
