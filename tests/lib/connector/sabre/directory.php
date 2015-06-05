<?php

/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class Test_OC_Connector_Sabre_Directory extends \Test\TestCase {

	/** @var OC\Files\View | PHPUnit_Framework_MockObject_MockObject */
	private $view;
	/** @var OC\Files\FileInfo | PHPUnit_Framework_MockObject_MockObject */
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

		return new \OC\Connector\Sabre\Directory($this->view, $this->info);
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

	public function testGetChildren() {
		$info1 = $this->getMockBuilder('OC\Files\FileInfo')
			->disableOriginalConstructor()
			->getMock();
		$info2 = $this->getMockBuilder('OC\Files\FileInfo')
			->disableOriginalConstructor()
			->getMock();
		$info1->expects($this->any())
			->method('getName')
			->will($this->returnValue('first'));
		$info1->expects($this->any())
			->method('getEtag')
			->will($this->returnValue('abc'));
		$info2->expects($this->any())
			->method('getName')
			->will($this->returnValue('second'));
		$info2->expects($this->any())
			->method('getEtag')
			->will($this->returnValue('def'));

		$this->view->expects($this->once())
			->method('getDirectoryContent')
			->with('')
			->will($this->returnValue(array($info1, $info2)));

		$this->view->expects($this->any())
			->method('getRelativePath')
			->will($this->returnValue(''));

		$dir = new \OC\Connector\Sabre\Directory($this->view, $this->info);
		$nodes = $dir->getChildren();

		$this->assertEquals(2, count($nodes));

		// calling a second time just returns the cached values,
		// does not call getDirectoryContents again
		$dir->getChildren();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ServiceUnavailable
	 */
	public function testGetChildThrowStorageNotAvailableException() {
		$this->view->expects($this->once())
			->method('getFileInfo')
			->willThrowException(new \OCP\Files\StorageNotAvailableException());

		$dir = new \OC\Connector\Sabre\Directory($this->view, $this->info);
		$dir->getChild('.');
	}

	/**
	 * @expectedException \OC\Connector\Sabre\Exception\InvalidPath
	 */
	public function testGetChildThrowInvalidPath() {
		$this->view->expects($this->once())
			->method('verifyPath')
			->willThrowException(new \OCP\Files\InvalidPathException());
		$this->view->expects($this->never())
			->method('getFileInfo');

		$dir = new \OC\Connector\Sabre\Directory($this->view, $this->info);
		$dir->getChild('.');
	}

	public function testGetQuotaInfo() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Quota')
			->disableOriginalConstructor()
			->getMock();

		$storage->expects($this->once())
			->method('instanceOfStorage')
			->with('\OC\Files\Storage\Wrapper\Quota')
			->will($this->returnValue(true));

		$storage->expects($this->once())
			->method('getQuota')
			->will($this->returnValue(1000));

		$storage->expects($this->once())
			->method('free_space')
			->will($this->returnValue(800));

		$this->info->expects($this->once())
			->method('getSize')
			->will($this->returnValue(200));

		$this->info->expects($this->once())
			->method('getStorage')
			->will($this->returnValue($storage));

		$dir = new \OC\Connector\Sabre\Directory($this->view, $this->info);
		$this->assertEquals([200, 800], $dir->getQuotaInfo()); //200 used, 800 free
	}
}
