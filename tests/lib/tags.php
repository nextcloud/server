<?php
/**
* ownCloud
*
* @author Thomas Tanghus
* @copyright 2012-13 Thomas Tanghus (thomas@tanghus.net)
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

class Test_Tags extends PHPUnit_Framework_TestCase {

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
		$defaultTags = array('Friends', 'Family', 'Work', 'Other');

		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType, $defaultTags);

		$this->assertEquals(4, count($tagMgr->getTags()));
	}

	public function testAddTags() {
		$tags = array('Friends', 'Family', 'Work', 'Other');

		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType);

		foreach($tags as $tag) {
			$result = $tagMgr->add($tag);
			$this->assertTrue((bool)$result);
		}

		$this->assertFalse($tagMgr->add('Family'));
		$this->assertFalse($tagMgr->add('fAMILY'));

		$this->assertEquals(4, count($tagMgr->getTags()));
	}

	public function testAddMultiple() {
		$tags = array('Friends', 'Family', 'Work', 'Other');

		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType);

		foreach($tags as $tag) {
			$this->assertFalse($tagMgr->hasTag($tag));
		}

		$result = $tagMgr->addMultiple($tags);
		$this->assertTrue((bool)$result);

		foreach($tags as $tag) {
			$this->assertTrue($tagMgr->hasTag($tag));
		}

		$this->assertEquals(4, count($tagMgr->getTags()));
	}

	public function testIsEmpty() {
		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType);

		$this->assertEquals(0, count($tagMgr->getTags()));
		$this->assertTrue($tagMgr->isEmpty());
		$this->assertNotEquals(false, $tagMgr->add('Tag'));
		$this->assertFalse($tagMgr->isEmpty());
	}

	public function testdeleteTags() {
		$defaultTags = array('Friends', 'Family', 'Work', 'Other');
		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType, $defaultTags);

		$this->assertEquals(4, count($tagMgr->getTags()));

		$tagMgr->delete('family');
		$this->assertEquals(3, count($tagMgr->getTags()));

		$tagMgr->delete(array('Friends', 'Work', 'Other'));
		$this->assertEquals(0, count($tagMgr->getTags()));

	}

	public function testRenameTag() {
		$defaultTags = array('Friends', 'Family', 'Wrok', 'Other');
		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType, $defaultTags);

		$this->assertTrue($tagMgr->rename('Wrok', 'Work'));
		$this->assertTrue($tagMgr->hasTag('Work'));
		$this->assertFalse($tagMgr->hastag('Wrok'));
		$this->assertFalse($tagMgr->rename('Wrok', 'Work'));

	}

	public function testTagAs() {
		$objids = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType);

		foreach($objids as $id) {
			$tagMgr->tagAs($id, 'Family');
		}

		$this->assertEquals(1, count($tagMgr->getTags()));
		$this->assertEquals(9, count($tagMgr->getIdsForTag('Family')));
	}

	/**
	* @depends testTagAs
	*/
	public function testUnTag() {
		$objIds = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

		// Is this "legal"?
		$this->testTagAs();
		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType);

		foreach($objIds as $id) {
			$this->assertTrue(in_array($id, $tagMgr->getIdsForTag('Family')));
			$tagMgr->unTag($id, 'Family');
			$this->assertFalse(in_array($id, $tagMgr->getIdsForTag('Family')));
		}

		$this->assertEquals(1, count($tagMgr->getTags()));
		$this->assertEquals(0, count($tagMgr->getIdsForTag('Family')));
	}

	public function testFavorite() {
		$tagMgr = new OC\Tags($this->user);
		$tagMgr->loadTagsFor($this->objectType);
		$this->assertTrue($tagMgr->addToFavorites(1));
		$this->assertTrue($tagMgr->removeFromFavorites(1));
	}

}
