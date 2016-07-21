<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
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


namespace OCA\Encryption\Tests;


use OCA\Encryption\HookManager;
use Test\TestCase;

class HookManagerTest extends TestCase {

	/**
	 * @var HookManager
	 */
	private static $instance;

	/**
	 *
	 */
	public function testRegisterHookWithArray() {
		self::$instance->registerHook([
			$this->getMockBuilder('OCA\Encryption\Hooks\Contracts\IHook')->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder('OCA\Encryption\Hooks\Contracts\IHook')->disableOriginalConstructor()->getMock(),
			$this->getMock('NotIHook')
		]);

		$hookInstances = self::invokePrivate(self::$instance, 'hookInstances');
		// Make sure our type checking works
		$this->assertCount(2, $hookInstances);
	}


	/**
	 *
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		// have to make instance static to preserve data between tests
		self::$instance = new HookManager();

	}

	/**
	 *
	 */
	public function testRegisterHooksWithInstance() {
		$mock = $this->getMockBuilder('OCA\Encryption\Hooks\Contracts\IHook')->disableOriginalConstructor()->getMock();
		/** @var \OCA\Encryption\Hooks\Contracts\IHook $mock */
		self::$instance->registerHook($mock);

		$hookInstances = self::invokePrivate(self::$instance, 'hookInstances');
		$this->assertCount(3, $hookInstances);

	}

}
