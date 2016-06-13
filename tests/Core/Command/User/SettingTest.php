<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace Tests\Core\Command\User;


use OC\Core\Command\User\Setting;
use Test\TestCase;

class SettingTest extends TestCase {
	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var \OCP\IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \OCP\IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	protected $connection;
	/** @var \Symfony\Component\Console\Input\InputInterface|\PHPUnit_Framework_MockObject_MockObject */
	protected $consoleInput;
	/** @var \Symfony\Component\Console\Output\OutputInterface|\PHPUnit_Framework_MockObject_MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->connection = $this->getMockBuilder('OCP\IDBConnection')
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
			->disableOriginalConstructor()
			->getMock();
		$this->consoleOutput = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->command = new Setting($this->userManager, $this->config, $this->connection);
	}

	public function dataCheckInput() {
		return [
			[
				[['uid', 'username']],
				[['ignore-missing-user', true]],
				[],
				false,
				false,
			],
			[
				[['uid', 'username']],
				[['ignore-missing-user', false]],
				[],
				null,
				'The user "username" does not exists.',
			],

			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true]],
				[['--default-value', true]],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', '']],
				[['ignore-missing-user', true]],
				[['--default-value', true]],
				false,
				'The "default-value" option can only be used when specifying a key.',
			],

			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true]],
				[['--value', true]],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', '']],
				[['ignore-missing-user', true]],
				[['--value', true]],
				false,
				'The "value" option can only be used when specifying a key.',
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true]],
				[['--value', true], ['--default-value', true]],
				false,
				'The "value" option can not be used together with "default-value".',
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['update-only', true]],
				[['--value', true]],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['update-only', true]],
				[['--value', false]],
				false,
				'The "update-only" option can only be used together with "value".',
			],

			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['delete', true]],
				[],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', '']],
				[['ignore-missing-user', true], ['delete', true]],
				[],
				false,
				'The "delete" option can only be used when specifying a key.',
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['delete', true]],
				[['--default-value', true]],
				false,
				'The "delete" option can not be used together with "default-value".',
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['delete', true]],
				[['--value', true]],
				false,
				'The "delete" option can not be used together with "value".',
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['delete', true], ['error-if-not-exists', true]],
				[],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true], ['delete', false], ['error-if-not-exists', true]],
				[],
				false,
				'The "error-if-not-exists" option can only be used together with "delete".',
			],
		];
	}

	/**
	 * @dataProvider dataCheckInput
	 *
	 * @param $arguments
	 * @param $options
	 * @param $parameterOptions
	 * @param $user
	 * @param $expectedException
	 */
	public function testCheckInput($arguments, $options, $parameterOptions, $user, $expectedException) {
		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap($arguments);
		$this->consoleInput->expects($this->any())
			->method('getOption')
			->willReturnMap($options);
		$this->consoleInput->expects($this->any())
			->method('hasParameterOption')
			->willReturnMap($parameterOptions);

		if ($user !== false) {
			$this->userManager->expects($this->once())
				->method('userExists')
				->willReturn($user);
		}

		try {
			$this->invokePrivate($this->command, 'checkInput', [$this->consoleInput]);
			$this->assertFalse($expectedException);
		} catch (\InvalidArgumentException $e) {
			$this->assertEquals($expectedException, $e->getMessage());
		}
	}
}
