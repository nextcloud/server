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

	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;

	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMock('OCP\IConfig');
		$this->user = $this->getMock('OCP\IUser');
		$this->loader = $this->getMock('OCP\Files\Storage\IStorageFactory');
		$this->shareManager = $this->getMock('\OCP\Share\IManager');
		$this->logger = $this->getMock('\OCP\ILogger');

		$this->provider = new MountProvider($this->config, $this->shareManager, $this->logger);
	}

	public function testExcludeShares() {
		/** @var IShare | \PHPUnit_Framework_MockObject_MockObject $share1 */
		$share1 = $this->getMock('\OCP\Share\IShare');
		$share1->expects($this->once())
			->method('getPermissions')
			->will($this->returnValue(0));

		$share2 = $this->getMock('\OCP\Share\IShare');
		$share2->expects($this->once())
			->method('getPermissions')
			->will($this->returnValue(31));
		$share2->expects($this->any())
			->method('getShareOwner')
			->will($this->returnValue('user2'));
		$share2->expects($this->any())
			->method('getTarget')
			->will($this->returnValue('/share2'));

		$share3 = $this->getMock('\OCP\Share\IShare');
		$share3->expects($this->once())
			->method('getPermissions')
			->will($this->returnValue(0));

		/** @var IShare | \PHPUnit_Framework_MockObject_MockObject $share4 */
		$share4 = $this->getMock('\OCP\Share\IShare');
		$share4->expects($this->once())
			->method('getPermissions')
			->will($this->returnValue(31));
		$share4->expects($this->any())
			->method('getShareOwner')
			->will($this->returnValue('user2'));
		$share4->expects($this->any())
			->method('getTarget')
			->will($this->returnValue('/share4'));

		$share5 = $this->getMock('\OCP\Share\IShare');
		$share5->expects($this->once())
			->method('getPermissions')
			->will($this->returnValue(31));
		$share5->expects($this->any())
			->method('getShareOwner')
			->will($this->returnValue('user1'));

		$userShares = [$share1, $share2];
		$groupShares = [$share3, $share4, $share5];

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

		$mounts = $this->provider->getMountsForUser($this->user, $this->loader);

		$this->assertCount(2, $mounts);
		$this->assertSharedMount($share1, $mounts[0]);
		$this->assertSharedMount($share4, $mounts[1]);
	}

	private function assertSharedMount(IShare $share, IMountPoint $mount) {
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mount);
		$this->assertEquals($share, $mount->getShare());
	}
}

