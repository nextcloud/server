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

	private function getDir($path = '/') {
		$this->view->expects($this->once())
			->method('getRelativePath')
			->will($this->returnValue($path));

		$this->info->expects($this->once())
			->method('getPath')
			->will($this->returnValue($path));

		return new \OCA\DAV\Connector\Sabre\Directory($this->view, $this->info);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteRootFolderFails() {
		$this->info->expects($this->any())
			->method('isDeletable')
			->will($this->returnValue(true));
		$this->view->expects($this->never())
			->method('rmdir');
		$dir = $this->getDir();
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
			->with('sub')
			->will($this->returnValue(true));

		$dir = $this->getDir('sub');
		$dir->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteFolderFailsWhenNotAllowed() {
		$this->info->expects($this->once())
			->method('isDeletable')
			->will($this->returnValue(false));

		$dir = $this->getDir('sub');
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
			->with('sub')
			->will($this->returnValue(false));

		$dir = $this->getDir('sub');
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

		$dir = new \OCA\DAV\Connector\Sabre\Directory($this->view, $this->info);
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

		$dir = new \OCA\DAV\Connector\Sabre\Directory($this->view, $this->info);
		$dir->getChild('.');
	}

	/**
	 * @expectedException \OCA\DAV\Connector\Sabre\Exception\InvalidPath
	 */
	public function testGetChildThrowInvalidPath() {
		$this->view->expects($this->once())
			->method('verifyPath')
			->willThrowException(new \OCP\Files\InvalidPathException());
		$this->view->expects($this->never())
			->method('getFileInfo');

		$dir = new \OCA\DAV\Connector\Sabre\Directory($this->view, $this->info);
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

		$dir = new \OCA\DAV\Connector\Sabre\Directory($this->view, $this->info);
		$this->assertEquals([200, 800], $dir->getQuotaInfo()); //200 used, 800 free
	}
}
