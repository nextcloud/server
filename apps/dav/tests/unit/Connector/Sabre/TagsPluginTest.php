<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\Node;
use OCA\DAV\Connector\Sabre\TagList;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCA\DAV\Upload\UploadFile;
use OCP\ITagManager;
use OCP\ITags;
use Sabre\DAV\Exception\Locked;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Test\TestCase;

/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class TagsPluginTest extends TestCase {
	public const TAGS_PROPERTYNAME = TagsPlugin::TAGS_PROPERTYNAME;
	public const FAVORITE_PROPERTYNAME = TagsPlugin::FAVORITE_PROPERTYNAME;
	public const TAG_FAVORITE = TagsPlugin::TAG_FAVORITE;

	/**
	 * @var Tree
	 */
	private $tree;

	/**
	 * @var ITags
	 */
	private $tagger;

	/**
	 * @var TagsPlugin
	 */
	private $plugin;

	protected function setUp(): void {
		parent::setUp();
		$server = new Server();
		$this->tree = $this->createMock(Tree::class);
		$this->tagger = $this->createMock(ITags::class);
		$tagManager = $this->createMock(ITagManager::class);
		$tagManager->expects($this->any())
			->method('load')
			->with('files')
			->willReturn($this->tagger);
		$this->plugin = new TagsPlugin($this->tree, $tagManager);
		$this->plugin->initialize($server);
	}

	/**
	 * @dataProvider tagsGetPropertiesDataProvider
	 * @throws Locked|Forbidden
	 */
	public function testGetProperties(array $tags, array $requestedProperties, array $expectedProperties) {
		$node = $this->createMock(Node::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(123);

		$expectedCallCount = 0;
		if (count($requestedProperties) > 0) {
			$expectedCallCount = 1;
		}

		$this->tagger->expects($this->exactly($expectedCallCount))
			->method('getTagsForObjects')
			->with($this->equalTo([123]))
			->willReturn([123 => $tags]);

		$propFind = new PropFind(
			'/dummyPath',
			$requestedProperties,
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$result = $propFind->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($expectedProperties, $result);
	}

	/**
	 * @dataProvider tagsGetPropertiesDataProvider
	 * @throws Locked|Forbidden
	 */
	public function testPreloadThenGetProperties(array $tags, array $requestedProperties, array $expectedProperties) {
		$node1 = $this->createMock(File::class);
		$node1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$node2 = $this->createMock(File::class);
		$node2->expects($this->any())
			->method('getId')
			->willReturn(222);

		$expectedCallCount = 0;
		if (count($requestedProperties) > 0) {
			// this guarantees that getTagsForObjects
			// is only called once and then the tags
			// are cached
			$expectedCallCount = 1;
		}

		$node = $this->createMock(Directory::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(123);
		$node->expects($this->exactly($expectedCallCount))
			->method('getChildren')
			->willReturn([$node1, $node2]);

		$this->tagger->expects($this->exactly($expectedCallCount))
			->method('getTagsForObjects')
			->with($this->equalTo([123, 111, 222]))
			->willReturn(
				[
					111 => $tags,
					123 => $tags
				]
			);

		// simulate sabre recursive PROPFIND traversal
		$propFindRoot = new PropFind(
			'/subdir',
			$requestedProperties,
			1
		);
		$propFind1 = new PropFind(
			'/subdir/test.txt',
			$requestedProperties,
			0
		);
		$propFind2 = new PropFind(
			'/subdir/test2.txt',
			$requestedProperties,
			0
		);

		$this->plugin->handleGetProperties(
			$propFindRoot,
			$node
		);
		$this->plugin->handleGetProperties(
			$propFind1,
			$node1
		);
		$this->plugin->handleGetProperties(
			$propFind2,
			$node2
		);

		$result = $propFind1->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($expectedProperties, $result);
	}

	public function tagsGetPropertiesDataProvider(): array {
		return [
			// request both, receive both
			[
				['tag1', 'tag2', self::TAG_FAVORITE],
				[self::TAGS_PROPERTYNAME, self::FAVORITE_PROPERTYNAME],
				[
					200 => [
						self::TAGS_PROPERTYNAME => new TagList(['tag1', 'tag2']),
						self::FAVORITE_PROPERTYNAME => true,
					]
				]
			],
			// request tags alone
			[
				['tag1', 'tag2', self::TAG_FAVORITE],
				[self::TAGS_PROPERTYNAME],
				[
					200 => [
						self::TAGS_PROPERTYNAME => new TagList(['tag1', 'tag2']),
					]
				]
			],
			// request fav alone
			[
				['tag1', 'tag2', self::TAG_FAVORITE],
				[self::FAVORITE_PROPERTYNAME],
				[
					200 => [
						self::FAVORITE_PROPERTYNAME => true,
					]
				]
			],
			// request none
			[
				['tag1', 'tag2', self::TAG_FAVORITE],
				[],
				[
					200 => []
				],
			],
			// request both with none set, receive both
			[
				[],
				[self::TAGS_PROPERTYNAME, self::FAVORITE_PROPERTYNAME],
				[
					200 => [
						self::TAGS_PROPERTYNAME => new TagList([]),
						self::FAVORITE_PROPERTYNAME => false,
					]
				]
			],
		];
	}

	/**
	 * @throws Forbidden
	 * @throws Locked
	 */
	public function testGetPropertiesSkipChunks(): void {
		$sabreNode = $this->createMock(UploadFile::class);

		$propFind = new PropFind(
			'/dummyPath',
			[self::TAGS_PROPERTYNAME, self::TAG_FAVORITE],
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$sabreNode
		);

		$result = $propFind->getResultForMultiStatus();
		$this->assertCount(2, $result[404]);
	}

	/**
	 * @throws NotFound
	 */
	public function testUpdateTags() {
		// this test will replace the existing tags "tagremove" with "tag1" and "tag2"
		// and keep "tagkeep"
		$node = $this->createMock(Node::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(123);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/dummypath')
			->willReturn($node);

		$this->tagger->expects($this->once())
			->method('getTagsForObjects')
			->with($this->equalTo([123]))
			->willReturn([123 => ['tagkeep', 'tagremove', self::TAG_FAVORITE]]);

		// then tag as tag1 and tag2
		$this->tagger->expects($this->exactly(2))
			->method('tagAs')
			->withConsecutive(
				[123, 'tag1'],
				[123, 'tag2']
			);

		// it will untag tag3
		$this->tagger->expects($this->once())
			->method('unTag')
			->with(123, 'tagremove');

		// properties to set
		$propPatch = new PropPatch([
			self::TAGS_PROPERTYNAME => new TagList(['tag1', 'tag2', 'tagkeep'])
		]);

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		// all requested properties removed, as they were processed already
		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(200, $result[self::TAGS_PROPERTYNAME]);
		$this->assertFalse(isset($result[self::FAVORITE_PROPERTYNAME]));
	}

	/**
	 * @throws NotFound
	 */
	public function testUpdateTagsFromScratch() {
		$node = $this->createMock(Node::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(123);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/dummypath')
			->willReturn($node);

		$this->tagger->expects($this->once())
			->method('getTagsForObjects')
			->with($this->equalTo([123]))
			->willReturn([]);

		// then tag as tag1 and tag2
		$this->tagger->expects($this->exactly(3))
			->method('tagAs')
			->withConsecutive(
				[123, 'tag1'],
				[123, 'tag2'],
				[123, 'tagkeep']
			);

		// properties to set
		$propPatch = new PropPatch([
			self::TAGS_PROPERTYNAME => new TagList(['tag1', 'tag2', 'tagkeep'])
		]);

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		// all requested properties removed, as they were processed already
		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(200, $result[self::TAGS_PROPERTYNAME]);
		$this->assertFalse(false, isset($result[self::FAVORITE_PROPERTYNAME]));
	}

	/**
	 * @throws NotFound
	 */
	public function testUpdateFav() {
		// this test will replace the existing tags "tagremove" with "tag1" and "tag2"
		// and keep "tagkeep"
		$node = $this->createMock(Node::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(123);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/dummypath')
			->willReturn($node);

		// set favorite tag
		$this->tagger->expects($this->once())
			->method('tagAs')
			->with(123, self::TAG_FAVORITE);

		// properties to set
		$propPatch = new PropPatch([
			self::FAVORITE_PROPERTYNAME => true
		]);

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		// all requested properties removed, as they were processed already
		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertFalse(false, isset($result[self::TAGS_PROPERTYNAME]));
		$this->assertEquals(200, isset($result[self::FAVORITE_PROPERTYNAME]));

		// unfavorite now
		// set favorite tag
		$this->tagger->expects($this->once())
			->method('unTag')
			->with(123, self::TAG_FAVORITE);

		// properties to set
		$propPatch = new PropPatch([
			self::FAVORITE_PROPERTYNAME => false
		]);

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		// all requested properties removed, as they were processed already
		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertFalse(false, isset($result[self::TAGS_PROPERTYNAME]));
		$this->assertEquals(200, isset($result[self::FAVORITE_PROPERTYNAME]));
	}
}
