<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Tagging\TagMapper;
use OC\TagManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class TagsTest
 *
 * @group DB
 */
class TagsTest extends \Test\TestCase {
	protected $objectType;
	/** @var \OCP\IUser */
	protected $user;
	/** @var \OCP\IUserSession */
	protected $userSession;
	protected $backupGlobals = false;
	/** @var \OC\Tagging\TagMapper */
	protected $tagMapper;
	/** @var \OCP\ITagManager */
	protected $tagMgr;

	protected function setUp(): void {
		parent::setUp();

		Server::get(IUserManager::class)->clearBackends();
		Server::get(IUserManager::class)->registerBackend(new \Test\Util\User\Dummy());
		$userId = $this->getUniqueID('user_');
		Server::get(IUserManager::class)->createUser($userId, 'pass');
		\OC_User::setUserId($userId);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')
			->willReturn($userId);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->objectType = $this->getUniqueID('type_');
		$this->tagMapper = new TagMapper(Server::get(IDBConnection::class));
		$this->tagMgr = new TagManager($this->tagMapper, $this->userSession, Server::get(IDBConnection::class), Server::get(LoggerInterface::class), Server::get(IEventDispatcher::class));
	}

	protected function tearDown(): void {
		$conn = Server::get(IDBConnection::class);
		$conn->executeQuery('DELETE FROM `*PREFIX*vcategory_to_object`');
		$conn->executeQuery('DELETE FROM `*PREFIX*vcategory`');

		parent::tearDown();
	}

	public function testTagManagerWithoutUserReturnsNull(): void {
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn(null);
		$this->tagMgr = new TagManager($this->tagMapper, $this->userSession, Server::get(IDBConnection::class), Server::get(LoggerInterface::class), Server::get(IEventDispatcher::class));
		$this->assertNull($this->tagMgr->load($this->objectType));
	}

	public function testInstantiateWithDefaults(): void {
		$defaultTags = ['Friends', 'Family', 'Work', 'Other'];

		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$this->assertEquals(4, count($tagger->getTags()));
	}

	public function testAddTags(): void {
		$tags = ['Friends', 'Family', 'Work', 'Other'];

		$tagger = $this->tagMgr->load($this->objectType);

		foreach ($tags as $tag) {
			$result = $tagger->add($tag);
			$this->assertGreaterThan(0, $result, 'add() returned an ID <= 0');
			$this->assertTrue((bool)$result);
		}

		$this->assertFalse($tagger->add('Family'));
		$this->assertFalse($tagger->add('fAMILY'));

		$this->assertCount(4, $tagger->getTags(), 'Wrong number of added tags');
	}

	public function testAddMultiple(): void {
		$tags = ['Friends', 'Family', 'Work', 'Other'];

		$tagger = $this->tagMgr->load($this->objectType);

		foreach ($tags as $tag) {
			$this->assertFalse($tagger->hasTag($tag));
		}

		$result = $tagger->addMultiple($tags);
		$this->assertTrue((bool)$result);

		foreach ($tags as $tag) {
			$this->assertTrue($tagger->hasTag($tag));
		}

		$tagMaps = $tagger->getTags();
		$this->assertCount(4, $tagMaps, 'Not all tags added');
		foreach ($tagMaps as $tagMap) {
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
		foreach ($tagMaps as $tagMap) {
			$this->assertNotEquals(null, $tagMap['id']);
		}

		// Reload the tagger.
		$tagger = $this->tagMgr->load($this->objectType);

		foreach ($tags as $tag) {
			$this->assertTrue($tagger->hasTag($tag));
		}

		$this->assertCount(4, $tagger->getTags(), 'Not all previously saved tags found');
	}

	public function testIsEmpty(): void {
		$tagger = $this->tagMgr->load($this->objectType);

		$this->assertEquals(0, count($tagger->getTags()));
		$this->assertTrue($tagger->isEmpty());

		$result = $tagger->add('Tag');
		$this->assertGreaterThan(0, $result, 'add() returned an ID <= 0');
		$this->assertNotEquals(false, $result, 'add() returned false');
		$this->assertFalse($tagger->isEmpty());
	}

	public function testGetTagsForObjects(): void {
		$defaultTags = ['Friends', 'Family', 'Work', 'Other'];
		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$tagger->tagAs(1, 'Friends');
		$tagger->tagAs(1, 'Other');
		$tagger->tagAs(2, 'Family');

		$tags = $tagger->getTagsForObjects([1]);
		$this->assertEquals(1, count($tags));
		$tags = current($tags);
		sort($tags);
		$this->assertSame(['Friends', 'Other'], $tags);

		$tags = $tagger->getTagsForObjects([1, 2]);
		$this->assertEquals(2, count($tags));
		$tags1 = $tags[1];
		sort($tags1);
		$this->assertSame(['Friends', 'Other'], $tags1);
		$this->assertSame(['Family'], $tags[2]);
		$this->assertEquals(
			[],
			$tagger->getTagsForObjects([4])
		);
		$this->assertEquals(
			[],
			$tagger->getTagsForObjects([4, 5])
		);
	}

	public function testGetTagsForObjectsMassiveResults(): void {
		$defaultTags = ['tag1'];
		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);
		$tagData = $tagger->getTags();
		$tagId = $tagData[0]['id'];
		$tagType = $tagData[0]['type'];

		$conn = Server::get(IDBConnection::class);
		$statement = $conn->prepare(
			'INSERT INTO `*PREFIX*vcategory_to_object` ' .
			'(`objid`, `categoryid`, `type`) VALUES ' .
			'(?, ?, ?)'
		);

		// insert lots of entries
		$idsArray = [];
		for ($i = 1; $i <= 1500; $i++) {
			$statement->execute([$i, $tagId, $tagType]);
			$idsArray[] = $i;
		}

		$tags = $tagger->getTagsForObjects($idsArray);
		$this->assertEquals(1500, count($tags));
	}

	public function testDeleteTags(): void {
		$defaultTags = ['Friends', 'Family', 'Work', 'Other'];
		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$this->assertEquals(4, count($tagger->getTags()));

		$tagger->delete('family');
		$this->assertEquals(3, count($tagger->getTags()));

		$tagger->delete(['Friends', 'Work', 'Other']);
		$this->assertEquals(0, count($tagger->getTags()));
	}

	public function testRenameTag(): void {
		$defaultTags = ['Friends', 'Family', 'Wrok', 'Other'];
		$tagger = $this->tagMgr->load($this->objectType, $defaultTags);

		$this->assertTrue($tagger->rename('Wrok', 'Work'));
		$this->assertTrue($tagger->hasTag('Work'));
		$this->assertFalse($tagger->hasTag('Wrok'));
		$this->assertFalse($tagger->rename('Wrok', 'Work')); // Rename non-existant tag.
		$this->assertFalse($tagger->rename('Work', 'Family')); // Collide with existing tag.
	}

	public function testTagAs(): void {
		$objids = [1, 2, 3, 4, 5, 6, 7, 8, 9];

		$tagger = $this->tagMgr->load($this->objectType);

		foreach ($objids as $id) {
			$this->assertTrue($tagger->tagAs($id, 'Family'));
		}

		$this->assertEquals(1, count($tagger->getTags()));
		$this->assertEquals(9, count($tagger->getIdsForTag('Family')));
	}

	/**
	 * @depends testTagAs
	 */
	public function testUnTag(): void {
		$objIds = [1, 2, 3, 4, 5, 6, 7, 8, 9];

		// Is this "legal"?
		$this->testTagAs();
		$tagger = $this->tagMgr->load($this->objectType);

		foreach ($objIds as $id) {
			$this->assertTrue(in_array($id, $tagger->getIdsForTag('Family')));
			$tagger->unTag($id, 'Family');
			$this->assertFalse(in_array($id, $tagger->getIdsForTag('Family')));
		}

		$this->assertEquals(1, count($tagger->getTags()));
		$this->assertEquals(0, count($tagger->getIdsForTag('Family')));
	}

	public function testFavorite(): void {
		$tagger = $this->tagMgr->load($this->objectType);
		$this->assertTrue($tagger->addToFavorites(1));
		$this->assertEquals([1], $tagger->getFavorites());
		$this->assertTrue($tagger->removeFromFavorites(1));
		$this->assertEquals([], $tagger->getFavorites());
	}
}
