<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Tests\Files_External;

use OC\Files\Filesystem;
use OC\User\User;

class EtagPropagator extends \Test\TestCase {
	protected function getUser() {
		return new User($this->getUniqueID(), null);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject | \OC\Files\Cache\ChangePropagator
	 */
	protected function getChangePropagator() {
		return $this->getMockBuilder('\OC\Files\Cache\ChangePropagator')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject | \OCP\IConfig
	 */
	protected function getConfig() {
		$appConfig = array();
		$userConfig = array();
		$mock = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

		$mock->expects($this->any())
			->method('getAppValue')
			->will($this->returnCallback(function ($appId, $key, $default = null) use (&$appConfig) {
				if (isset($appConfig[$appId]) and isset($appConfig[$appId][$key])) {
					return $appConfig[$appId][$key];
				} else {
					return $default;
				}
			}));
		$mock->expects($this->any())
			->method('setAppValue')
			->will($this->returnCallback(function ($appId, $key, $value) use (&$appConfig) {
				if (!isset($appConfig[$appId])) {
					$appConfig[$appId] = array();
				}
				$appConfig[$appId][$key] = $value;
			}));
		$mock->expects($this->any())
			->method('getAppKeys')
			->will($this->returnCallback(function ($appId) use (&$appConfig) {
				if (!isset($appConfig[$appId])) {
					$appConfig[$appId] = array();
				}
				return array_keys($appConfig[$appId]);
			}));

		$mock->expects($this->any())
			->method('getUserValue')
			->will($this->returnCallback(function ($userId, $appId, $key, $default = null) use (&$userConfig) {
				if (isset($userConfig[$userId]) and isset($userConfig[$userId][$appId]) and isset($userConfig[$userId][$appId][$key])) {
					return $userConfig[$userId][$appId][$key];
				} else {
					return $default;
				}
			}));
		$mock->expects($this->any())
			->method('setUserValue')
			->will($this->returnCallback(function ($userId, $appId, $key, $value) use (&$userConfig) {
				if (!isset($userConfig[$userId])) {
					$userConfig[$userId] = array();
				}
				if (!isset($userConfig[$userId][$appId])) {
					$userConfig[$userId][$appId] = array();
				}
				$userConfig[$userId][$appId][$key] = $value;
			}));

		return $mock;
	}

	public function testSingleUserMount() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$changePropagator->expects($this->once())
			->method('addChange')
			->with('/test');
		$changePropagator->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator->updateHook(array(
			Filesystem::signal_param_path => '/test',
			Filesystem::signal_param_mount_type => \OC_Mount_Config::MOUNT_TYPE_USER,
			Filesystem::signal_param_users => $user->getUID(),
		), $time);
	}

	public function testGlobalMountNoDirectUpdate() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		// not updated directly
		$changePropagator->expects($this->never())
			->method('addChange');
		$changePropagator->expects($this->never())
			->method('propagateChanges');

		$propagator->updateHook(array(
			Filesystem::signal_param_path => '/test',
			Filesystem::signal_param_mount_type => \OC_Mount_Config::MOUNT_TYPE_USER,
			Filesystem::signal_param_users => 'all',
		), $time);

		// mount point marked as dirty
		$this->assertEquals(array('/test'), $config->getAppKeys('files_external'));
		$this->assertEquals($time, $config->getAppValue('files_external', '/test'));
	}

	public function testGroupMountNoDirectUpdate() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		// not updated directly
		$changePropagator->expects($this->never())
			->method('addChange');
		$changePropagator->expects($this->never())
			->method('propagateChanges');

		$propagator->updateHook(array(
			Filesystem::signal_param_path => '/test',
			Filesystem::signal_param_mount_type => \OC_Mount_Config::MOUNT_TYPE_GROUP,
			Filesystem::signal_param_users => 'test',
		), $time);

		// mount point marked as dirty
		$this->assertEquals(array('/test'), $config->getAppKeys('files_external'));
		$this->assertEquals($time, $config->getAppValue('files_external', '/test'));
	}

	public function testGlobalMountNoDirtyMountPoint() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$changePropagator->expects($this->never())
			->method('addChange');
		$changePropagator->expects($this->never())
			->method('propagateChanges');

		$propagator->propagateDirtyMountPoints($time);

		$this->assertEquals(0, $config->getUserValue($user->getUID(), 'files_external', '/test', 0));
	}

	public function testGlobalMountDirtyMountPointFirstTime() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$config->setAppValue('files_external', '/test', $time - 10);

		$changePropagator->expects($this->once())
			->method('addChange')
			->with('/test');
		$changePropagator->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator->propagateDirtyMountPoints($time);

		$this->assertEquals($time, $config->getUserValue($user->getUID(), 'files_external', '/test'));
	}

	public function testGlobalMountNonDirtyMountPoint() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$config->setAppValue('files_external', '/test', $time - 10);
		$config->setUserValue($user->getUID(), 'files_external', '/test', $time - 10);

		$changePropagator->expects($this->never())
			->method('addChange');
		$changePropagator->expects($this->never())
			->method('propagateChanges');

		$propagator->propagateDirtyMountPoints($time);

		$this->assertEquals($time - 10, $config->getUserValue($user->getUID(), 'files_external', '/test'));
	}

	public function testGlobalMountNonDirtyMountPointOtherUser() {
		$time = time();
		$user = $this->getUser();
		$user2 = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$config->setAppValue('files_external', '/test', $time - 10);
		$config->setUserValue($user2->getUID(), 'files_external', '/test', $time - 10);

		$changePropagator->expects($this->once())
			->method('addChange')
			->with('/test');
		$changePropagator->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator->propagateDirtyMountPoints($time);

		$this->assertEquals($time, $config->getUserValue($user->getUID(), 'files_external', '/test'));
	}

	public function testGlobalMountDirtyMountPointSecondTime() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$config->setAppValue('files_external', '/test', $time - 10);
		$config->setUserValue($user->getUID(), 'files_external', '/test', $time - 20);

		$changePropagator->expects($this->once())
			->method('addChange')
			->with('/test');
		$changePropagator->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator->propagateDirtyMountPoints($time);

		$this->assertEquals($time, $config->getUserValue($user->getUID(), 'files_external', '/test'));
	}

	public function testGlobalMountMultipleUsers() {
		$time = time();
		$config = $this->getConfig();
		$user1 = $this->getUser();
		$user2 = $this->getUser();
		$user3 = $this->getUser();
		$changePropagator1 = $this->getChangePropagator();
		$changePropagator2 = $this->getChangePropagator();
		$changePropagator3 = $this->getChangePropagator();
		$propagator1 = new \OCA\Files_External\EtagPropagator($user1, $changePropagator1, $config);
		$propagator2 = new \OCA\Files_External\EtagPropagator($user2, $changePropagator2, $config);
		$propagator3 = new \OCA\Files_External\EtagPropagator($user3, $changePropagator3, $config);

		$config->setAppValue('files_external', '/test', $time - 10);

		$changePropagator1->expects($this->once())
			->method('addChange')
			->with('/test');
		$changePropagator1->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator1->propagateDirtyMountPoints($time);

		$this->assertEquals($time, $config->getUserValue($user1->getUID(), 'files_external', '/test'));
		$this->assertEquals(0, $config->getUserValue($user2->getUID(), 'files_external', '/test', 0));
		$this->assertEquals(0, $config->getUserValue($user3->getUID(), 'files_external', '/test', 0));

		$changePropagator2->expects($this->once())
			->method('addChange')
			->with('/test');
		$changePropagator2->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator2->propagateDirtyMountPoints($time);

		$this->assertEquals($time, $config->getUserValue($user1->getUID(), 'files_external', '/test'));
		$this->assertEquals($time, $config->getUserValue($user2->getUID(), 'files_external', '/test', 0));
		$this->assertEquals(0, $config->getUserValue($user3->getUID(), 'files_external', '/test', 0));
	}

	public function testGlobalMountMultipleDirtyMountPoints() {
		$time = time();
		$user = $this->getUser();
		$config = $this->getConfig();
		$changePropagator = $this->getChangePropagator();
		$propagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, $config);

		$config->setAppValue('files_external', '/test', $time - 10);
		$config->setAppValue('files_external', '/foo', $time - 50);
		$config->setAppValue('files_external', '/bar', $time - 70);

		$config->setUserValue($user->getUID(), 'files_external', '/foo', $time - 70);
		$config->setUserValue($user->getUID(), 'files_external', '/bar', $time - 70);

		$changePropagator->expects($this->exactly(2))
			->method('addChange');
		$changePropagator->expects($this->once())
			->method('propagateChanges')
			->with($time);

		$propagator->propagateDirtyMountPoints($time);

		$this->assertEquals($time, $config->getUserValue($user->getUID(), 'files_external', '/test'));
		$this->assertEquals($time, $config->getUserValue($user->getUID(), 'files_external', '/foo'));
		$this->assertEquals($time - 70, $config->getUserValue($user->getUID(), 'files_external', '/bar'));
	}
}
