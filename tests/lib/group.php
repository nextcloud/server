<?php
/**
* ownCloud
*
* @author Robin Appelman
* @author Bernhard Posselt
* @copyright 2012 Robin Appelman icewind@owncloud.com
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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
*
*/

class Test_Group extends UnitTestCase {
	function setUp() {
		OC_Group::clearBackends();
	}

	function testSingleBackend() {
		OC_Group::useBackend(new OC_Group_Dummy());
		
		$group1=uniqid();
		$group2=uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);

		$user1=uniqid();
		$user2=uniqid();

		$this->assertFalse(OC_Group::inGroup($user1, $group1));
		$this->assertFalse(OC_Group::inGroup($user2, $group1));
		$this->assertFalse(OC_Group::inGroup($user1, $group2));
		$this->assertFalse(OC_Group::inGroup($user2, $group2));

		$this->assertTrue(OC_Group::addToGroup($user1, $group1));

		$this->assertTrue(OC_Group::inGroup($user1, $group1));
		$this->assertFalse(OC_Group::inGroup($user2, $group1));
		$this->assertFalse(OC_Group::inGroup($user1, $group2));
		$this->assertFalse(OC_Group::inGroup($user2, $group2));

		$this->assertFalse(OC_Group::addToGroup($user1, $group1));

		$this->assertEqual(array($user1), OC_Group::usersInGroup($group1));
		$this->assertEqual(array(), OC_Group::usersInGroup($group2));

		$this->assertEqual(array($group1), OC_Group::getUserGroups($user1));
		$this->assertEqual(array(), OC_Group::getUserGroups($user2));

		OC_Group::deleteGroup($group1);
		$this->assertEqual(array(), OC_Group::getUserGroups($user1));
		$this->assertEqual(array(), OC_Group::usersInGroup($group1));
		$this->assertFalse(OC_Group::inGroup($user1, $group1));
	}


	public function testNoEmptyGIDs(){
		OC_Group::useBackend(new OC_Group_Dummy());
		$emptyGroup = null;

		$this->assertEqual(false, OC_Group::createGroup($emptyGroup));
	}


	public function testNoGroupsTwice(){
		OC_Group::useBackend(new OC_Group_Dummy());
		$group = uniqid();
		OC_Group::createGroup($group);

		$groupCopy = $group;

		$this->assertEqual(false, OC_Group::createGroup($groupCopy));
		$this->assertEqual(array($group), OC_Group::getGroups());
	}


	public function testDontDeleteAdminGroup(){
		OC_Group::useBackend(new OC_Group_Dummy());
		$adminGroup = 'admin';
		OC_Group::createGroup($adminGroup);

		$this->assertEqual(false, OC_Group::deleteGroup($adminGroup));
		$this->assertEqual(array($adminGroup), OC_Group::getGroups());	
	}


	public function testDontAddUserToNonexistentGroup(){
		OC_Group::useBackend(new OC_Group_Dummy());
		$groupNonExistent = 'notExistent';
		$user = uniqid();

		$this->assertEqual(false, OC_Group::addToGroup($user, $groupNonExistent));
		$this->assertEqual(array(), OC_Group::getGroups());
	}


	public function testUsersInGroup(){
		OC_Group::useBackend(new OC_Group_Dummy());
		$group1 = uniqid();
		$group2 = uniqid();
		$group3 = uniqid();
		$user1 = uniqid();
		$user2 = uniqid();
		$user3 = uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);
		OC_Group::createGroup($group3);

		OC_Group::addToGroup($user1, $group1);
		OC_Group::addToGroup($user2, $group1);
		OC_Group::addToGroup($user3, $group1);
		OC_Group::addToGroup($user3, $group2);

		$this->assertEqual(array($user1, $user2, $user3), 
					OC_Group::usersInGroups(array($group1, $group2, $group3)));

		// FIXME: needs more parameter variation
	}



	function testMultiBackend() {
		$backend1=new OC_Group_Dummy();
		$backend2=new OC_Group_Dummy();
		OC_Group::useBackend($backend1);
		OC_Group::useBackend($backend2);

		$group1=uniqid();
		$group2=uniqid();
		OC_Group::createGroup($group1);

		//groups should be added to the first registered backend
		$this->assertEqual(array($group1), $backend1->getGroups());
		$this->assertEqual(array(), $backend2->getGroups());

		$this->assertEqual(array($group1), OC_Group::getGroups());
		$this->assertTrue(OC_Group::groupExists($group1));
		$this->assertFalse(OC_Group::groupExists($group2));

		$backend1->createGroup($group2);

		$this->assertEqual(array($group1, $group2), OC_Group::getGroups());
		$this->assertTrue(OC_Group::groupExists($group1));
		$this->assertTrue(OC_Group::groupExists($group2));

		$user1=uniqid();
		$user2=uniqid();

		$this->assertFalse(OC_Group::inGroup($user1, $group1));
		$this->assertFalse(OC_Group::inGroup($user2, $group1));


		$this->assertTrue(OC_Group::addToGroup($user1, $group1));

		$this->assertTrue(OC_Group::inGroup($user1, $group1));
		$this->assertFalse(OC_Group::inGroup($user2, $group1));
		$this->assertFalse($backend2->inGroup($user1, $group1));

		$this->assertFalse(OC_Group::addToGroup($user1, $group1));

		$this->assertEqual(array($user1), OC_Group::usersInGroup($group1));

		$this->assertEqual(array($group1), OC_Group::getUserGroups($user1));
		$this->assertEqual(array(), OC_Group::getUserGroups($user2));

		OC_Group::deleteGroup($group1);
		$this->assertEqual(array(), OC_Group::getUserGroups($user1));
		$this->assertEqual(array(), OC_Group::usersInGroup($group1));
		$this->assertFalse(OC_Group::inGroup($user1, $group1));
	}
}
