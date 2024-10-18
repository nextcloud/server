<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OCA\DAV\SystemTag\SystemTagsObjectTypeCollection;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;

class SystemTagsObjectTypeCollectionTest extends \Test\TestCase {

	/**
	 * @var SystemTagsObjectTypeCollection
	 */
	private $node;

	/**
	 * @var ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var ISystemTagObjectMapper
	 */
	private $tagMapper;

	/**
	 * @var Folder
	 */
	private $userFolder;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->getMockBuilder(ISystemTagManager::class)
			->getMock();
		$this->tagMapper = $this->getMockBuilder(ISystemTagObjectMapper::class)
			->getMock();

		$user = $this->getMockBuilder(IUser::class)
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->willReturn('testuser');
		$userSession = $this->getMockBuilder(IUserSession::class)
			->getMock();
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$groupManager = $this->getMockBuilder(IGroupManager::class)
			->getMock();
		$groupManager->expects($this->any())
			->method('isAdmin')
			->with('testuser')
			->willReturn(true);

		$this->userFolder = $this->getMockBuilder(Folder::class)
			->getMock();
		$userFolder = $this->userFolder;

		$closure = function ($name) use ($userFolder) {
			$node = $userFolder->getFirstNodeById(intval($name));
			return $node !== null;
		};
		$writeAccessClosure = function ($name) use ($userFolder) {
			$nodes = $userFolder->getById((int)$name);
			foreach ($nodes as $node) {
				if (($node->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE) {
					return true;
				}
			}
			return false;
		};

		$this->node = new SystemTagsObjectTypeCollection(
			'files',
			$this->tagManager,
			$this->tagMapper,
			$userSession,
			$groupManager,
			$closure,
			$writeAccessClosure,
		);
	}


	public function testForbiddenCreateFile(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->node->createFile('555');
	}


	public function testForbiddenCreateDirectory(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->node->createDirectory('789');
	}

	public function testGetChild(): void {
		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('555')
			->willReturn($this->createMock(Node::class));
		$childNode = $this->node->getChild('555');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection', $childNode);
		$this->assertEquals('555', $childNode->getName());
	}


	public function testGetChildWithoutAccess(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('555')
			->willReturn(null);
		$this->node->getChild('555');
	}


	public function testGetChildren(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);

		$this->node->getChildren();
	}

	public function testChildExists(): void {
		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('123')
			->willReturn($this->createMock(Node::class));
		$this->assertTrue($this->node->childExists('123'));
	}

	public function testChildExistsWithoutAccess(): void {
		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('555')
			->willReturn(null);
		$this->assertFalse($this->node->childExists('555'));
	}


	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->node->delete();
	}


	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->node->setName('somethingelse');
	}

	public function testGetName(): void {
		$this->assertEquals('files', $this->node->getName());
	}
}
