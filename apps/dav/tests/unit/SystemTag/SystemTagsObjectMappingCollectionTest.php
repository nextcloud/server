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

use Exception;
use InvalidArgumentException;
use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagMappingNode;
use OCA\DAV\SystemTag\SystemTagsObjectMappingCollection;
use OCP\IUser;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Test\TestCase;

class SystemTagsObjectMappingCollectionTest extends TestCase {

	/**
	 * @var ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var ISystemTagObjectMapper
	 */
	private $tagMapper;

	/**
	 * @var IUser
	 */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);

		$this->user = $this->createMock(IUser::class);
	}

	public function getNode(): SystemTagsObjectMappingCollection {
		return new SystemTagsObjectMappingCollection(
			111,
			'files',
			$this->user,
			$this->tagManager,
			$this->tagMapper
		);
	}

	/**
	 * @throws PreconditionFailed
	 * @throws Forbidden
	 */
	public function testAssignTag() {
		$tag = new SystemTag('1', 'Test', true, true);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555');

		$this->getNode()->createFile('555');
	}

	public function permissionsProvider(): array {
		return [
			// invisible, tag does not exist for user
			[false, true, PreconditionFailed::class],
			// visible but static, cannot assign tag
			[true, false, Forbidden::class],
		];
	}

	/**
	 * @dataProvider permissionsProvider
	 */
	public function testAssignTagNoPermission(bool $userVisible, bool $userAssignable, string $expectedException) {
		$tag = new SystemTag('1', 'Test', $userVisible, $userAssignable);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($userVisible);
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn($userAssignable);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);
		$this->tagMapper->expects($this->never())
			->method('assignTags');

		$thrown = null;
		try {
			$this->getNode()->createFile('555');
		} catch (Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}


	/**
	 * @throws Forbidden
	 */
	public function testAssignTagNotFound() {
		$this->expectException(PreconditionFailed::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->will($this->throwException(new TagNotFoundException()));

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
		$tag = new SystemTag(555, 'TheTag', true, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555', true)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn(['555' => $tag]);

		$childNode = $this->getNode()->getChild('555');

		$this->assertInstanceOf(SystemTagMappingNode::class, $childNode);
		$this->assertEquals('555', $childNode->getName());
	}


	/**
	 * @throws BadRequest
	 */
	public function testGetChildNonVisible() {
		$this->expectException(NotFound::class);

		$tag = new SystemTag(555, 'TheTag', false, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555', true)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn(['555' => $tag]);

		$this->getNode()->getChild('555');
	}


	/**
	 * @throws BadRequest
	 */
	public function testGetChildRelationNotFound() {
		$this->expectException(NotFound::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '777')
			->willReturn(false);

		$this->getNode()->getChild('777');
	}


	/**
	 * @throws NotFound
	 */
	public function testGetChildInvalidId() {
		$this->expectException(BadRequest::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', 'badid')
			->will($this->throwException(new InvalidArgumentException()));

		$this->getNode()->getChild('badid');
	}


	/**
	 * @throws BadRequest
	 */
	public function testGetChildTagDoesNotExist() {
		$this->expectException(NotFound::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '777')
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->getChild('777');
	}

	public function testGetChildren() {
		$tag1 = new SystemTag(555, 'TagOne', true, false);
		$tag2 = new SystemTag(556, 'TagTwo', true, true);
		$tag3 = new SystemTag(557, 'InvisibleTag', false, true);

		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with([111], 'files')
			->willReturn(['111' => ['555', '556', '557']]);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555', '556', '557'])
			->willReturn(['555' => $tag1, '556' => $tag2, '557' => $tag3]);

		$this->tagManager->expects($this->exactly(3))
			->method('canUserSeeTag')
			->willReturnCallback(function ($tag) {
				return $tag->isUserVisible();
			});

		$children = $this->getNode()->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf(SystemTagMappingNode::class, $children[0]);
		$this->assertInstanceOf(SystemTagMappingNode::class, $children[1]);

		$this->assertEquals(111, $children[0]->getObjectId());
		$this->assertEquals('files', $children[0]->getObjectType());
		$this->assertEquals($tag1, $children[0]->getSystemTag());

		$this->assertEquals(111, $children[1]->getObjectId());
		$this->assertEquals('files', $children[1]->getObjectType());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	/**
	 * @throws BadRequest
	 */
	public function testChildExistsWithVisibleTag() {
		$tag = new SystemTag(555, 'TagOne', true, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);

		$this->assertTrue($this->getNode()->childExists('555'));
	}

	/**
	 * @throws BadRequest
	 */
	public function testChildExistsWithInvisibleTag() {
		$tag = new SystemTag(555, 'TagOne', false, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);

		$this->assertFalse($this->getNode()->childExists('555'));
	}

	/**
	 * @throws BadRequest
	 */
	public function testChildExistsNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->willReturn(false);

		$this->assertFalse($this->getNode()->childExists('555'));
	}

	/**
	 * @throws BadRequest
	 */
	public function testChildExistsTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->getNode()->childExists('555'));
	}


	public function testChildExistsInvalidId() {
		$this->expectException(BadRequest::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->throwException(new InvalidArgumentException()));

		$this->getNode()->childExists('555');
	}


	public function testDelete() {
		$this->expectException(Forbidden::class);

		$this->getNode()->delete();
	}


	public function testSetName() {
		$this->expectException(Forbidden::class);

		$this->getNode()->setName('somethingelse');
	}

	public function testGetName() {
		$this->assertEquals('111', $this->getNode()->getName());
	}
}
