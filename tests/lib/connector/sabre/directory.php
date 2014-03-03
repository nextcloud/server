<?php

/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class Test_OC_Connector_Sabre_Directory extends PHPUnit_Framework_TestCase {

	private function getRootDir() {
		$view = $this->getMock('OC\Files\View', array(), array(), '', false);
		$view->expects($this->once())
			->method('getRelativePath')
			->will($this->returnValue(''));

		$info = $this->getMock('OC\Files\FileInfo', array(), array(), '', false);
		$info->expects($this->once())
			->method('getPath')
			->will($this->returnValue(''));

		return new OC_Connector_Sabre_Directory($view, $info);
	}

	/**
	 * @expectedException Sabre_DAV_Exception_Forbidden
	 */
	public function testCreateSharedFileFails() {
		$dir = $this->getRootDir();
		$dir->createFile('Shared');
	}

	/**
	 * @expectedException Sabre_DAV_Exception_Forbidden
	 */
	public function testCreateSharedFolderFails() {
		$dir = $this->getRootDir();
		$dir->createDirectory('Shared');
	}

	/**
	 * @expectedException Sabre_DAV_Exception_Forbidden
	 */
	public function testDeleteSharedFolderFails() {
		$dir = $this->getRootDir();
		$dir->delete();
	}
}
