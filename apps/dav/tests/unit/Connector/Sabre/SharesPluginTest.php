<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Maxence Lange <maxence@nextcloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\Node;
use OCP\Files\Folder;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Sabre\DAV\Tree;

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
		$this->tree = $this->getMockBuilder(Tree::class)
			->disableOriginalConstructor()
			->getMock();
		$this->shareManager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('user1'));
		$userSession = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->getMock();
		$userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->userFolder = $this->getMockBuilder(Folder::class)
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
		$sabreNode = $this->getMockBuilder(Node::class)
			->disableOriginalConstructor()
			->getMock();
		$sabreNode->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		$sabreNode->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/subdir'));

		// node API nodes
		$node = $this->getMockBuilder(Folder::class)
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
		$sabreNode1 = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$sabreNode1->expects($this->any())
			->method('getId')
			->will($this->returnValue(111));
		$sabreNode1->expects($this->any())
			->method('getPath');
		$sabreNode2 = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$sabreNode2->expects($this->any())
			->method('getId')
			->will($this->returnValue(222));
		$sabreNode2->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/subdir/foo'));

		$sabreNode = $this->getMockBuilder(Directory::class)
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
		$node = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));
		$node1 = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$node1->expects($this->any())
			->method('getId')
			->will($this->returnValue(111));
		$node2 = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$node2->expects($this->any())
			->method('getId')
			->will($this->returnValue(222));

		$this->userFolder->expects($this->once())
			->method('get')
			->with('/subdir')
			->will($this->returnValue($node));
		
		$dummyShares = array_map(function($type) {
			$share = $this->getMockBuilder(IShare::class)->getMock();
			$share->expects($this->any())
				->method('getShareType')
				->will($this->returnValue($type));
			return $share;
		}, $shareTypes);

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

		$this->shareManager->expects($this->any())
			->method('getSharesInFolder')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->equalTo(true)
			)
			->will($this->returnCallback(function ($userId, $node, $flag) use ($shareTypes, $dummyShares) {
				return [111 => $dummyShares];
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
			[[\OCP\Share::SHARE_TYPE_ROOM]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_LINK]],
			[[\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_REMOTE]],
		];
	}
}
