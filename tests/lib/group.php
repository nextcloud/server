<?php
/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2012 Robin Appelman icewind@owncloud.com
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
	function setUp(){
		OC_Group::clearBackends();
	}

	function testSingleBackend(){
		OC_Group::useBackend(new OC_Group_Dummy());
		
		$group1=uniqid();
		$group2=uniqid();
		OC_Group::createGroup($group1);
		OC_Group::createGroup($group2);

		$user1=uniqid();
		$user2=uniqid();

		$this->assertFalse(OC_Group::inGroup($user1,$group1));
		$this->assertFalse(OC_Group::inGroup($user2,$group1));
		$this->assertFalse(OC_Group::inGroup($user1,$group2));
		$this->assertFalse(OC_Group::inGroup($user2,$group2));

		$this->assertTrue(OC_Group::addToGroup($user1,$group1));

		$this->assertTrue(OC_Group::inGroup($user1,$group1));
		$this->assertFalse(OC_Group::inGroup($user2,$group1));
		$this->assertFalse(OC_Group::inGroup($user1,$group2));
		$this->assertFalse(OC_Group::inGroup($user2,$group2));

		$this->assertFalse(OC_Group::addToGroup($user1,$group1));

		$this->assertEqual(array($user1),OC_Group::usersInGroup($group1));
		$this->assertEqual(array(),OC_Group::usersInGroup($group2));

		$this->assertEqual(array($group1),OC_Group::getUserGroups($user1));
		$this->assertEqual(array(),OC_Group::getUserGroups($user2));

		OC_Group::deleteGroup($group1);
		$this->assertEqual(array(),OC_Group::getUserGroups($user1));
		$this->assertEqual(array(),OC_Group::usersInGroup($group1));
		$this->assertFalse(OC_Group::inGroup($user1,$group1));
	}

	function testMultiBackend(){
		$backend1=new OC_Group_Dummy();
		$backend2=new OC_Group_Dummy();
		OC_Group::useBackend($backend1);
		OC_Group::useBackend($backend2);

		$group1=uniqid();
		$group2=uniqid();
		OC_Group::createGroup($group1);

		//groups should be added to the first registered backend
		$this->assertEqual(array($group1),$backend1->getGroups());
		$this->assertEqual(array(),$backend2->getGroups());

		$this->assertEqual(array($group1),OC_Group::getGroups());
		$this->assertTrue(OC_Group::groupExists($group1));
		$this->assertFalse(OC_Group::groupExists($group2));

		$backend1->createGroup($group2);

		$this->assertEqual(array($group1,$group2),OC_Group::getGroups());
		$this->assertTrue(OC_Group::groupExists($group1));
		$this->assertTrue(OC_Group::groupExists($group2));

		$user1=uniqid();
		$user2=uniqid();

		$this->assertFalse(OC_Group::inGroup($user1,$group1));
		$this->assertFalse(OC_Group::inGroup($user2,$group1));


		$this->assertTrue(OC_Group::addToGroup($user1,$group1));

		$this->assertTrue(OC_Group::inGroup($user1,$group1));
		$this->assertFalse(OC_Group::inGroup($user2,$group1));
		$this->assertFalse($backend2->inGroup($user1,$group1));

		$this->assertFalse(OC_Group::addToGroup($user1,$group1));

		$this->assertEqual(array($user1),OC_Group::usersInGroup($group1));

		$this->assertEqual(array($group1),OC_Group::getUserGroups($user1));
		$this->assertEqual(array(),OC_Group::getUserGroups($user2));

		OC_Group::deleteGroup($group1);
		$this->assertEqual(array(),OC_Group::getUserGroups($user1));
		$this->assertEqual(array(),OC_Group::usersInGroup($group1));
		$this->assertFalse(OC_Group::inGroup($user1,$group1));
	}
}
