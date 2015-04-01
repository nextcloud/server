<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC\Group\Group;
use OC\User\User;

class Manager extends \PHPUnit_Framework_TestCase {
	/**
	 * @return \OCP\IAppConfig | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getAppConfig() {
		$appConfig = array();
		$config = $this->getMockBuilder('\OCP\IAppConfig')
			->disableOriginalConstructor()
			->getMock();

		$config->expects($this->any())
			->method('getValue')
			->will($this->returnCallback(function ($app, $key, $default) use (&$appConfig) {
				return (isset($appConfig[$app]) and isset($appConfig[$app][$key])) ? $appConfig[$app][$key] : $default;
			}));
		$config->expects($this->any())
			->method('setValue')
			->will($this->returnCallback(function ($app, $key, $value) use (&$appConfig) {
				if (!isset($appConfig[$app])) {
					$appConfig[$app] = array();
				}
				$appConfig[$app][$key] = $value;
			}));
		$config->expects($this->any())
			->method('getValues')
			->will($this->returnCallback(function ($app, $key) use (&$appConfig) {
				if ($app) {
					return $appConfig[$app];
				} else {
					$values = array();
					foreach ($appConfig as $app => $appData) {
						if (isset($appData[$key])) {
							$values[$app] = $appData[$key];
						}
					}
					return $values;
				}
			}));

		return $config;
	}

	/** @var \OCP\IUserSession */
	protected $userSession;

	/** @var \OCP\IGroupManager */
	protected $groupManager;

	/** @var \OCP\IAppConfig */
	protected $appConfig;

	/** @var \OCP\ICache */
	protected $cache;

	/** @var \OCP\ICacheFactory */
	protected $cacheFactory;

	/** @var \OCP\App\IAppManager */
	protected $manager;

	protected function setUp() {
		parent::setUp();

		$this->userSession = $this->getMock('\OCP\IUserSession');
		$this->groupManager = $this->getMock('\OCP\IGroupManager');
		$this->appConfig = $this->getAppConfig();
		$this->cacheFactory = $this->getMock('\OCP\ICacheFactory');
		$this->cache = $this->getMock('\OCP\ICache');
		$this->cacheFactory->expects($this->any())
			->method('create')
			->with('settings')
			->willReturn($this->cache);
		$this->manager = new \OC\App\AppManager($this->userSession, $this->appConfig, $this->groupManager, $this->cacheFactory);
	}

	protected function expectClearCache() {
		$this->cache->expects($this->once())
			->method('clear')
			->with('listApps');
	}

	public function testEnableApp() {
		$this->expectClearCache();
		$this->manager->enableApp('test');
		$this->assertEquals('yes', $this->appConfig->getValue('test', 'enabled', 'no'));
	}

	public function testDisableApp() {
		$this->expectClearCache();
		$this->manager->disableApp('test');
		$this->assertEquals('no', $this->appConfig->getValue('test', 'enabled', 'no'));
	}

	public function testEnableAppForGroups() {
		$groups = array(
			new Group('group1', array(), null),
			new Group('group2', array(), null)
		);
		$this->expectClearCache();
		$this->manager->enableAppForGroups('test', $groups);
		$this->assertEquals('["group1","group2"]', $this->appConfig->getValue('test', 'enabled', 'no'));
	}

	public function testIsInstalledEnabled() {
		$this->appConfig->setValue('test', 'enabled', 'yes');
		$this->assertTrue($this->manager->isInstalled('test'));
	}

	public function testIsInstalledDisabled() {
		$this->appConfig->setValue('test', 'enabled', 'no');
		$this->assertFalse($this->manager->isInstalled('test'));
	}

	public function testIsInstalledEnabledForGroups() {
		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertTrue($this->manager->isInstalled('test'));
	}

	public function testIsEnabledForUserEnabled() {
		$this->appConfig->setValue('test', 'enabled', 'yes');
		$user = new User('user1', null);
		$this->assertTrue($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserDisabled() {
		$this->appConfig->setValue('test', 'enabled', 'no');
		$user = new User('user1', null);
		$this->assertFalse($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserEnabledForGroup() {
		$user = new User('user1', null);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(array('foo', 'bar')));

		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertTrue($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserDisabledForGroup() {
		$user = new User('user1', null);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(array('bar')));

		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertFalse($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserLoggedOut() {
		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertFalse($this->manager->IsEnabledForUser('test'));
	}

	public function testIsEnabledForUserLoggedIn() {
		$user = new User('user1', null);

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(array('foo', 'bar')));

		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertTrue($this->manager->isEnabledForUser('test'));
	}

	public function testGetInstalledApps() {
		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'no');
		$this->appConfig->setValue('test3', 'enabled', '["foo"]');
		$this->assertEquals(['test1', 'test3'], $this->manager->getInstalledApps());
	}

	public function testGetAppsForUser() {
		$user = new User('user1', null);
		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->with($user)
			->will($this->returnValue(array('foo', 'bar')));

		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'no');
		$this->appConfig->setValue('test3', 'enabled', '["foo"]');
		$this->appConfig->setValue('test4', 'enabled', '["asd"]');
		$this->assertEquals(['test1', 'test3'], $this->manager->getEnabledAppsForUser($user));
	}
}
