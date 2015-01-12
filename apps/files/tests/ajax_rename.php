<?php

/**
 * ownCloud - Core
 *
 * @author Morris Jobke
 * @copyright 2013 Morris Jobke morris.jobke@gmail.com
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

class Test_OC_Files_App_Rename extends \Test\TestCase {
	private static $user;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $viewMock;

	/**
	 * @var \OCA\Files\App
	 */
	private $files;

	private $originalStorage;

	protected function setUp() {
		parent::setUp();

		$this->originalStorage = \OC\Files\Filesystem::getStorage('/');

		// mock OC_L10n
		if (!self::$user) {
			self::$user = uniqid();
		}
		\OC_User::createUser(self::$user, 'password');
		\OC_User::setUserId(self::$user);

		\OC\Files\Filesystem::init(self::$user, '/' . self::$user . '/files');

		$l10nMock = $this->getMock('\OC_L10N', array('t'), array(), '', false);
		$l10nMock->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));
		$viewMock = $this->getMock('\OC\Files\View', array('rename', 'normalizePath', 'getFileInfo', 'file_exists'), array(), '', false);
		$viewMock->expects($this->any())
			->method('normalizePath')
			->will($this->returnArgument(0));
		$viewMock->expects($this->any())
			->method('rename')
			->will($this->returnValue(true));
		$this->viewMock = $viewMock;
		$this->files = new \OCA\Files\App($viewMock, $l10nMock);
	}

	protected function tearDown() {
		$result = \OC_User::deleteUser(self::$user);
		$this->assertTrue($result);
		\OC\Files\Filesystem::tearDown();
		\OC\Files\Filesystem::mount($this->originalStorage, array(), '/');

		parent::tearDown();
	}

	/**
	 * test rename of file/folder
	 */
	function testRenameFolder() {
		$dir = '/';
		$oldname = 'oldname';
		$newname = 'newname';

		$this->viewMock->expects($this->any())
			->method('file_exists')
			->with($this->anything())
			->will($this->returnValueMap(array(
				array('/', true),
				array('/oldname', true)
				)));


		$this->viewMock->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(new \OC\Files\FileInfo(
				'/new_name',
				new \OC\Files\Storage\Local(array('datadir' => '/')),
				'/',
				array(
				'fileid' => 123,
				'type' => 'dir',
				'mimetype' => 'httpd/unix-directory',
				'mtime' => 0,
				'permissions' => 31,
				'size' => 18,
				'etag' => 'abcdef',
				'directory' => '/',
				'name' => 'new_name',
			), null)));

		$result = $this->files->rename($dir, $oldname, $newname);

		$this->assertTrue($result['success']);
		$this->assertEquals(123, $result['data']['id']);
		$this->assertEquals('new_name', $result['data']['name']);
		$this->assertEquals(18, $result['data']['size']);
		$this->assertEquals('httpd/unix-directory', $result['data']['mimetype']);
		$this->assertEquals('abcdef', $result['data']['etag']);
		$this->assertFalse(isset($result['data']['tags']));
		$this->assertEquals('/', $result['data']['path']);
		$icon = \OC_Helper::mimetypeIcon('dir');
		$icon = substr($icon, 0, -3) . 'svg';
		$this->assertEquals($icon, $result['data']['icon']);
	}

	/**
	 * test rename of file with tag
	 */
	function testRenameFileWithTag() {
		$taggerMock = $this->getMock('\OCP\ITags');
		$taggerMock->expects($this->any())
			->method('getTagsForObjects')
			->with(array(123))
			->will($this->returnValue(array(123 => array('tag1', 'tag2'))));
		$tagManagerMock = $this->getMock('\OCP\ITagManager');
		$tagManagerMock->expects($this->any())
			->method('load')
			->with('files')
			->will($this->returnValue($taggerMock));
		$oldTagManager = \OC::$server->query('TagManager');
		\OC::$server->registerService('TagManager', function ($c) use ($tagManagerMock) {
			return $tagManagerMock;
		});

		$dir = '/';
		$oldname = 'oldname.txt';
		$newname = 'newname.txt';

		$this->viewMock->expects($this->any())
			->method('file_exists')
			->with($this->anything())
			->will($this->returnValueMap(array(
				array('/', true),
				array('/oldname.txt', true)
				)));


		$this->viewMock->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(new \OC\Files\FileInfo(
				'/new_name.txt',
				new \OC\Files\Storage\Local(array('datadir' => '/')),
				'/',
				array(
				'fileid' => 123,
				'type' => 'file',
				'mimetype' => 'text/plain',
				'mtime' => 0,
				'permissions' => 31,
				'size' => 18,
				'etag' => 'abcdef',
				'directory' => '/',
				'name' => 'new_name.txt',
			), null)));

		$result = $this->files->rename($dir, $oldname, $newname);

		$this->assertTrue($result['success']);
		$this->assertEquals(123, $result['data']['id']);
		$this->assertEquals('new_name.txt', $result['data']['name']);
		$this->assertEquals(18, $result['data']['size']);
		$this->assertEquals('text/plain', $result['data']['mimetype']);
		$this->assertEquals('abcdef', $result['data']['etag']);
		$this->assertEquals(array('tag1', 'tag2'), $result['data']['tags']);
		$this->assertEquals('/', $result['data']['path']);
		$icon = \OC_Helper::mimetypeIcon('text');
		$icon = substr($icon, 0, -3) . 'svg';
		$this->assertEquals($icon, $result['data']['icon']);

		\OC::$server->registerService('TagManager', function ($c) use ($oldTagManager) {
			return $oldTagManager;
		});
	}

	/**
	 * Test rename inside a folder that doesn't exist any more
	 */
	function testRenameInNonExistingFolder() {
		$dir = '/unexist';
		$oldname = 'oldname';
		$newname = 'newname';

		$this->viewMock->expects($this->at(0))
			->method('file_exists')
			->with('/unexist/oldname')
			->will($this->returnValue(false));

		$this->viewMock->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array(
				'fileid' => 123,
				'type' => 'dir',
				'mimetype' => 'httpd/unix-directory',
				'size' => 18,
				'etag' => 'abcdef',
				'directory' => '/unexist',
				'name' => 'new_name',
			)));

		$result = $this->files->rename($dir, $oldname, $newname);

		$this->assertFalse($result['success']);
		$this->assertEquals('sourcenotfound', $result['data']['code']);
	}

	/**
	 * Test move to a folder that doesn't exist any more
	 */
	function testRenameToNonExistingFolder() {
		$dir = '/';
		$oldname = 'oldname';
		$newname = '/unexist/newname';

		$this->viewMock->expects($this->any())
			->method('file_exists')
			->with($this->anything())
			->will($this->returnValueMap(array(
				array('/oldname', true),
				array('/unexist', false)
				)));

		$this->viewMock->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array(
				'fileid' => 123,
				'type' => 'dir',
				'mimetype' => 'httpd/unix-directory',
				'size' => 18,
				'etag' => 'abcdef',
				'directory' => '/unexist',
				'name' => 'new_name',
			)));

		$result = $this->files->rename($dir, $oldname, $newname);

		$this->assertFalse($result['success']);
		$this->assertEquals('targetnotfound', $result['data']['code']);
	}
}
