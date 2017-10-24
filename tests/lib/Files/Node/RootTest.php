<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Cache\CappedMemoryCache;
use OC\Files\FileInfo;
use OC\Files\Mount\Manager;
use OC\Files\Node\Folder;
use OC\Files\View;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class RootTest
 *
 * @package Test\Files\Node
 */
class RootTest extends \Test\TestCase {
	/** @var \OC\User\User */
	private $user;
	/** @var \OC\Files\Mount\Manager */
	private $manager;
	/** @var \OCP\Files\Config\IUserMountCache|\PHPUnit_Framework_MockObject_MockObject */
	private $userMountCache;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	protected function setUp() {
		parent::setUp();

		$config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$urlgenerator = $this->getMockBuilder(IURLGenerator::class)
			->disableOriginalConstructor()
			->getMock();

		$this->user = new \OC\User\User('', new \Test\Util\User\Dummy, null, $config, $urlgenerator);
		$this->manager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userMountCache = $this->getMockBuilder('\OCP\Files\Config\IUserMountCache')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->createMock(ILogger::class);
		$this->userManager = $this->createMock(IUserManager::class);
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
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);

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
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);

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
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);

		$root->get('/../foo');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testGetNoStorages() {
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);

		$root->get('/bar/foo');
	}

	public function testGetUserFolder() {
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$this->createMock(View::class),
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUserId');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('MyUserId')
			->willReturn($user);
		/** @var CappedMemoryCache|\PHPUnit_Framework_MockObject_MockObject $cappedMemoryCache */
		$cappedMemoryCache = $this->createMock(CappedMemoryCache::class);
		$cappedMemoryCache
			->expects($this->once())
			->method('hasKey')
			->willReturn(true);
		$folder = $this->createMock(Folder::class);
		$cappedMemoryCache
			->expects($this->once())
			->method('get')
			->with('MyUserId')
			->willReturn($folder);

		$this->invokePrivate($root, 'userFolderCache', [$cappedMemoryCache]);
		$this->assertEquals($folder, $root->getUserFolder('MyUserId'));
	}

	/**
	 * @expectedException \OC\User\NoUserException
	 * @expectedExceptionMessage Backends provided no user object
	 */
	public function testGetUserFolderWithNoUserObj() {
		$root = new \OC\Files\Node\Root(
			$this->createMock(Manager::class),
			$this->createMock(View::class),
			null,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);
		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Backends provided no user object for NotExistingUser',
				[
					'app' => 'files',
				]
			);

		$root->getUserFolder('NotExistingUser');
	}
}
