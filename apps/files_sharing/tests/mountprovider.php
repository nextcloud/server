<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\MountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\Share\IShare;
use OCP\Share\IManager;
use OCP\Files\Mount\IMountPoint;

/**
 * @group DB
 */
class MountProviderTest extends \Test\TestCase {

	/** @var MountProvider */
	private $provider;

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var IUser|\PHPUnit_Framework_MockObject_MockObject */
	private $user;

	/** @var IStorageFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $loader;

	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMock('OCP\IConfig');
		$this->user = $this->getMock('OCP\IUser');
		$this->loader = $this->getMock('OCP\Files\Storage\IStorageFactory');
		$this->loader->expects($this->any())
			->method('getInstance')
			->will($this->returnCallback(function($mountPoint, $class, $arguments) {
				$storage = $this->getMockBuilder('OC\Files\Storage\Shared')
					->disableOriginalConstructor()
					->getMock();
				$storage->expects($this->any())
					->method('getShare')
					->will($this->returnValue($arguments['share']));
				return $storage;
			}));
		$this->logger = $this->getMock('\OCP\ILogger');
		$this->logger->expects($this->never())
			->method('error');

		$this->provider = $this->getMockBuilder('OCA\Files_Sharing\MountProvider')
			->setMethods(['getItemsSharedWithUser'])
			->setConstructorArgs([$this->config, $this->logger])
			->getMock();
	}

	private function makeMockShare($id, $nodeId, $owner = 'user2', $target = null, $permissions = 31, $shareType) {
		return [
			'id' => $id,
			'uid_owner' => $owner,
			'share_type' => $shareType,
			'item_type' => 'file',
			'file_target' => $target,
			'file_source' => $nodeId,
			'item_target' => null,
			'item_source' => $nodeId,
			'permissions' => $permissions,
			'stime' => time(),
			'token' => null,
			'expiration' => null,
		];
	}

	/**
	 * Tests excluding shares from the current view. This includes:
	 * - shares that were opted out of (permissions === 0)
	 * - shares with a group in which the owner is already in
	 */
	public function testExcludeShares() {
		$userShares = [
			$this->makeMockShare(1, 100, 'user2', '/share2', 0, \OCP\Share::SHARE_TYPE_USER), 
			$this->makeMockShare(2, 100, 'user2', '/share2', 31, \OCP\Share::SHARE_TYPE_USER),
		];

		$groupShares = [
			$this->makeMockShare(3, 100, 'user2', '/share2', 0, \OCP\Share::SHARE_TYPE_GROUP), 
			$this->makeMockShare(4, 100, 'user2', '/share4', 31, \OCP\Share::SHARE_TYPE_GROUP), 
		];

		$this->user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('user1'));

		$allShares = array_merge($userShares, $groupShares);

		$this->provider->expects($this->once())
			->method('getItemsSharedWithUser')
			->with('user1')
			->will($this->returnValue($allShares));

		$mounts = $this->provider->getMountsForUser($this->user, $this->loader);

		$this->assertCount(2, $mounts);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[0]);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[1]);

		$mountedShare1 = $mounts[0]->getShare();

		$this->assertEquals('2', $mountedShare1['id']);
		$this->assertEquals('user2', $mountedShare1['uid_owner']);
		$this->assertEquals(100, $mountedShare1['file_source']);
		$this->assertEquals('/share2', $mountedShare1['file_target']);
		$this->assertEquals(31, $mountedShare1['permissions']);

		$mountedShare2 = $mounts[1]->getShare();
		$this->assertEquals('4', $mountedShare2['id']);
		$this->assertEquals('user2', $mountedShare2['uid_owner']);
		$this->assertEquals(100, $mountedShare2['file_source']);
		$this->assertEquals('/share4', $mountedShare2['file_target']);
		$this->assertEquals(31, $mountedShare2['permissions']);
	}
}

