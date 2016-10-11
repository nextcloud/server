<?php
/**
 * @author Jörn Friedrich Dreyer
 * @copyright (c) 2014 Jörn Friedrich Dreyer <jfd@owncloud.com>
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

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\Swift;

/**
 * Class SwiftTest
 *
 * @group DB
 *
 * @package Test\Files\Cache\ObjectStore
 */
class SwiftTest extends \Test\Files\Storage\Storage {

	/**
	 * @var Swift
	 */
	private $objectStorage;

	protected function setUp() {
		parent::setUp();

		if (!getenv('RUN_OBJECTSTORE_TESTS')) {
			$this->markTestSkipped('objectstore tests are unreliable in some environments');
		}

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// create users
		$users = array('test');
		foreach($users as $userName) {
			$user = \OC::$server->getUserManager()->get($userName);
			if ($user !== null) { $user->delete(); }
			\OC::$server->getUserManager()->createUser($userName, $userName);
		}

		// main test user
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId('test');

		$config = \OC::$server->getConfig()->getSystemValue('objectstore');
		$this->objectStorage = new Swift($config['arguments']);
		$config['objectstore'] = $this->objectStorage;
		$this->instance = new ObjectStoreStorage($config);
	}

	protected function tearDown() {
		if (is_null($this->instance)) {
			return;
		}
		$this->objectStorage->deleteContainer(true);
		$this->instance->getCache()->clear();

		$users = array('test');
		foreach($users as $userName) {
			$user = \OC::$server->getUserManager()->get($userName);
			if ($user !== null) { $user->delete(); }
		}
		parent::tearDown();
	}

	public function testStat() {

		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$ctimeStart = time();
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));
		$this->assertTrue($this->instance->isReadable('/lorem.txt'));
		$ctimeEnd = time();
		$mTime = $this->instance->filemtime('/lorem.txt');

		// check that ($ctimeStart - 5) <= $mTime <= ($ctimeEnd + 1)
		$this->assertGreaterThanOrEqual(($ctimeStart - 5), $mTime);
		$this->assertLessThanOrEqual(($ctimeEnd + 1), $mTime);
		$this->assertEquals(filesize($textFile), $this->instance->filesize('/lorem.txt'));

		$stat = $this->instance->stat('/lorem.txt');
		//only size and mtime are required in the result
		$this->assertEquals($stat['size'], $this->instance->filesize('/lorem.txt'));
		$this->assertEquals($stat['mtime'], $mTime);

		if ($this->instance->touch('/lorem.txt', 100) !== false) {
			$mTime = $this->instance->filemtime('/lorem.txt');
			$this->assertEquals($mTime, 100);
		}
	}

	public function testCheckUpdate() {
		$this->markTestSkipped('Detecting external changes is not supported on object storages');
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testMove($source, $target) {
		$this->initSourceAndTarget($source);
		$sourceId = $this->instance->getCache()->getId(ltrim('/',$source));
		$this->assertNotEquals(-1, $sourceId);

		$this->instance->rename($source, $target);

		$this->assertTrue($this->instance->file_exists($target), $target.' was not created');
		$this->assertFalse($this->instance->file_exists($source), $source.' still exists');
		$this->assertSameAsLorem($target);

		$targetId = $this->instance->getCache()->getId(ltrim('/',$target));
		$this->assertSame($sourceId, $targetId, 'fileid must be stable on move or shares will break');
	}

	public function testRenameDirectory() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');
		$this->instance->file_put_contents('source/test2.txt', 'qwerty');
		$this->instance->mkdir('source/subfolder');
		$this->instance->file_put_contents('source/subfolder/test.txt', 'bar');
		$sourceId = $this->instance->getCache()->getId('source');
		$this->assertNotEquals(-1, $sourceId);
		$this->instance->rename('source', 'target');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertFalse($this->instance->file_exists('source/test2.txt'));
		$this->assertFalse($this->instance->file_exists('source/subfolder'));
		$this->assertFalse($this->instance->file_exists('source/subfolder/test.txt'));

		$this->assertTrue($this->instance->file_exists('target'));
		$this->assertTrue($this->instance->file_exists('target/test1.txt'));
		$this->assertTrue($this->instance->file_exists('target/test2.txt'));
		$this->assertTrue($this->instance->file_exists('target/subfolder'));
		$this->assertTrue($this->instance->file_exists('target/subfolder/test.txt'));

		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
		$this->assertEquals('qwerty', $this->instance->file_get_contents('target/test2.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents('target/subfolder/test.txt'));
		$targetId = $this->instance->getCache()->getId('target');
		$this->assertSame($sourceId, $targetId, 'fileid must be stable on move or shares will break');
	}

	public function testRenameOverWriteDirectory() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');
		$sourceId = $this->instance->getCache()->getId('source');
		$this->assertNotEquals(-1, $sourceId);

		$this->instance->mkdir('target');
		$this->instance->file_put_contents('target/test1.txt', 'bar');
		$this->instance->file_put_contents('target/test2.txt', 'bar');

		$this->instance->rename('source', 'target');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertFalse($this->instance->file_exists('target/test2.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
		$targetId = $this->instance->getCache()->getId('target');
		$this->assertSame($sourceId, $targetId, 'fileid must be stable on move or shares will break');
	}

	public function testRenameOverWriteDirectoryOverFile() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');
		$sourceId = $this->instance->getCache()->getId('source');
		$this->assertNotEquals(-1, $sourceId);

		$this->instance->file_put_contents('target', 'bar');

		$this->instance->rename('source', 'target');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
		$targetId = $this->instance->getCache()->getId('target');
		$this->assertSame($sourceId, $targetId, 'fileid must be stable on move or shares will break');
	}
}
