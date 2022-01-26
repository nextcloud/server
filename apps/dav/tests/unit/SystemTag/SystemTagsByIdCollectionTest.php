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

use InvalidArgumentException;
use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagNode;
use OCA\DAV\SystemTag\SystemTagsByIdCollection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class SystemTagsByIdCollectionTest extends TestCase {

	/**
	 * @var ISystemTagManager
	 */
	private $tagManager;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
	}

	public function getNode($isAdmin = true): SystemTagsByIdCollection {
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
			->willReturn($isAdmin);
		return new SystemTagsByIdCollection(
			$this->tagManager,
			$userSession,
			$groupManager
		);
	}

	public function adminFlagProvider(): array {
		return [[true], [false]];
	}


	public function testForbiddenCreateFile() {
		$this->expectException(Forbidden::class);

		$this->getNode()->createFile('555');
	}


	public function testForbiddenCreateDirectory() {
		$this->expectException(Forbidden::class);

		$this->getNode()->createDirectory('789');
	}

	/**
	 * @throws BadRequest
	 * @throws NotFound
	 */
	public function testGetChild() {
		$tag = new SystemTag(123, 'Test', true, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag]);

		$childNode = $this->getNode()->getChild('123');

		$this->assertInstanceOf(SystemTagNode::class, $childNode);
		$this->assertEquals('123', $childNode->getName());
		$this->assertEquals($tag, $childNode->getSystemTag());
	}


	/**
	 * @throws NotFound
	 */
	public function testGetChildInvalidName() {
		$this->expectException(BadRequest::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['invalid'])
			->will($this->throwException(new InvalidArgumentException()));

		$this->getNode()->getChild('invalid');
	}


	/**
	 * @throws BadRequest
	 */
	public function testGetChildNotFound() {
		$this->expectException(NotFound::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['444'])
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->getChild('444');
	}


	/**
	 * @throws BadRequest
	 */
	public function testGetChildUserNotVisible() {
		$this->expectException(NotFound::class);

		$tag = new SystemTag(123, 'Test', false, false);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag]);

		$this->getNode(false)->getChild('123');
	}

	public function testGetChildrenAdmin() {
		$tag1 = new SystemTag(123, 'One', true, false);
		$tag2 = new SystemTag(456, 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(null)
			->willReturn([$tag1, $tag2]);

		$children = $this->getNode()->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf(SystemTagNode::class, $children[0]);
		$this->assertInstanceOf(SystemTagNode::class, $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenNonAdmin() {
		$tag1 = new SystemTag(123, 'One', true, false);
		$tag2 = new SystemTag(456, 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(true)
			->willReturn([$tag1, $tag2]);

		$children = $this->getNode(false)->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf(SystemTagNode::class, $children[0]);
		$this->assertInstanceOf(SystemTagNode::class, $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenEmpty() {
		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(null)
			->willReturn([]);
		$this->assertCount(0, $this->getNode()->getChildren());
	}

	public function childExistsProvider(): array {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @dataProvider childExistsProvider
	 * @throws BadRequest
	 */
	public function testChildExists(bool $userVisible, bool $expectedResult) {
		$tag = new SystemTag(123, 'One', $userVisible, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($userVisible);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag]);

		$this->assertEquals($expectedResult, $this->getNode()->childExists('123'));
	}

	/**
	 * @throws BadRequest
	 */
	public function testChildExistsNotFound() {
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->getNode()->childExists('123'));
	}


	public function testChildExistsBadRequest() {
		$this->expectException(BadRequest::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['invalid'])
			->will($this->throwException(new InvalidArgumentException()));

		$this->getNode()->childExists('invalid');
	}
}
