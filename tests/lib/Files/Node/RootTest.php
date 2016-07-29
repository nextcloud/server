<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\FileInfo;

class RootTest extends \Test\TestCase {
	/** @var \OC\User\User */
	private $user;

	/** @var \OC\Files\Mount\Manager */
	private $manager;

	protected function setUp() {
		parent::setUp();

		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$urlgenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		$this->user = new \OC\User\User('', new \Test\Util\User\Dummy, null, $config, $urlgenerator);

		$this->manager = $this->getMockBuilder('\OC\Files\Mount\Manager')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function getFileInfo($data) {
		return new FileInfo('', null, '', $data, null);
	}

	public function testGet() {
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root($this->manager, $view, $this->user);

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
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root($this->manager, $view, $this->user);

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
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root($this->manager, $view, $this->user);

		$root->get('/../foo');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testGetNoStorages() {
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root($this->manager, $view, $this->user);

		$root->get('/bar/foo');
	}
}
