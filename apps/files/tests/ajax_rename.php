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

class Test_OC_Files_App_Rename extends \PHPUnit_Framework_TestCase {
	private static $user;

	function setUp() {
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

	function tearDown() {
		$result = \OC_User::deleteUser(self::$user);
		$this->assertTrue($result);
		\OC\Files\Filesystem::tearDown();
	}

	/**
	 * @brief test rename of file/folder named "Shared"
	 */
	function testRenameSharedFolder() {
		$dir = '/';
		$oldname = 'Shared';
		$newname = 'new_name';

		$this->viewMock->expects($this->at(0))
			->method('file_exists')
			->with('/')
			->will($this->returnValue(true));

		$result = $this->files->rename($dir, $oldname, $newname);
		$expected = array(
			'success'	=> false,
			'data'		=> array('message' => '%s could not be renamed')
		);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @brief test rename of file/folder named "Shared"
	 */
	function testRenameSharedFolderInSubdirectory() {
		$dir = '/test';
		$oldname = 'Shared';
		$newname = 'new_name';

		$this->viewMock->expects($this->at(0))
			->method('file_exists')
			->with('/test')
			->will($this->returnValue(true));

		$this->viewMock->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(new \OC\Files\FileInfo(
				'/test',
				null,
				'/test',	
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
			))));

		$result = $this->files->rename($dir, $oldname, $newname);

		$this->assertTrue($result['success']);
		$this->assertEquals(123, $result['data']['id']);
		$this->assertEquals('new_name', $result['data']['name']);
		$this->assertEquals(18, $result['data']['size']);
		$this->assertEquals('httpd/unix-directory', $result['data']['mimetype']);
		$icon = \OC_Helper::mimetypeIcon('dir');
		$icon = substr($icon, 0, -3) . 'svg';
		$this->assertEquals($icon, $result['data']['icon']);
	}

	/**
	 * @brief test rename of file/folder to "Shared"
	 */
	function testRenameFolderToShared() {
		$dir = '/';
		$oldname = 'oldname';
		$newname = 'Shared';

		$result = $this->files->rename($dir, $oldname, $newname);
		$expected = array(
			'success'	=> false,
			'data'		=> array('message' => "Invalid folder name. Usage of 'Shared' is reserved.")
		);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @brief test rename of file/folder
	 */
	function testRenameFolder() {
		$dir = '/';
		$oldname = 'oldname';
		$newname = 'newname';

		$this->viewMock->expects($this->at(0))
			->method('file_exists')
			->with('/')
			->will($this->returnValue(true));

		$this->viewMock->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(new \OC\Files\FileInfo(
				'/',
				null,
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
			))));

		$result = $this->files->rename($dir, $oldname, $newname);

		$this->assertTrue($result['success']);
		$this->assertEquals(123, $result['data']['id']);
		$this->assertEquals('new_name', $result['data']['name']);
		$this->assertEquals(18, $result['data']['size']);
		$this->assertEquals('httpd/unix-directory', $result['data']['mimetype']);
		$this->assertEquals('abcdef', $result['data']['etag']);
		$icon = \OC_Helper::mimetypeIcon('dir');
		$icon = substr($icon, 0, -3) . 'svg';
		$this->assertEquals($icon, $result['data']['icon']);
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
			->with('/unexist')
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
		$this->assertEquals('targetnotfound', $result['data']['code']);
	}
}
