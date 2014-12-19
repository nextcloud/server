<?php

namespace Tests\Connector\Sabre;

/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class TagsPlugin extends \Test\TestCase {

	const TAGS_PROPERTYNAME = \OC\Connector\Sabre\TagsPlugin::TAGS_PROPERTYNAME;
	const FAVORITE_PROPERTYNAME = \OC\Connector\Sabre\TagsPlugin::FAVORITE_PROPERTYNAME;
	const TAG_FAVORITE = \OC\Connector\Sabre\TagsPlugin::TAG_FAVORITE;

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\ObjectTree
	 */
	private $tree;

	/**
	 * @var \OCP\ITagManager
	 */
	private $tagManager;

	/**
	 * @var \OCP\ITags
	 */
	private $tagger;

	/**
	 * @var \OC\Connector\Sabre\TagsPlugin
	 */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->server = new \Sabre\DAV\Server();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\ObjectTree')
			->disableOriginalConstructor()
			->getMock();
		$this->tagger = $this->getMock('\OCP\ITags');
		$this->tagManager = $this->getMock('\OCP\ITagManager');
		$this->tagManager->expects($this->any())
			->method('load')
			->with('files')
			->will($this->returnValue($this->tagger));
		$this->plugin = new \OC\Connector\Sabre\TagsPlugin($this->tree, $this->tagManager);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider tagsGetPropertiesDataProvider
	 */
	public function testGetProperties($tags, $requestedProperties, $expectedProperties) {
		$node = $this->getMockBuilder('\OC_Connector_Sabre_Node')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));

		$expectedCallCount = 0;
		if (count($requestedProperties) > 0) {
			$expectedCallCount = 1;
		}

		$this->tagger->expects($this->exactly($expectedCallCount))
			->method('getTagsForObjects')
			->with($this->equalTo(array(123)))
			->will($this->returnValue(array(123 => $tags)));

		$returnedProperties = array();

		$this->plugin->beforeGetProperties(
			'',
			$node,
			$requestedProperties,
			$returnedProperties
		);

		$this->assertEquals($expectedProperties, $returnedProperties);
	}

	/**
	 * @dataProvider tagsGetPropertiesDataProvider
	 */
	public function testPreloadThenGetProperties($tags, $requestedProperties, $expectedProperties) {
		$node1 = $this->getMockBuilder('\OC_Connector_Sabre_File')
			->disableOriginalConstructor()
			->getMock();
		$node1->expects($this->any())
			->method('getId')
			->will($this->returnValue(111));
		$node2 = $this->getMockBuilder('\OC_Connector_Sabre_File')
			->disableOriginalConstructor()
			->getMock();
		$node2->expects($this->any())
			->method('getId')
			->will($this->returnValue(222));

		$expectedCallCount = 0;
		if (count($requestedProperties) > 0) {
			// this guarantees that getTagsForObjects
			// is only called once and then the tags
			// are cached
			$expectedCallCount = 1;
		}

		$node = $this->getMockBuilder('\OC_Connector_Sabre_Directory')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		$node->expects($this->exactly($expectedCallCount))
			->method('getChildren')
			->will($this->returnValue(array($node1, $node2)));

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/subdir')
			->will($this->returnValue($node));

		$this->tagger->expects($this->exactly($expectedCallCount))
			->method('getTagsForObjects')
			->with($this->equalTo(array(111, 222)))
			->will($this->returnValue(
				array(
					111 => $tags,
					123 => $tags
				)
			));

		$returnedProperties = array();

		$this->plugin->beforeGetPropertiesForPath(
			'/subdir',
			$requestedProperties,
			1
		);

		$this->plugin->beforeGetProperties(
			'/subdir/test.txt',
			$node1,
			$requestedProperties,
			$returnedProperties
		);

		$this->assertEquals($expectedProperties, $returnedProperties);
	}

	function tagsGetPropertiesDataProvider() {
		return array(
			// request both, receive both
			array(
				array('tag1', 'tag2', self::TAG_FAVORITE),
				array(self::TAGS_PROPERTYNAME, self::FAVORITE_PROPERTYNAME),
				array(
					200 => array(
						self::TAGS_PROPERTYNAME => new \OC\Connector\Sabre\TagList(array('tag1', 'tag2')),
						self::FAVORITE_PROPERTYNAME => true,
					)
				)
			),
			// request tags alone
			array(
				array('tag1', 'tag2', self::TAG_FAVORITE),
				array(self::TAGS_PROPERTYNAME),
				array(
					200 => array(
						self::TAGS_PROPERTYNAME => new \OC\Connector\Sabre\TagList(array('tag1', 'tag2')),
					)
				)
			),
			// request fav alone
			array(
				array('tag1', 'tag2', self::TAG_FAVORITE),
				array(self::FAVORITE_PROPERTYNAME),
				array(
					200 => array(
						self::FAVORITE_PROPERTYNAME => true,
					)
				)
			),
			// request none
			array(
				array('tag1', 'tag2', self::TAG_FAVORITE),
				array(),
				array(),
			),
			// request both with none set, receive both
			array(
				array(),
				array(self::TAGS_PROPERTYNAME, self::FAVORITE_PROPERTYNAME),
				array(
					200 => array(
						self::TAGS_PROPERTYNAME => new \OC\Connector\Sabre\TagList(array()),
						self::FAVORITE_PROPERTYNAME => false,
					)
				)
			),
		);
	}

	public function testUpdateTags() {
		// this test will replace the existing tags "tagremove" with "tag1" and "tag2"
		// and keep "tagkeep"
		$node = $this->getMockBuilder('\OC_Connector_Sabre_Node')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));

		$this->tagger->expects($this->at(0))
			->method('getTagsForObjects')
			->with($this->equalTo(array(123)))
			->will($this->returnValue(array(123 => array('tagkeep', 'tagremove', self::TAG_FAVORITE))));

		// then tag as tag1 and tag2
		$this->tagger->expects($this->at(1))
			->method('tagAs')
			->with(123, 'tag1');
		$this->tagger->expects($this->at(2))
			->method('tagAs')
			->with(123, 'tag2');

		// it will untag tag3
		$this->tagger->expects($this->at(3))
			->method('unTag')
			->with(123, 'tagremove');

		// properties to set
		$properties = array(
			self::TAGS_PROPERTYNAME => new \OC\Connector\Sabre\TagList(array('tag1', 'tag2', 'tagkeep'))
		);
		$result = array();

		$this->plugin->updateProperties(
			$properties,
			$result,
			$node
		);

		// all requested properties removed, as they were processed already
		$this->assertEmpty($properties);

		$this->assertEquals(
			new \OC\Connector\Sabre\TagList(array('tag1', 'tag2', 'tagkeep')),
			$result[200][self::TAGS_PROPERTYNAME]
		);
		$this->assertFalse(isset($result[200][self::FAVORITE_PROPERTYNAME]));
	}

	public function testUpdateFav() {
		// this test will replace the existing tags "tagremove" with "tag1" and "tag2"
		// and keep "tagkeep"
		$node = $this->getMockBuilder('\OC_Connector_Sabre_Node')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));

		// set favorite tag
		$this->tagger->expects($this->once())
			->method('tagAs')
			->with(123, self::TAG_FAVORITE);

		// properties to set
		$properties = array(
			self::FAVORITE_PROPERTYNAME => true
		);
		$result = array();
		$this->plugin->updateProperties(
			$properties,
			$result,
			$node
		);

		// all requested properties removed, as they were processed already
		$this->assertEmpty($properties);

		$this->assertTrue($result[200][self::FAVORITE_PROPERTYNAME]);
		$this->assertFalse(isset($result[200][self::TAGS_PROPERTYNAME]));

		// unfavorite now
		// set favorite tag
		$this->tagger->expects($this->once())
			->method('unTag')
			->with(123, self::TAG_FAVORITE);

		$properties = array(
			self::FAVORITE_PROPERTYNAME => false
		);
		$result = array();
		$this->plugin->updateProperties(
			$properties,
			$result,
			$node
		);

		$this->assertFalse($result[200][self::FAVORITE_PROPERTYNAME]);
		$this->assertFalse(isset($result[200][self::TAGS_PROPERTYNAME]));
	}

}
