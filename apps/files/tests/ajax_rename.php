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

	function setUp() {
		// mock OC_L10n
		$l10nMock = $this->getMock('\OC_L10N', array('t'), array(), '', false);
		$l10nMock->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));
		$viewMock = $this->getMock('\OC\Files\View', array('rename', 'normalizePath'), array(), '', false);
		$viewMock->expects($this->any())
			->method('normalizePath')
			->will($this->returnArgument(0));
		$viewMock->expects($this->any())
			->method('rename')
			->will($this->returnValue(true));
		$this->files = new \OCA\Files\App($viewMock, $l10nMock);
	}

	/**
	 * @brief test rename of file/folder named "Shared"
	 */
	function testRenameSharedFolder() {
		$dir = '/';
		$oldname = 'Shared';
		$newname = 'new_name';

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

		$result = $this->files->rename($dir, $oldname, $newname);
		$expected = array(
			'success'	=> true,
			'data'		=> array(
				'dir'		=> $dir,
				'file'		=> $oldname,
				'newname'	=> $newname
			)
		);

		$this->assertEquals($expected, $result);
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

		$result = $this->files->rename($dir, $oldname, $newname);
		$expected = array(
			'success'	=> true,
			'data'		=> array(
				'dir'		=> $dir,
				'file'		=> $oldname,
				'newname'	=> $newname
			)
		);

		$this->assertEquals($expected, $result);
	}
}
