<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;

	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('OCP\IConfig')->getMock();
		$this->user = $this->getMockBuilder('OCP\IUser')->getMock();
		$this->loader = $this->getMockBuilder('OCP\Files\Storage\IStorageFactory')->getMock();
		$this->shareManager = $this->getMockBuilder('\OCP\Share\IManager')->getMock();
		$this->logger = $this->getMockBuilder('\OCP\ILogger')->getMock();

		$this->provider = new MountProvider($this->config, $this->shareManager, $this->logger);
	}

	private function makeMockShare($id, $nodeId, $owner = 'user2', $target = null, $permissions = 31) {
		$share = $this->getMock('\OCP\Share\IShare');
		$share->expects($this->any())
			->method('getPermissions')
			->will($this->returnValue($permissions));
		$share->expects($this->any())
			->method('getShareOwner')
			->will($this->returnValue($owner));
		$share->expects($this->any())
			->method('getTarget')
			->will($this->returnValue($target));
		$share->expects($this->any())
			->method('getId')
			->will($this->returnValue($id));
		$share->expects($this->any())
			->method('getNodeId')
			->will($this->returnValue($nodeId));
		return $share;
	}

	public function testExcludeShares() {
		$rootFolder = $this->getMock('\OCP\Files\IRootFolder');
		$userManager = $this->getMock('\OCP\IUserManager');
		$userShares = [
			$this->makeMockShare(1, 100, 'user2', '/share2', 0),
			$this->makeMockShare(2, 100, 'user2', '/share2', 31),
		];
		$groupShares = [
			$this->makeMockShare(3, 100, 'user2', '/share2', 0),
			$this->makeMockShare(4, 100, 'user2', '/share4', 31),
			$this->makeMockShare(5, 100, 'user1', '/share4', 31),
		];
		$this->user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('user1'));
		$this->shareManager->expects($this->at(0))
			->method('getSharedWith')
			->with('user1', \OCP\Share::SHARE_TYPE_USER)
			->will($this->returnValue($userShares));
		$this->shareManager->expects($this->at(1))
			->method('getSharedWith')
			->with('user1', \OCP\Share::SHARE_TYPE_GROUP, null, -1)
			->will($this->returnValue($groupShares));
		$this->shareManager->expects($this->any())
			->method('newShare')
			->will($this->returnCallback(function() use ($rootFolder, $userManager) {
				return new \OC\Share20\Share($rootFolder, $userManager);
			}));
		$mounts = $this->provider->getMountsForUser($this->user, $this->loader);
		$this->assertCount(2, $mounts);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[0]);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[1]);
		$mountedShare1 = $mounts[0]->getShare();
		$this->assertEquals('2', $mountedShare1->getId());
		$this->assertEquals('user2', $mountedShare1->getShareOwner());
		$this->assertEquals(100, $mountedShare1->getNodeId());
		$this->assertEquals('/share2', $mountedShare1->getTarget());
		$this->assertEquals(31, $mountedShare1->getPermissions());
		$mountedShare2 = $mounts[1]->getShare();
		$this->assertEquals('4', $mountedShare2->getId());
		$this->assertEquals('user2', $mountedShare2->getShareOwner());
		$this->assertEquals(100, $mountedShare2->getNodeId());
		$this->assertEquals('/share4', $mountedShare2->getTarget());
		$this->assertEquals(31, $mountedShare2->getPermissions());
	}
}

