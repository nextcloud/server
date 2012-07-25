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

class Test_Share_Base extends UnitTestCase {

	protected $itemType;
	protected $userBackend;
	protected $user1;
	protected $user2;
	protected $groupBackend;
	protected $group1;
	protected $group2;


	public function setUp() {
		OC_User::clearBackends();
		OC_User::useBackend('dummy');
		$this->user1 = uniqid('user_');
		$this->user2 = uniqid('user_');
		OC_User::createUser($this->user1, 'pass1');
		OC_User::createUser($this->user2, 'pass2');
		OC_Group::clearBackends();
		OC_Group::useBackend(new OC_Group_Dummy);
		$this->group1 = uniqid('group_');
		$this->group2 = uniqid('group_');
		OC_Group::createGroup($this->group1);
		OC_Group::createGroup($this->group2);
	}

	public function testShareInvalidShareType() {
		$this->assertFalse(OCP\Share::share('file', 'test.txt', 'foobar', $this->user1, OCP\Share::PERMISSION_READ));
	}

	public function testShareInvalidItemType() {
		$this->assertFalse(OCP\Share::share('foobar', 'test.txt', OCP\Share::SHARETYPE_USER, $this->user1, OCP\Share::PERMISSION_READ));
	}

	public function testShareWithSelf() {
		OC_User::setUserId($this->user1);
		$this->assertFalse(OCP\Share::share('file', 'test.txt', OCP\Share::SHARETYPE_USER, $this->user1, OCP\Share::PERMISSION_READ));
	}

	public function testShareWithNonExistentUser() {
		$this->assertFalse(OCP\Share::share('file', 'test.txt', OCP\Share::SHARETYPE_USER, 'foobar', OCP\Share::PERMISSION_READ));
	}

	public function testShareWithUserOwnerNotPartOfGroup() {
		
	}

	public function testShareWithUserAlreadySharedWith() {
		
	}

	public function testShareWithNonExistentGroup() {
		$this->assertFalse(OCP\Share::share('file', 'test.txt', OCP\Share::SHARETYPE_GROUP, 'foobar', OCP\Share::PERMISSION_READ));
	}

	public function testShareWithGroupOwnerNotPartOfGroup() {

	}


	public function testShareWithGroupItem() {

	}

	public function testUnshareInvalidShareType() {

	}

	public function testUnshareNonExistentItem() {

	}

	public function testUnshareFromUserItem() {

	}

	public function testUnshareFromGroupItem() {

	}

	


	
		// Test owner not in same group (false)

		

		// Test non-existant item type

		// Test valid item

		// Test existing shared item (false)
		
		// Test unsharing item

		// Test setting permissions

		// Test setting permissions not as owner (false)

		// Test setting target

		// Test setting target as owner (false)
		
		// Spam reshares

		

		// Test non-existant group
		

		// Test owner not part of group

		// Test existing shared item with group

		// Test valid item, valid name for all users

		// Test unsharing item

		// Test item with name conflicts

		// Test unsharing item

		// Test setting permissions

		// Test setting target no name conflicts

		// Test setting target with conflicts

		// Spam reshares

		

		

	public function testPrivateLink() {
		
	}

}
