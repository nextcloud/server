<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Node;

use OC\Files\Filesystem;
use OC\Files\Node\HookConnector;
use OC\Files\Node\Root;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OCP\EventDispatcher\GenericEvent as APIGenericEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCP\Files\Events\Node\AbstractNodesEvent;
use OCP\Files\Events\Node\BeforeNodeCopiedEvent;
use OCP\Files\Events\Node\BeforeNodeCreatedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\BeforeNodeTouchedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Node;
use OCP\ICacheFactory;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class HookConnectorTest
 *
 * @group DB
 *
 * @package Test\Files\Node
 */
class HookConnectorTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	/** @var IEventDispatcher */
	protected $eventDispatcher;

	private LoggerInterface $logger;

	/** @var View */
	private $view;

	/** @var Root */
	private $root;

	/** @var string */
	private $userId;

	protected function setUp(): void {
		parent::setUp();
		$this->userId = $this->getUniqueID();
		$this->createUser($this->userId, 'pass');
		// this will setup the FS
		$this->loginAsUser($this->userId);
		$this->registerMount($this->userId, new Temporary(), '/' . $this->userId . '/files/');
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createLocal')
			->willReturnCallback(function () {
				return new ArrayCache();
			});
		$this->view = new View();
		$this->root = new Root(
			Filesystem::getMountManager(),
			$this->view,
			\OC::$server->getUserManager()->get($this->userId),
			\OC::$server->getUserMountCache(),
			$this->createMock(LoggerInterface::class),
			$this->createMock(IUserManager::class),
			$this->createMock(IEventDispatcher::class),
			$cacheFactory,
		);
		$this->eventDispatcher = \OC::$server->query(IEventDispatcher::class);
		$this->logger = \OC::$server->query(LoggerInterface::class);
	}

	protected function tearDown(): void {
		parent::tearDown();
		\OC_Hook::clear('OC_Filesystem');
		\OC_Util::tearDownFS();
	}

	public function viewToNodeProvider() {
		return [
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'preWrite', '\OCP\Files::preWrite', BeforeNodeWrittenEvent::class],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'postWrite', '\OCP\Files::postWrite', NodeWrittenEvent::class],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'preCreate', '\OCP\Files::preCreate', BeforeNodeCreatedEvent::class],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'postCreate', '\OCP\Files::postCreate', NodeCreatedEvent::class],
			[function () {
				Filesystem::mkdir('test.txt');
			}, 'preCreate', '\OCP\Files::preCreate', BeforeNodeCreatedEvent::class],
			[function () {
				Filesystem::mkdir('test.txt');
			}, 'postCreate', '\OCP\Files::postCreate', NodeCreatedEvent::class],
			[function () {
				Filesystem::touch('test.txt');
			}, 'preTouch', '\OCP\Files::preTouch', BeforeNodeTouchedEvent::class],
			[function () {
				Filesystem::touch('test.txt');
			}, 'postTouch', '\OCP\Files::postTouch', NodeTouchedEvent::class],
			[function () {
				Filesystem::touch('test.txt');
			}, 'preCreate', '\OCP\Files::preCreate', BeforeNodeCreatedEvent::class],
			[function () {
				Filesystem::touch('test.txt');
			}, 'postCreate', '\OCP\Files::postCreate', NodeCreatedEvent::class],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
				Filesystem::unlink('test.txt');
			}, 'preDelete', '\OCP\Files::preDelete', BeforeNodeDeletedEvent::class],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
				Filesystem::unlink('test.txt');
			}, 'postDelete', '\OCP\Files::postDelete', NodeDeletedEvent::class],
			[function () {
				Filesystem::mkdir('test.txt');
				Filesystem::rmdir('test.txt');
			}, 'preDelete', '\OCP\Files::preDelete', BeforeNodeDeletedEvent::class],
			[function () {
				Filesystem::mkdir('test.txt');
				Filesystem::rmdir('test.txt');
			}, 'postDelete', '\OCP\Files::postDelete', NodeDeletedEvent::class],
		];
	}

	/**
	 * @param callable $operation
	 * @param string $expectedHook
	 * @dataProvider viewToNodeProvider
	 */
	public function testViewToNode(callable $operation, $expectedHook, $expectedLegacyEvent, $expectedEvent): void {
		$connector = new HookConnector($this->root, $this->view, $this->eventDispatcher, $this->logger);
		$connector->viewToNode();
		$hookCalled = false;
		/** @var Node $hookNode */
		$hookNode = null;

		$this->root->listen('\OC\Files', $expectedHook, function ($node) use (&$hookNode, &$hookCalled) {
			$hookCalled = true;
			$hookNode = $node;
		});

		$dispatcherCalled = false;
		/** @var Node $dispatcherNode */
		$dispatcherNode = null;
		$this->eventDispatcher->addListener($expectedLegacyEvent, function ($event) use (&$dispatcherCalled, &$dispatcherNode) {
			/** @var GenericEvent|APIGenericEvent $event */
			$dispatcherCalled = true;
			$dispatcherNode = $event->getSubject();
		});

		$newDispatcherCalled = false;
		$newDispatcherNode = null;
		$this->eventDispatcher->addListener($expectedEvent, function ($event) use ($expectedEvent, &$newDispatcherCalled, &$newDispatcherNode) {
			if ($event instanceof  $expectedEvent) {
				/** @var AbstractNodeEvent $event */
				$newDispatcherCalled = true;
				$newDispatcherNode = $event->getNode();
			}
		});

		$operation();

		$this->assertTrue($hookCalled);
		$this->assertEquals('/' . $this->userId . '/files/test.txt', $hookNode->getPath());

		$this->assertTrue($dispatcherCalled);
		$this->assertEquals('/' . $this->userId . '/files/test.txt', $dispatcherNode->getPath());

		$this->assertTrue($newDispatcherCalled);
		$this->assertEquals('/' . $this->userId . '/files/test.txt', $newDispatcherNode->getPath());
	}

	public function viewToNodeProviderCopyRename() {
		return [
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::rename('source', 'target');
			}, 'preRename', '\OCP\Files::preRename', BeforeNodeRenamedEvent::class],
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::rename('source', 'target');
			}, 'postRename', '\OCP\Files::postRename', NodeRenamedEvent::class],
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::copy('source', 'target');
			}, 'preCopy', '\OCP\Files::preCopy', BeforeNodeCopiedEvent::class],
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::copy('source', 'target');
			}, 'postCopy', '\OCP\Files::postCopy', NodeCopiedEvent::class],
		];
	}

	/**
	 * @param callable $operation
	 * @param string $expectedHook
	 * @dataProvider viewToNodeProviderCopyRename
	 */
	public function testViewToNodeCopyRename(callable $operation, $expectedHook, $expectedLegacyEvent, $expectedEvent): void {
		$connector = new HookConnector($this->root, $this->view, $this->eventDispatcher, $this->logger);
		$connector->viewToNode();
		$hookCalled = false;
		/** @var Node $hookSourceNode */
		$hookSourceNode = null;
		/** @var Node $hookTargetNode */
		$hookTargetNode = null;

		$this->root->listen('\OC\Files', $expectedHook, function ($sourceNode, $targetNode) use (&$hookCalled, &$hookSourceNode, &$hookTargetNode) {
			$hookCalled = true;
			$hookSourceNode = $sourceNode;
			$hookTargetNode = $targetNode;
		});

		$dispatcherCalled = false;
		/** @var Node $dispatcherSourceNode */
		$dispatcherSourceNode = null;
		/** @var Node $dispatcherTargetNode */
		$dispatcherTargetNode = null;
		$this->eventDispatcher->addListener($expectedLegacyEvent, function ($event) use (&$dispatcherSourceNode, &$dispatcherTargetNode, &$dispatcherCalled) {
			/** @var GenericEvent|APIGenericEvent $event */
			$dispatcherCalled = true;
			[$dispatcherSourceNode, $dispatcherTargetNode] = $event->getSubject();
		});

		$newDispatcherCalled = false;
		/** @var Node $dispatcherSourceNode */
		$newDispatcherSourceNode = null;
		/** @var Node $dispatcherTargetNode */
		$newDispatcherTargetNode = null;
		$this->eventDispatcher->addListener($expectedEvent, function ($event) use ($expectedEvent, &$newDispatcherSourceNode, &$newDispatcherTargetNode, &$newDispatcherCalled) {
			if ($event instanceof $expectedEvent) {
				/** @var AbstractNodesEvent$event */
				$newDispatcherCalled = true;
				$newDispatcherSourceNode = $event->getSource();
				$newDispatcherTargetNode = $event->getTarget();
			}
		});

		$operation();

		$this->assertTrue($hookCalled);
		$this->assertEquals('/' . $this->userId . '/files/source', $hookSourceNode->getPath());
		$this->assertEquals('/' . $this->userId . '/files/target', $hookTargetNode->getPath());

		$this->assertTrue($dispatcherCalled);
		$this->assertEquals('/' . $this->userId . '/files/source', $dispatcherSourceNode->getPath());
		$this->assertEquals('/' . $this->userId . '/files/target', $dispatcherTargetNode->getPath());

		$this->assertTrue($newDispatcherCalled);
		$this->assertEquals('/' . $this->userId . '/files/source', $newDispatcherSourceNode->getPath());
		$this->assertEquals('/' . $this->userId . '/files/target', $newDispatcherTargetNode->getPath());
	}

	public function testPostDeleteMeta(): void {
		$connector = new HookConnector($this->root, $this->view, $this->eventDispatcher, $this->logger);
		$connector->viewToNode();
		$hookCalled = false;
		/** @var Node $hookNode */
		$hookNode = null;

		$this->root->listen('\OC\Files', 'postDelete', function ($node) use (&$hookNode, &$hookCalled) {
			$hookCalled = true;
			$hookNode = $node;
		});

		$dispatcherCalled = false;
		/** @var Node $dispatcherNode */
		$dispatcherNode = null;
		$this->eventDispatcher->addListener('\OCP\Files::postDelete', function ($event) use (&$dispatcherCalled, &$dispatcherNode) {
			/** @var GenericEvent|APIGenericEvent $event */
			$dispatcherCalled = true;
			$dispatcherNode = $event->getSubject();
		});

		$newDispatcherCalled = false;
		/** @var Node $dispatcherNode */
		$newDispatcherNode = null;
		$this->eventDispatcher->addListener(NodeDeletedEvent::class, function ($event) use (&$newDispatcherCalled, &$newDispatcherNode) {
			if ($event instanceof NodeDeletedEvent) {
				/** @var AbstractNodeEvent $event */
				$newDispatcherCalled = true;
				$newDispatcherNode = $event->getNode();
			}
		});

		Filesystem::file_put_contents('test.txt', 'asd');
		$info = Filesystem::getFileInfo('test.txt');
		Filesystem::unlink('test.txt');

		$this->assertTrue($hookCalled);
		$this->assertEquals($hookNode->getId(), $info->getId());

		$this->assertTrue($dispatcherCalled);
		$this->assertEquals($dispatcherNode->getId(), $info->getId());

		$this->assertTrue($newDispatcherCalled);
		$this->assertEquals($newDispatcherNode->getId(), $info->getId());
	}
}
