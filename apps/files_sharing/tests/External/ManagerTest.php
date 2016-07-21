<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Files_Sharing\Tests\External;

use OC\Files\Storage\StorageFactory;
use OCA\FederatedFileSharing\DiscoveryManager;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\External\MountProvider;
use OCA\Files_Sharing\Tests\TestCase;
use Test\Traits\UserTrait;

/**
 * Class ManagerTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests\External
 */
class ManagerTest extends TestCase {
	use UserTrait;

	/** @var Manager **/
	private $manager;

	/** @var \OC\Files\Mount\Manager */
	private $mountManager;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $httpHelper;

	private $uid;

	/**
	 * @var \OCP\IUser
	 */
	private $user;
	private $mountProvider;

	protected function setUp() {
		parent::setUp();

		$this->uid = $this->getUniqueID('user');
		$this->createUser($this->uid, '');
		$this->user = \OC::$server->getUserManager()->get($this->uid);
		$this->mountManager = new \OC\Files\Mount\Manager();
		$this->httpHelper = $httpHelper = $this->getMockBuilder('\OC\HTTPHelper')->disableOriginalConstructor()->getMock();
		$discoveryManager = new DiscoveryManager(
			\OC::$server->getMemCacheFactory(),
			\OC::$server->getHTTPClientService()
		);
		/** @var \OC\HTTPHelper $httpHelper */
		$this->manager = new Manager(
			\OC::$server->getDatabaseConnection(),
			$this->mountManager,
			new StorageFactory(),
			$httpHelper,
			\OC::$server->getNotificationManager(),
			$discoveryManager,
			$this->uid
		);
		$this->mountProvider = new MountProvider(\OC::$server->getDatabaseConnection(), function() {
			return $this->manager;
		});
	}

	private function setupMounts() {
		$mounts = $this->mountProvider->getMountsForUser($this->user, new StorageFactory());
		foreach ($mounts as $mount) {
			$this->mountManager->addMount($mount);
		}
	}

	public function testAddShare() {

		$shareData1 = [
			'remote' => 'http://localhost',
			'token' => 'token1',
			'password' => '',
			'name' => '/SharedFolder',
			'owner' => 'foobar',
			'accepted' => false,
			'user' => $this->uid,
		];
		$shareData2 = $shareData1;
		$shareData2['token'] = 'token2';
		$shareData3 = $shareData1;
		$shareData3['token'] = 'token3';

		// Add a share for "user"
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData1));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData1, $openShares[0], 1, '{{TemporaryMountPointName#' . $shareData1['name'] . '}}');

		$this->setupMounts();
		$this->assertNotMount('SharedFolder');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');

		// Add a second share for "user" with the same name
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData2));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(2, $openShares);
		$this->assertExternalShareEntry($shareData1, $openShares[0], 1, '{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		// New share falls back to "-1" appendix, because the name is already taken
		$this->assertExternalShareEntry($shareData2, $openShares[1], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1');

		$this->setupMounts();
		$this->assertNotMount('SharedFolder');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		$this->httpHelper->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('http://localhost/ocs/v1.php/cloud/shares/' . $openShares[0]['remote_id']), $this->anything());

		// Accept the first share
		$this->manager->acceptShare($openShares[0]['id']);

		// Check remaining shares - Accepted
		$acceptedShares = self::invokePrivate($this->manager, 'getShares', [true]);
		$this->assertCount(1, $acceptedShares);
		$shareData1['accepted'] = true;
		$this->assertExternalShareEntry($shareData1, $acceptedShares[0], 1, $shareData1['name']);
		// Check remaining shares - Open
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1');

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		// Add another share for "user" with the same name
		$this->assertSame(null, call_user_func_array([$this->manager, 'addShare'], $shareData3));
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(2, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1');
		// New share falls back to the original name (no "-\d", because the name is not taken)
		$this->assertExternalShareEntry($shareData3, $openShares[1], 3, '{{TemporaryMountPointName#' . $shareData3['name'] . '}}');

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		$this->httpHelper->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('http://localhost/ocs/v1.php/cloud/shares/' . $openShares[1]['remote_id'] . '/decline'), $this->anything());

		// Decline the third share
		$this->manager->declineShare($openShares[1]['id']);

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		// Check remaining shares - Accepted
		$acceptedShares = self::invokePrivate($this->manager, 'getShares', [true]);
		$this->assertCount(1, $acceptedShares);
		$shareData1['accepted'] = true;
		$this->assertExternalShareEntry($shareData1, $acceptedShares[0], 1, $shareData1['name']);
		// Check remaining shares - Open
		$openShares = $this->manager->getOpenShares();
		$this->assertCount(1, $openShares);
		$this->assertExternalShareEntry($shareData2, $openShares[0], 2, '{{TemporaryMountPointName#' . $shareData2['name'] . '}}-1');

		$this->setupMounts();
		$this->assertMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');

		$this->httpHelper->expects($this->at(0))
			->method('post')
			->with($this->stringStartsWith('http://localhost/ocs/v1.php/cloud/shares/' . $openShares[0]['remote_id'] . '/decline'), $this->anything());
		$this->httpHelper->expects($this->at(1))
			->method('post')
			->with($this->stringStartsWith('http://localhost/ocs/v1.php/cloud/shares/' . $acceptedShares[0]['remote_id'] . '/decline'), $this->anything());

		$this->manager->removeUserShares($this->uid);
		$this->assertEmpty(self::invokePrivate($this->manager, 'getShares', [null]), 'Asserting all shares for the user have been deleted');

		$this->mountManager->clear();
		self::invokePrivate($this->manager, 'setupMounts');
		$this->assertNotMount($shareData1['name']);
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}');
		$this->assertNotMount('{{TemporaryMountPointName#' . $shareData1['name'] . '}}-1');
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param int $share
	 * @param string $mountPoint
	 */
	protected function assertExternalShareEntry($expected, $actual, $share, $mountPoint) {
		$this->assertEquals($expected['remote'], $actual['remote'], 'Asserting remote of a share #' . $share);
		$this->assertEquals($expected['token'], $actual['share_token'], 'Asserting token of a share #' . $share);
		$this->assertEquals($expected['name'], $actual['name'], 'Asserting name of a share #' . $share);
		$this->assertEquals($expected['owner'], $actual['owner'], 'Asserting owner of a share #' . $share);
		$this->assertEquals($expected['accepted'], (int) $actual['accepted'], 'Asserting accept of a share #' . $share);
		$this->assertEquals($expected['user'], $actual['user'], 'Asserting user of a share #' . $share);
		$this->assertEquals($mountPoint, $actual['mountpoint'], 'Asserting mountpoint of a share #' . $share);
	}

	private function assertMount($mountPoint) {
		$mountPoint = rtrim($mountPoint, '/');
		$mount = $this->mountManager->find($this->getFullPath($mountPoint));
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Mount', $mount);
		$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
		$this->assertEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		$storage = $mount->getStorage();
		$this->assertInstanceOf('\OCA\Files_Sharing\External\Storage', $storage);
	}

	private function assertNotMount($mountPoint) {
		$mountPoint = rtrim($mountPoint, '/');
		$mount = $this->mountManager->find($this->getFullPath($mountPoint));
		if ($mount) {
			$this->assertInstanceOf('\OCP\Files\Mount\IMountPoint', $mount);
			$this->assertNotEquals($this->getFullPath($mountPoint), rtrim($mount->getMountPoint(), '/'));
		} else {
			$this->assertNull($mount);
		}
	}

	private function getFullPath($path) {
		return '/' . $this->uid . '/files' . $path;
	}
}
