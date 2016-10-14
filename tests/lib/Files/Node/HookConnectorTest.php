<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\Filesystem;
use OC\Files\Node\Root;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCP\Files\Node;
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

	/**
	 * @var View
	 */
	private $view;

	/**
	 * @var Root
	 */
	private $root;

	/**
	 * @var string
	 */
	private $userId;

	public function setUp() {
		parent::setUp();
		$this->userId = $this->getUniqueID();
		$this->createUser($this->userId, 'pass');
		$this->registerMount($this->userId, new Temporary(), '/' . $this->userId . '/files/');
		\OC_Util::setupFS($this->userId);
		$this->view = new View();
		$this->root = new Root(
			Filesystem::getMountManager(),
			$this->view,
			\OC::$server->getUserManager()->get($this->userId),
			\OC::$server->getUserMountCache()
		);
	}

	public function tearDown() {
		parent::tearDown();
		\OC_Hook::clear('OC_Filesystem');
		\OC_Util::tearDownFS();
	}

	public function viewToNodeProvider() {
		return [
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'preWrite'],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'postWrite'],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'preCreate'],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
			}, 'postCreate'],
			[function () {
				Filesystem::mkdir('test.txt');
			}, 'preCreate'],
			[function () {
				Filesystem::mkdir('test.txt');
			}, 'postCreate'],
			[function () {
				Filesystem::touch('test.txt');
			}, 'preTouch'],
			[function () {
				Filesystem::touch('test.txt');
			}, 'postTouch'],
			[function () {
				Filesystem::touch('test.txt');
			}, 'preCreate'],
			[function () {
				Filesystem::touch('test.txt');
			}, 'postCreate'],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
				Filesystem::unlink('test.txt');
			}, 'preDelete'],
			[function () {
				Filesystem::file_put_contents('test.txt', 'asd');
				Filesystem::unlink('test.txt');
			}, 'postDelete'],
			[function () {
				Filesystem::mkdir('test.txt');
				Filesystem::rmdir('test.txt');
			}, 'preDelete'],
			[function () {
				Filesystem::mkdir('test.txt');
				Filesystem::rmdir('test.txt');
			}, 'postDelete'],
		];
	}

	/**
	 * @param callable $operation
	 * @param string $expectedHook
	 * @dataProvider viewToNodeProvider
	 */
	public function testViewToNode(callable $operation, $expectedHook) {
		$connector = new \OC\Files\Node\HookConnector($this->root, $this->view);
		$connector->viewToNode();
		$hookCalled = false;
		/** @var Node $hookNode */
		$hookNode = null;

		$this->root->listen('\OC\Files', $expectedHook, function ($node) use (&$hookNode, &$hookCalled) {
			$hookCalled = true;
			$hookNode = $node;
		});

		$operation();

		$this->assertTrue($hookCalled);
		$this->assertEquals('/' . $this->userId . '/files/test.txt', $hookNode->getPath());
	}

	public function viewToNodeProviderCopyRename() {
		return [
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::rename('source', 'target');
			}, 'preRename'],
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::rename('source', 'target');
			}, 'postRename'],
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::copy('source', 'target');
			}, 'preCopy'],
			[function () {
				Filesystem::file_put_contents('source', 'asd');
				Filesystem::copy('source', 'target');
			}, 'postCopy'],
		];
	}

	/**
	 * @param callable $operation
	 * @param string $expectedHook
	 * @dataProvider viewToNodeProviderCopyRename
	 */
	public function testViewToNodeCopyRename(callable $operation, $expectedHook) {
		$connector = new \OC\Files\Node\HookConnector($this->root, $this->view);
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

		$operation();

		$this->assertTrue($hookCalled);
		$this->assertEquals('/' . $this->userId . '/files/source', $hookSourceNode->getPath());
		$this->assertEquals('/' . $this->userId . '/files/target', $hookTargetNode->getPath());
	}

	public function testPostDeleteMeta() {
		$connector = new \OC\Files\Node\HookConnector($this->root, $this->view);
		$connector->viewToNode();
		$hookCalled = false;
		/** @var Node $hookNode */
		$hookNode = null;

		$this->root->listen('\OC\Files', 'postDelete', function ($node) use (&$hookNode, &$hookCalled) {
			$hookCalled = true;
			$hookNode = $node;
		});

		Filesystem::file_put_contents('test.txt', 'asd');
		$info = Filesystem::getFileInfo('test.txt');
		Filesystem::unlink('test.txt');

		$this->assertTrue($hookCalled);
		$this->assertEquals($hookNode->getId(), $info->getId());
	}
}
