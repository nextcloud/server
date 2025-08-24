<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\SystemTag;

use OC\SystemTag\SystemTag;
use OC\SystemTag\SystemTagManager;
use OC\SystemTag\SystemTagObjectMapper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\Server;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use Test\TestCase;

/**
 * Class TestSystemTagObjectMapper
 *
 * @group DB
 * @package Test\SystemTag
 */
class SystemTagObjectMapperTest extends TestCase {
	/**
	 * @var ISystemTagManager
	 **/
	private $tagManager;

	/**
	 * @var ISystemTagObjectMapper
	 **/
	private $tagMapper;

	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var IEventDispatcher
	 */
	private $dispatcher;

	/**
	 * @var ISystemTag
	 */
	private $tag1;

	/**
	 * @var ISystemTag
	 */
	private $tag2;

	/**
	 * @var ISystemTag
	 */
	private $tag3;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->pruneTagsTables();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);

		$this->tagMapper = new SystemTagObjectMapper(
			$this->connection,
			$this->tagManager,
			$this->dispatcher
		);

		$this->tag1 = new SystemTag(1, 'testtag1', false, false);
		$this->tag2 = new SystemTag(2, 'testtag2', true, false);
		$this->tag3 = new SystemTag(3, 'testtag3', false, false);

		$this->tagManager->expects($this->any())
			->method('getTagsByIds')
			->willReturnCallback(function ($tagIds) {
				$result = [];
				if (in_array(1, $tagIds)) {
					$result[1] = $this->tag1;
				}
				if (in_array(2, $tagIds)) {
					$result[2] = $this->tag2;
				}
				if (in_array(3, $tagIds)) {
					$result[3] = $this->tag3;
				}
				return $result;
			});

		$this->tagMapper->assignTags('1', 'testtype', $this->tag1->getId());
		$this->tagMapper->assignTags('1', 'testtype', $this->tag2->getId());
		$this->tagMapper->assignTags('2', 'testtype', $this->tag1->getId());
		$this->tagMapper->assignTags('3', 'anothertype', $this->tag1->getId());
	}

	protected function tearDown(): void {
		$this->pruneTagsTables();
		parent::tearDown();
	}

	protected function pruneTagsTables() {
		$query = $this->connection->getQueryBuilder();
		$query->delete(SystemTagObjectMapper::RELATION_TABLE)->execute();
		$query->delete(SystemTagManager::TAG_TABLE)->execute();
	}

	public function testGetTagIdsForObjects(): void {
		$tagIdMapping = $this->tagMapper->getTagIdsForObjects(
			['1', '2', '3', '4'],
			'testtype'
		);

		$this->assertEquals([
			'1' => [$this->tag1->getId(), $this->tag2->getId()],
			'2' => [$this->tag1->getId()],
			'3' => [],
			'4' => [],
		], $tagIdMapping);
	}

	public function testGetTagIdsForNoObjects(): void {
		$tagIdMapping = $this->tagMapper->getTagIdsForObjects(
			[],
			'testtype'
		);

		$this->assertEquals([], $tagIdMapping);
	}

	public function testGetTagIdsForALotOfObjects(): void {
		$ids = range(1, 10500);
		$tagIdMapping = $this->tagMapper->getTagIdsForObjects(
			$ids,
			'testtype'
		);

		$this->assertEquals(10500, count($tagIdMapping));
		$this->assertEquals([$this->tag1->getId(), $this->tag2->getId()], $tagIdMapping[1]);
	}

	public function testGetObjectsForTags(): void {
		$objectIds = $this->tagMapper->getObjectIdsForTags(
			[$this->tag1->getId(), $this->tag2->getId(), $this->tag3->getId()],
			'testtype'
		);
		sort($objectIds);

		$this->assertEquals([
			'1',
			'2',
		], $objectIds);
	}

	public function testGetObjectsForTagsLimit(): void {
		$objectIds = $this->tagMapper->getObjectIdsForTags(
			[$this->tag1->getId()],
			'testtype',
			1
		);

		$this->assertEquals([
			1,
		], $objectIds);
	}


	public function testGetObjectsForTagsLimitWithMultipleTags(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->tagMapper->getObjectIdsForTags(
			[$this->tag1->getId(), $this->tag2->getId(), $this->tag3->getId()],
			'testtype',
			1
		);
	}

	public function testGetObjectsForTagsLimitOffset(): void {
		$objectIds = $this->tagMapper->getObjectIdsForTags(
			[$this->tag1->getId()],
			'testtype',
			1,
			'1'
		);

		$this->assertEquals([
			2,
		], $objectIds);
	}


	public function testGetObjectsForNonExistingTag(): void {
		$this->expectException(TagNotFoundException::class);

		$this->tagMapper->getObjectIdsForTags(
			[100],
			'testtype'
		);
	}

	public function testAssignUnassignTags(): void {
		$this->tagMapper->unassignTags('1', 'testtype', [$this->tag1->getId()]);

		$tagIdMapping = $this->tagMapper->getTagIdsForObjects('1', 'testtype');
		$this->assertEquals([
			1 => [$this->tag2->getId()],
		], $tagIdMapping);

		$this->tagMapper->assignTags('1', 'testtype', [$this->tag1->getId()]);
		$this->tagMapper->assignTags('1', 'testtype', $this->tag3->getId());

		$tagIdMapping = $this->tagMapper->getTagIdsForObjects('1', 'testtype');

		$this->assertEquals([
			'1' => [$this->tag1->getId(), $this->tag2->getId(), $this->tag3->getId()],
		], $tagIdMapping);
	}

	public function testReAssignUnassignTags(): void {
		// reassign tag1
		$this->tagMapper->assignTags('1', 'testtype', [$this->tag1->getId()]);

		// tag 3 was never assigned
		$this->tagMapper->unassignTags('1', 'testtype', [$this->tag3->getId()]);

		$this->assertTrue(true, 'No error when reassigning/unassigning');
	}


	public function testAssignNonExistingTags(): void {
		$this->expectException(TagNotFoundException::class);

		$this->tagMapper->assignTags('1', 'testtype', [100]);
	}

	public function testAssignNonExistingTagInArray(): void {
		$caught = false;
		try {
			$this->tagMapper->assignTags('1', 'testtype', [100, $this->tag3->getId()]);
		} catch (TagNotFoundException $e) {
			$caught = true;
		}

		$this->assertTrue($caught, 'Exception thrown');

		$tagIdMapping = $this->tagMapper->getTagIdsForObjects(
			['1'],
			'testtype'
		);

		$this->assertEquals([
			'1' => [$this->tag1->getId(), $this->tag2->getId()],
		], $tagIdMapping, 'None of the tags got assigned');
	}


	public function testUnassignNonExistingTags(): void {
		$this->expectException(TagNotFoundException::class);

		$this->tagMapper->unassignTags('1', 'testtype', [100]);
	}

	public function testUnassignNonExistingTagsInArray(): void {
		$caught = false;
		try {
			$this->tagMapper->unassignTags('1', 'testtype', [100, $this->tag1->getId()]);
		} catch (TagNotFoundException $e) {
			$caught = true;
		}

		$this->assertTrue($caught, 'Exception thrown');

		$tagIdMapping = $this->tagMapper->getTagIdsForObjects(
			[1],
			'testtype'
		);

		$this->assertEquals([
			'1' => [$this->tag1->getId(), $this->tag2->getId()],
		], $tagIdMapping, 'None of the tags got unassigned');
	}

	public function testHaveTagAllMatches(): void {
		$this->assertTrue(
			$this->tagMapper->haveTag(
				['1'],
				'testtype',
				$this->tag1->getId(),
				true
			),
			'object 1 has the tag tag1'
		);

		$this->assertTrue(
			$this->tagMapper->haveTag(
				['1', '2'],
				'testtype',
				$this->tag1->getId(),
				true
			),
			'object 1 and object 2 ALL have the tag tag1'
		);

		$this->assertFalse(
			$this->tagMapper->haveTag(
				['1', '2'],
				'testtype',
				$this->tag2->getId(),
				true
			),
			'object 1 has tag2 but not object 2, so not ALL of them'
		);

		$this->assertFalse(
			$this->tagMapper->haveTag(
				['2'],
				'testtype',
				$this->tag2->getId(),
				true
			),
			'object 2 does not have tag2'
		);

		$this->assertFalse(
			$this->tagMapper->haveTag(
				['3'],
				'testtype',
				$this->tag2->getId(),
				true
			),
			'object 3 does not have tag1 due to different type'
		);
	}

	public function testHaveTagAtLeastOneMatch(): void {
		$this->assertTrue(
			$this->tagMapper->haveTag(
				['1'],
				'testtype',
				$this->tag1->getId(),
				false
			),
			'object1 has the tag tag1'
		);

		$this->assertTrue(
			$this->tagMapper->haveTag(
				['1', '2'],
				'testtype',
				$this->tag1->getId(),
				false
			),
			'object 1  and object 2 both the tag tag1'
		);

		$this->assertTrue(
			$this->tagMapper->haveTag(
				['1', '2'],
				'testtype',
				$this->tag2->getId(),
				false
			),
			'at least object 1 has the tag tag2'
		);

		$this->assertFalse(
			$this->tagMapper->haveTag(
				['2'],
				'testtype',
				$this->tag2->getId(),
				false
			),
			'object 2 does not have tag2'
		);

		$this->assertFalse(
			$this->tagMapper->haveTag(
				['3'],
				'testtype',
				$this->tag2->getId(),
				false
			),
			'object 3 does not have tag1 due to different type'
		);
	}


	public function testHaveTagNonExisting(): void {
		$this->expectException(TagNotFoundException::class);

		$this->tagMapper->haveTag(
			['1'],
			'testtype',
			100
		);
	}
}
