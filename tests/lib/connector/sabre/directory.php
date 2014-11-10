<?php

/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class Test_OC_Connector_Sabre_Directory extends \Test\TestCase {

	private $view;
	private $info;

	protected function setUp() {
		parent::setUp();

		$this->view = $this->getMock('OC\Files\View', array(), array(), '', false);
		$this->info = $this->getMock('OC\Files\FileInfo', array(), array(), '', false);
	}

	private function getRootDir() {
		$this->view->expects($this->once())
			->method('getRelativePath')
			->will($this->returnValue(''));

		$this->info->expects($this->once())
			->method('getPath')
			->will($this->returnValue(''));

		return new OC_Connector_Sabre_Directory($this->view, $this->info);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testCreateSharedFileFails() {
		$dir = $this->getRootDir();
		$dir->createFile('Shared');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testCreateSharedFolderFails() {
		$dir = $this->getRootDir();
		$dir->createDirectory('Shared');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteSharedFolderFails() {
		$dir = $this->getRootDir();
		$dir->delete();
	}

	/**
	 *
	 */
	public function testDeleteFolderWhenAllowed() {
		// deletion allowed
		$this->info->expects($this->once())
			->method('isDeletable')
			->will($this->returnValue(true));

		// but fails
		$this->view->expects($this->once())
			->method('rmdir')
			->will($this->returnValue(true));

		$dir = $this->getRootDir();
		$dir->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteFolderFailsWhenNotAllowed() {
		$this->info->expects($this->once())
			->method('isDeletable')
			->will($this->returnValue(false));

		$dir = $this->getRootDir();
		$dir->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteFolderThrowsWhenDeletionFailed() {
		// deletion allowed
		$this->info->expects($this->once())
			->method('isDeletable')
			->will($this->returnValue(true));

		// but fails
		$this->view->expects($this->once())
			->method('rmdir')
			->will($this->returnValue(false));

		$dir = $this->getRootDir();
		$dir->delete();
	}
}
