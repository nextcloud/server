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

namespace OCA\ObjectStore\Tests\Unit;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\Swift as ObjectStoreToTest;

use PHPUnit_Framework_TestCase;

class Swift extends PHPUnit_Framework_TestCase {

	/**
	 * @var \OC\Files\ObjectStore\Swift $storage
	 */
	private $storage;

	private $objectStorage;
	
	public function setUp() {
		
		\OC_App::disable('files_sharing');
		\OC_App::disable('files_versions');

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// create users
		$users = array('test');
		foreach($users as $userName) {
			\OC_User::deleteUser($userName);
			\OC_User::createUser($userName, $userName);
		}

		// main test user
		$userName = 'test';
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId('test');

		$testContainer = 'oc-test-container-'.substr( md5(rand()), 0, 7);

		$params = array(
			'username' => 'facebook100000330192569',
			'password' => 'Dbdj1sXnRSHxIGc4',
			'container' => $testContainer,
			'autocreate' => true,
			'region' => 'RegionOne', //required, trystack defaults to 'RegionOne'
			'url' => 'http://8.21.28.222:5000/v2.0', // The Identity / Keystone endpoint
			'tenantName' => 'facebook100000330192569', // required on trystack
			'serviceName' => 'swift', //trystack uses swift by default, the lib defaults to 'cloudFiles' if omitted
			'user' => \OC_User::getManager()->get($userName)
		);
		$this->objectStorage = new ObjectStoreToTest($params);
		$params['objectstore'] = $this->objectStorage;
		$this->storage = new ObjectStoreStorage($params);
	}

	public function tearDown() {
		if (is_null($this->storage)) {
			return;
		}
		$this->objectStorage->deleteContainer(true);
		$this->storage->getCache()->clear();
		//TODO how do I clear hooks?
	}
	
	public function testStat () {
		$stat = $this->storage->stat('');
		$this->assertInternalType('array', $stat);
		$this->assertEquals(-1, $stat['parent']);
		$this->assertEquals('', $stat['path']);
		$this->assertEquals('', $stat['name']);
		$this->assertEquals(0, $stat['size']);
	}
	public function testMkdir () {
		$root = $this->storage->stat('');
		
		$statBefore = $this->storage->stat('someuser');
		$this->assertFalse($statBefore);
		
		$this->storage->mkdir('someuser');
		$statAfter = $this->storage->stat('someuser');
		
		$this->assertTrue(is_array($statAfter));
		$this->assertEquals($root['fileid'], $statAfter['parent']);
		$this->assertEquals('someuser', $statAfter['path']);
		$this->assertEquals('someuser', $statAfter['name']);
		$this->assertEquals(0, $statAfter['size']);
		
	}
	
	public function filesProvider() {
		return array(
			array('file.txt'),
			array(' file.txt'),
			array('file.txt '),
			array('file with space.txt'),
			array('spéciäl fìle.txt'),
			array('☠ skull and crossbones.txt'),
			array('skull and crossbones ☠ in between.txt'),
			array('💩 pile of poo.txt'),
			array('pile of 💩.txt'),
			// check if someone tries to guess type on a date string
			array('2013-04-25'),
		);
	}
	/**
	 * @dataProvider filesProvider
	 */
	public function testTouch ($file) {
		$root = $this->storage->stat('');
		
		$statBefore = $this->storage->stat($file);
		$this->assertFalse($statBefore);
		
		$this->assertTrue($this->storage->touch($file));
		$statAfter = $this->storage->stat($file);
		
		$this->assertTrue(is_array($statAfter));
		$this->assertEquals($root['fileid'], $statAfter['parent']);
		$this->assertEquals($file, $statAfter['path']);
		$this->assertEquals($file, $statAfter['name']);
		$this->assertEquals(0, $statAfter['size']);

		$this->assertFalse($this->storage->touch('non-existing/'.$file));
		
		//TODO test mtime
		//TODO test existing files
		//TODO test folders
	}
	/**
	 * @dataProvider filesProvider
	 */
	public function testUnlink ($file) {
		$root = $this->storage->stat('');

		$this->assertFalse($this->storage->unlink($file));

		$this->storage->touch($file);
		$statBefore = $this->storage->stat($file);

		$this->assertTrue(is_array($statBefore));
		$this->assertEquals($root['fileid'], $statBefore['parent']);
		$this->assertEquals($file, $statBefore['path']);
		$this->assertEquals($file, $statBefore['name']);
		$this->assertEquals(0, $statBefore['size']);

		$this->assertTrue($this->storage->unlink($file));
		
		$this->assertFalse($this->storage->stat($file));

		//TODO test folders
	}
	
	/**
	 * checks several methods by creating directories:
	 * - file_exists (f/t)
	 * - mkdir (t/f)
	 * - is_dir (t)
	 * - is_file (f)
	 * - filetype ('dir')
	 * - filesize (0)
	 * - isReadable (t)
	 * - isUpdateable (t)
	 * - opendir (dir array/empty array)
	 * - rmdir (t/f)
	 * @dataProvider directoryProvider
	 */
	public function testDirectories($directory) {
		$this->assertFalse($this->storage->file_exists('/' . $directory), 'Expected /'.$directory.' to not exist');

		$this->assertTrue($this->storage->mkdir('/' . $directory), 'Expected creating /'.$directory.' to succeed');

		$this->assertTrue($this->storage->file_exists('/' . $directory), 'Expected /'.$directory.' to exist');
		$this->assertTrue($this->storage->is_dir('/' . $directory), 'Expected /'.$directory.' to be a directory');
		$this->assertFalse($this->storage->is_file('/' . $directory), 'Expected /'.$directory.' not to be a file');
		$this->assertEquals('dir', $this->storage->filetype('/' . $directory), 'Expected /'.$directory.' to have filetype \'dir\'');
		$this->assertEquals(0, $this->storage->filesize('/' . $directory), 'Expected /'.$directory.' to have size 0');
		$this->assertTrue($this->storage->isReadable('/' . $directory), 'Expected /'.$directory.' to be readable');
		$this->assertTrue($this->storage->isUpdatable('/' . $directory), 'Expected /'.$directory.' to be updateable');

		$dh = $this->storage->opendir('');
		$content = array();
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}
		$this->assertEquals(array($directory), $content);

		$this->assertFalse($this->storage->mkdir('/' . $directory), 'Expected already existing folder /'.$directory.' to not be createable');
		$this->assertTrue($this->storage->rmdir('/' . $directory));

		$this->assertFalse($this->storage->file_exists('/' . $directory));

		$this->assertFalse($this->storage->rmdir('/' . $directory), 'Expected not existing folder /'.$directory.' to not be removable');

		$dh = $this->storage->opendir('/');
		$content = array();
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}
		$this->assertEquals(array(), $content);
	}

	public function directoryProvider() {
		return array(
			array('folder'),
			array(' folder'),
			array('folder '),
			array('folder with space'),
			array('spéciäl földer'),
			array('☠ skull and crossbones'),
			array('skull and crossbones ☠ in between'),
			array('💩 pile of poo'),
			array('pile of 💩'),
			// check if someone tries to guess type on a date string
			array('2013-04-25'),
		);
	}

	public function testCopyAndMove() {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->storage->file_put_contents('/source.txt', file_get_contents($textFile));
		$this->storage->copy('/source.txt', '/target.txt');
		$this->assertTrue($this->storage->file_exists('/target.txt'));
		$this->assertEquals($this->storage->file_get_contents('/source.txt'), $this->storage->file_get_contents('/target.txt'));

		$this->storage->rename('/source.txt', '/target2.txt');
		$this->assertTrue($this->storage->file_exists('/target2.txt'));
		$this->assertFalse($this->storage->file_exists('/source.txt'));
		$this->assertEquals(file_get_contents($textFile), $this->storage->file_get_contents('/target2.txt'));

		// move to overwrite
		$testContents = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->storage->file_put_contents('/target3.txt', $testContents);
		$this->storage->rename('/target2.txt', '/target3.txt');
		$this->assertTrue($this->storage->file_exists('/target3.txt'));
		$this->assertFalse($this->storage->file_exists('/target2.txt'));
		$this->assertEquals(file_get_contents($textFile), $this->storage->file_get_contents('/target3.txt'));
	}

	//fopen
	//filetype test
	//getMimetype
	//getURN?!?!
	//test?
	//getConnection
	//writeback

}
