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

abstract class Test_Share_Backend extends UnitTestCase {

	protected $userBackend;
	protected $user1;
	protected $user2;
	protected $groupBackend;
	protected $group;
	protected $itemType;
	protected $item;

	public function setUp() {
		OC_User::clearBackends();
		OC_User::useBackend('dummy');
		$this->user1 = uniqid('user_');
		$this->user2 = uniqid('user_');
		OC_User::createUser($this->user1, 'pass1');
		OC_User::createUser($this->user2, 'pass2');
		OC_Group::clearBackends();
		OC_Group::useBackend(new OC_Group_Dummy);
		$this->group = uniqid('group_');
		OC_Group::createGroup($this->group);
	}

	public function testShareWithUserNonExistentItem() {
		$this->assertFalse(OCP\Share::share($this->itemType, uniqid('foobar_'), OCP\Share::SHARETYPE_USER, $this->user2, OCP\Share::PERMISSION_READ));
	}

	public function testShareWithUserItem() {
		$this->assertTrue(OCP\Share::share($this->itemType, $this->item, OCP\Share::SHARETYPE_USER, $this->user2, OCP\Share::PERMISSION_READ));
	}

	public function testShareWithGroupNonExistentItem() {
		$this->assertFalse(OCP\Share::share($this->itemType, uniqid('foobar_'), OCP\Share::SHARETYPE_GROUP, $this->group, OCP\Share::PERMISSION_READ));
	}

	public function testShareWithGroupItem() {
		$this->assertTrue(OCP\Share::share($this->itemType, $this->item, OCP\Share::SHARETYPE_GROUP, $this->group, OCP\Share::PERMISSION_READ));
	}

	public function testShareWithUserAlreadySharedWith() {
		$this->assertTrue(OCP\Share::share($this->itemType, $this->item, OCP\Share::SHARETYPE_USER, $this->user2, OCP\Share::PERMISSION_READ));
		$this->assertFalse(OCP\Share::share($this->itemType, $this->item, OCP\Share::SHARETYPE_USER, $this->user2, OCP\Share::PERMISSION_READ));
	}

}
