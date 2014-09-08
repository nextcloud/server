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
		$this->tagMapper = new OC\Tagging\TagMapper(new OC\AppFramework\Db\Db());
		$this->tagMgr = new OC\TagManager($this->tagMapper, $this->user);

	}

	public function tearDown() {
		//$query = OC_DB::prepare('DELETE FROM `*PREFIX*vcategories` WHERE `item_type` = ?');
		//$query->execute(array('test'));
	}

	public function testInstantiateWithDefaults() {
		$defaultTags = array('Friends', 'Family', 'Work', 'Other');

		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$this->assertEquals(4, count($tagger->getTags()));
	}

	public function testAddTags() {
		$tags = array('Friends', 'Family', 'Work', 'Other');

		$tagger = $this->tagMgr->load($this->objectType);

		foreach($tags as $tag) {
			$result = $tagger->add($tag);
			$this->assertGreaterThan(0, $result, 'add() returned an ID <= 0');
			$this->assertTrue((bool)$result);
		}

		$this->assertFalse($tagger->add('Family'));
		$this->assertFalse($tagger->add('fAMILY'));

		$this->assertCount(4, $tagger->getTags(), 'Wrong number of added tags');
	}

	public function testAddMultiple() {
		$tags = array('Friends', 'Family', 'Work', 'Other');

		$tagger = $this->tagMgr->load($this->objectType);

		foreach($tags as $tag) {
			$this->assertFalse($tagger->hasTag($tag));
		}

		$result = $tagger->addMultiple($tags);
		$this->assertTrue((bool)$result);

		foreach($tags as $tag) {
			$this->assertTrue($tagger->hasTag($tag));
		}

		$tagMaps = $tagger->getTags();
		$this->assertCount(4, $tagMaps, 'Not all tags added');
		foreach($tagMaps as $tagMap) {
			$this->assertEquals(null, $tagMap['id']);
		}

		// As addMultiple has been called without $sync=true, the tags aren't
		// saved to the database, so they're gone when we reload $tagger:

		$tagger = $this->tagMgr->load($this->objectType);
		$this->assertEquals(0, count($tagger->getTags()));

		// Now, we call addMultiple() with $sync=true so the tags will be
		// be saved to the database.
		$result = $tagger->addMultiple($tags, true);
		$this->assertTrue((bool)$result);

		$tagMaps = $tagger->getTags();
		foreach($tagMaps as $tagMap) {
			$this->assertNotEquals(null, $tagMap['id']);
		}

		// Reload the tagger.
		$tagger = $this->tagMgr->load($this->objectType);

		foreach($tags as $tag) {
			$this->assertTrue($tagger->hasTag($tag));
		}

		$this->assertCount(4, $tagger->getTags(), 'Not all previously saved tags found');
	}

	public function testIsEmpty() {
		$tagger = $this->tagMgr->load($this->objectType);

		$this->assertEquals(0, count($tagger->getTags()));
		$this->assertTrue($tagger->isEmpty());

		$result = $tagger->add('Tag');
		$this->assertGreaterThan(0, $result, 'add() returned an ID <= 0');
		$this->assertNotEquals(false, $result, 'add() returned false');
		$this->assertFalse($tagger->isEmpty());
	}

	public function testdeleteTags() {
		$defaultTags = array('Friends', 'Family', 'Work', 'Other');
		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$this->assertEquals(4, count($tagger->getTags()));

		$tagger->delete('family');
		$this->assertEquals(3, count($tagger->getTags()));

		$tagger->delete(array('Friends', 'Work', 'Other'));
		$this->assertEquals(0, count($tagger->getTags()));

	}

	public function testRenameTag() {
		$defaultTags = array('Friends', 'Family', 'Wrok', 'Other');
		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$this->assertTrue($tagger->rename('Wrok', 'Work'));
		$this->assertTrue($tagger->hasTag('Work'));
		$this->assertFalse($tagger->hastag('Wrok'));
		$this->assertFalse($tagger->rename('Wrok', 'Work'));

	}

	public function testTagAs() {
		$objids = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

		$tagger = $this->tagMgr->load($this->objectType);

		foreach($objids as $id) {
			$this->assertTrue($tagger->tagAs($id, 'Family'));
		}

		$this->assertEquals(1, count($tagger->getTags()));
		$this->assertEquals(9, count($tagger->getIdsForTag('Family')));
	}

	/**
	* @depends testTagAs
	*/
	public function testUnTag() {
		$objIds = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

		// Is this "legal"?
		$this->testTagAs();
		$tagger = $this->tagMgr->load($this->objectType);

		foreach($objIds as $id) {
			$this->assertTrue(in_array($id, $tagger->getIdsForTag('Family')));
			$tagger->unTag($id, 'Family');
			$this->assertFalse(in_array($id, $tagger->getIdsForTag('Family')));
		}

		$this->assertEquals(1, count($tagger->getTags()));
		$this->assertEquals(0, count($tagger->getIdsForTag('Family')));
	}

	public function testFavorite() {
		$tagger = $this->tagMgr->load($this->objectType);
		$this->assertTrue($tagger->addToFavorites(1));
		$this->assertEquals(array(1), $tagger->getFavorites());
		$this->assertTrue($tagger->removeFromFavorites(1));
		$this->assertEquals(array(), $tagger->getFavorites());
	}

}
