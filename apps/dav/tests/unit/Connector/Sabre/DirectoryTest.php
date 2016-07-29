<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\Unit\Connector\Sabre;

use OCP\Files\ForbiddenException;

/**
 * @group DB
 */
class DirectoryTest extends \Test\TestCase {

	/** @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject */
	private $view;
	/** @var \OC\Files\FileInfo | \PHPUnit_Framework_MockObject_MockObject */
	private $info;

	protected function setUp() {
		parent::setUp();

		$this->view = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$this->info = $this->getMockBuilder('OC\Files\FileInfo')
			->disableOriginalConstructor()
			->getMock();
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
	 * @expectedException \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testDeleteForbidden() {
		// deletion allowed
		$this->info->expects($this->once())
			->method('isDeletable')
			->will($this->returnValue(true));

		// but fails
		$this->view->expects($this->once())
			->method('rmdir')
			->with('sub')
			->willThrowException(new ForbiddenException('', true));

		$dir = $this->getDir('sub');
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

	public function testGetQuotaInfoUnlimited() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Quota')
			->disableOriginalConstructor()
			->getMock();

		$storage->expects($this->any())
			->method('instanceOfStorage')
			->will($this->returnValueMap([
				'\OC\Files\Storage\Shared' => false,
				'\OC\Files\Storage\Wrapper\Quota' => false,
			]));

		$storage->expects($this->never())
			->method('getQuota');

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
		$this->assertEquals([200, -3], $dir->getQuotaInfo()); //200 used, unlimited
	}

	public function testGetQuotaInfoSpecific() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Quota')
			->disableOriginalConstructor()
			->getMock();

		$storage->expects($this->any())
			->method('instanceOfStorage')
			->will($this->returnValueMap([
				['\OC\Files\Storage\Shared', false],
				['\OC\Files\Storage\Wrapper\Quota', true],
			]));

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
