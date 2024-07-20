<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests;

use OCA\Encryption\HookManager;
use OCA\Encryption\Hooks\Contracts\IHook;
use OCP\IConfig;
use Test\TestCase;

class HookManagerTest extends TestCase {

	/**
	 * @var HookManager
	 */
	private static $instance;

	
	public function testRegisterHookWithArray() {
		self::$instance->registerHook([
			$this->getMockBuilder(IHook::class)->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder(IHook::class)->disableOriginalConstructor()->getMock(),
			$this->createMock(IConfig::class)
		]);

		$hookInstances = self::invokePrivate(self::$instance, 'hookInstances');
		// Make sure our type checking works
		$this->assertCount(2, $hookInstances);
	}


	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		// have to make instance static to preserve data between tests
		self::$instance = new HookManager();
	}

	
	public function testRegisterHooksWithInstance() {
		$mock = $this->getMockBuilder(IHook::class)->disableOriginalConstructor()->getMock();
		/** @var \OCA\Encryption\Hooks\Contracts\IHook $mock */
		self::$instance->registerHook($mock);

		$hookInstances = self::invokePrivate(self::$instance, 'hookInstances');
		$this->assertCount(3, $hookInstances);
	}
}
