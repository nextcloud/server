<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OCA\DAV\SystemTag\SystemTagsObjectMappingCollection;
use OCA\DAV\SystemTag\SystemTagsObjectTypeCollection;
use OCP\Files\Folder;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class SystemTagsObjectTypeCollectionTest extends TestCase {

	/**
	 * @var SystemTagsObjectTypeCollection
	 */
	private $node;

	/**
	 * @var Folder
	 */
	private $userFolder;

	protected function setUp(): void {
		parent::setUp();

		$tagManager = $this->createMock(ISystemTagManager::class);
		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('testuser');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->expects($this->any())
			->method('isAdmin')
			->with('testuser')
			->willReturn(true);

		$this->userFolder = $this->createMock(Folder::class);
		$userFolder = $this->userFolder;

		$closure = function ($name) use ($userFolder) {
			$nodes = $userFolder->getById(intval($name));
			return !empty($nodes);
		};

		$this->node = new SystemTagsObjectTypeCollection(
			'files',
			$tagManager,
			$tagMapper,
			$userSession,
			$groupManager,
			$closure
		);
	}


	public function testForbiddenCreateFile() {
		$this->expectException(Forbidden::class);

		$this->node->createFile('555');
	}


	public function testForbiddenCreateDirectory() {
		$this->expectException(Forbidden::class);

		$this->node->createDirectory('789');
	}

	/**
	 * @throws NotFound
	 */
	public function testGetChild() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('555')
			->willReturn([true]);
		$childNode = $this->node->getChild('555');

		$this->assertInstanceOf(SystemTagsObjectMappingCollection::class, $childNode);
		$this->assertEquals('555', $childNode->getName());
	}


	public function testGetChildWithoutAccess() {
		$this->expectException(NotFound::class);

		$this->userFolder->expects($this->once())
			->method('getById')
			->with('555')
			->willReturn([]);
		$this->node->getChild('555');
	}


	public function testGetChildren() {
		$this->expectException(MethodNotAllowed::class);

		$this->node->getChildren();
	}

	public function testChildExists() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('123')
			->willReturn([true]);
		$this->assertTrue($this->node->childExists('123'));
	}

	public function testChildExistsWithoutAccess() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('555')
			->willReturn([]);
		$this->assertFalse($this->node->childExists('555'));
	}


	public function testDelete() {
		$this->expectException(Forbidden::class);

		$this->node->delete();
	}


	public function testSetName() {
		$this->expectException(Forbidden::class);

		$this->node->setName('somethingelse');
	}

	public function testGetName() {
		$this->assertEquals('files', $this->node->getName());
	}
}
