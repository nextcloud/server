<?php
/**
* ownCloud
*
* @author Thomas Tanghus
* @copyright 2012 Thomas Tanghus (thomas@tanghus.net)
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

//require_once("../lib/template.php");

class Test_VCategories extends UnitTestCase {

	protected $objectType;
	protected $user;
	protected $backupGlobals = FALSE;

	public function setUp() {

		OC_User::clearBackends();
		OC_User::useBackend('dummy');
		$this->user = uniqid('user_');
		$this->objectType = uniqid('type_');
		OC_User::createUser($this->user, 'pass');
		OC_User::setUserId($this->user);

	}

	public function tearDown() {
		//$query = OC_DB::prepare('DELETE FROM `*PREFIX*vcategories` WHERE `item_type` = ?');
		//$query->execute(array('test'));
	}

	public function testInstantiateWithDefaults() {
		$defcategories = array('Friends', 'Family', 'Work', 'Other');

		$catmgr = new OC_VCategories($this->objectType, $this->user, $defcategories);

		$this->assertEqual(count($catmgr->categories()), 4);
	}

	public function testAddCategories() {
		$categories = array('Friends', 'Family', 'Work', 'Other');

		$catmgr = new OC_VCategories($this->objectType, $this->user, $defcategories);

		foreach($categories as $category) {
			$result = $catmgr->add($category);
			$this->assertTrue($result);
		}

		$this->assertFalse($catmgr->add('Family'));
		$this->assertFalse($catmgr->add('fAMILY'));

		$this->assertEqual(count($catmgr->categories()), 4);
	}

	public function testdeleteCategories() {
		$defcategories = array('Friends', 'Family', 'Work', 'Other');
		$catmgr = new OC_VCategories($this->objectType, $this->user, $defcategories);
		$this->assertEqual(count($catmgr->categories()), 4);

		$catmgr->delete('family');
		$this->assertEqual(count($catmgr->categories()), 3);

		$catmgr->delete(array('Friends', 'Work', 'Other'));
		$this->assertEqual(count($catmgr->categories()), 0);

	}

	public function testAddToCategory() {
		$objids = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

		$catmgr = new OC_VCategories($this->objectType, $this->user);

		foreach($objids as $id) {
			$catmgr->addToCategory($id, 'Family');
		}

		$this->assertEqual(count($catmgr->categories()), 1);
		$this->assertEqual(count($catmgr->idsForCategory('Family')), 9);
	}

	public function testRemoveFromCategory() {
		$objids = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

		// Is this "legal"?
		$this->testAddToCategory();
		$catmgr = new OC_VCategories($this->objectType, $this->user);

		foreach($objids as $id) {
			$this->assertTrue(in_array($id, $catmgr->idsForCategory('Family')));
			$catmgr->removeFromCategory($id, 'Family');
			$this->assertFalse(in_array($id, $catmgr->idsForCategory('Family')));
		}

		$this->assertEqual(count($catmgr->categories()), 1);
		$this->assertEqual(count($catmgr->idsForCategory('Family')), 0);
	}

}
