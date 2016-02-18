<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\FileInfo;
use OCP\Files\NotPermittedException;
use OC\Files\Mount\Manager;

/**
 * @group DB
 */
class Root extends \Test\TestCase {
	private $user;

	protected function setUp() {
		parent::setUp();
		$this->user = new \OC\User\User('', new \Test\Util\User\Dummy);
	}

	protected function getFileInfo($data) {
		return new FileInfo('', null, '', $data, null);
	}

	public function testGet() {
		$manager = new Manager();
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = $this->getMock('\OC\Files\Storage\Storage');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('fileid' => 10, 'path' => 'bar/foo', 'name', 'mimetype' => 'text/plain'))));

		$root->mount($storage, '');
		$node = $root->get('/bar/foo');
		$this->assertEquals(10, $node->getId());
		$this->assertInstanceOf('\OC\Files\Node\File', $node);
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testGetNotFound() {
		$manager = new Manager();
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = $this->getMock('\OC\Files\Storage\Storage');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(false));

		$root->mount($storage, '');
		$root->get('/bar/foo');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testGetInvalidPath() {
		$manager = new Manager();
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$root->get('/../foo');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testGetNoStorages() {
		$manager = new Manager();
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$root->get('/bar/foo');
	}
}
