<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Node;

use OC\Files\FileInfo;
use OC\Files\Mount\Manager;
use OC\Files\Node\Folder;
use OC\Files\Node\Root;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OC\User\NoUserException;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

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
	/** @var \OCP\Files\Config\IUserMountCache|\PHPUnit\Framework\MockObject\MockObject */
	private $userMountCache;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var IEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
	private $eventDispatcher;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $cacheFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->manager = $this->getMockBuilder(Manager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userMountCache = $this->getMockBuilder('\OCP\Files\Config\IUserMountCache')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheFactory->method('createLocal')
			->willReturnCallback(function () {
				return new ArrayCache();
			});
	}

	/**
	 * @return \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
	 */
	protected function getRootViewMock() {
		$view = $this->createMock(View::class);
		$view->expects($this->any())
			->method('getRoot')
			->willReturn('');
		return $view;
	}

	protected function getFileInfo($data) {
		return new FileInfo('', null, '', $data, null);
	}

	public function testGet(): void {
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();
		$view = $this->getRootViewMock();
		$root = new Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['fileid' => 10, 'path' => 'bar/foo', 'name', 'mimetype' => 'text/plain']));

		$root->mount($storage, '');
		$node = $root->get('/bar/foo');
		$this->assertEquals(10, $node->getId());
		$this->assertInstanceOf('\OC\Files\Node\File', $node);
	}


	public function testGetNotFound(): void {
		$this->expectException(NotFoundException::class);

		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();
		$view = $this->getRootViewMock();
		$root = new Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn(false);

		$root->mount($storage, '');
		$root->get('/bar/foo');
	}


	public function testGetInvalidPath(): void {
		$this->expectException(NotPermittedException::class);

		$view = $this->getRootViewMock();
		$root = new Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);

		$root->get('/../foo');
	}


	public function testGetNoStorages(): void {
		$this->expectException(NotFoundException::class);

		$view = $this->getRootViewMock();
		$root = new Root(
			$this->manager,
			$view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);

		$root->get('/bar/foo');
	}

	public function testGetUserFolder(): void {
		$root = new Root(
			$this->manager,
			$this->getRootViewMock(),
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
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
		/** @var CappedMemoryCache|\PHPUnit\Framework\MockObject\MockObject $cappedMemoryCache */
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


	public function testGetUserFolderWithNoUserObj(): void {
		$this->expectException(NoUserException::class);
		$this->expectExceptionMessage('Backends provided no user object');

		$root = new Root(
			$this->createMock(Manager::class),
			$this->getRootViewMock(),
			null,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
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
				$this->anything()
			);

		$root->getUserFolder('NotExistingUser');
	}
}
