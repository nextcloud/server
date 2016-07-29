<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

class SharesPluginTest extends \Test\TestCase {

	const SHARETYPES_PROPERTYNAME = \OCA\DAV\Connector\Sabre\SharesPlugin::SHARETYPES_PROPERTYNAME;

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @var \OCP\Share\IManager
	 */
	private $shareManager;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	/**
	 * @var \OCA\DAV\Connector\Sabre\SharesPlugin
	 */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->server = new \Sabre\DAV\Server();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();
		$this->shareManager = $this->getMockBuilder('\OCP\Share\IManager')
			->disableOriginalConstructor()
			->getMock();
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('user1'));
		$userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->userFolder = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();

		$this->plugin = new \OCA\DAV\Connector\Sabre\SharesPlugin(
			$this->tree,
			$userSession,
			$this->userFolder,
			$this->shareManager
		);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider sharesGetPropertiesDataProvider
	 */
	public function testGetProperties($shareTypes) {
		$sabreNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Node')
			->disableOriginalConstructor()
			->getMock();
		$sabreNode->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		$sabreNode->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/subdir'));

		// node API nodes
		$node = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();

		$this->userFolder->expects($this->once())
			->method('get')
			->with('/subdir')
			->will($this->returnValue($node));

		$this->shareManager->expects($this->any())
			->method('getSharesBy')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->anything(),
				$this->equalTo(false),
				$this->equalTo(1)
			)
			->will($this->returnCallback(function($userId, $requestedShareType, $node, $flag, $limit) use ($shareTypes){
				if (in_array($requestedShareType, $shareTypes)) {
					return ['dummyshare'];
				}
				return [];
			}));

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$sabreNode
		);

		$result = $propFind->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($shareTypes, $result[200][self::SHARETYPES_PROPERTYNAME]->getShareTypes());
	}

	/**
	 * @dataProvider sharesGetPropertiesDataProvider
	 */
	public function testPreloadThenGetProperties($shareTypes) {
		$sabreNode1 = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\File')
			->disableOriginalConstructor()
			->getMock();
		$sabreNode1->expects($this->any())
			->method('getId')
			->will($this->returnValue(111));
		$sabreNode1->expects($this->never())
			->method('getPath');
		$sabreNode2 = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\File')
			->disableOriginalConstructor()
			->getMock();
		$sabreNode2->expects($this->any())
			->method('getId')
			->will($this->returnValue(222));
		$sabreNode2->expects($this->never())
			->method('getPath');

		$sabreNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();
		$sabreNode->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		// never, because we use getDirectoryListing from the Node API instead
		$sabreNode->expects($this->never())
			->method('getChildren');
		$sabreNode->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/subdir'));

		// node API nodes
		$node = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		$node1 = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$node1->expects($this->any())
			->method('getId')
			->will($this->returnValue(111));
		$node2 = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$node2->expects($this->any())
			->method('getId')
			->will($this->returnValue(222));
		$node->expects($this->once())
			->method('getDirectoryListing')
			->will($this->returnValue([$node1, $node2]));

		$this->userFolder->expects($this->once())
			->method('get')
			->with('/subdir')
			->will($this->returnValue($node));

		$this->shareManager->expects($this->any())
			->method('getSharesBy')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->anything(),
				$this->equalTo(false),
				$this->equalTo(1)
			)
			->will($this->returnCallback(function($userId, $requestedShareType, $node, $flag, $limit) use ($shareTypes){
				if ($node->getId() === 111 && in_array($requestedShareType, $shareTypes)) {
					return ['dummyshare'];
				}

				return [];
			}));

		// simulate sabre recursive PROPFIND traversal
		$propFindRoot = new \Sabre\DAV\PropFind(
			'/subdir',
			[self::SHARETYPES_PROPERTYNAME],
			1
		);
		$propFind1 = new \Sabre\DAV\PropFind(
			'/subdir/test.txt',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);
		$propFind2 = new \Sabre\DAV\PropFind(
			'/subdir/test2.txt',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);

		$this->plugin->handleGetProperties(
			$propFindRoot,
			$sabreNode
		);
		$this->plugin->handleGetProperties(
			$propFind1,
			$sabreNode1
		);
		$this->plugin->handleGetProperties(
			$propFind2,
			$sabreNode2
		);

		$result = $propFind1->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($shareTypes, $result[200][self::SHARETYPES_PROPERTYNAME]->getShareTypes());
	}

	function sharesGetPropertiesDataProvider() {
		return [
			[[]],
			[[\OCP\Share::SHARE_TYPE_USER]],
			[[\OCP\Share::SHARE_TYPE_GROUP]],
			[[\OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_REMOTE]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_REMOTE]],
		];
	}
}
