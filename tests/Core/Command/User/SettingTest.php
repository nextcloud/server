<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\User;

use OC\Core\Command\User\Setting;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SettingTest extends TestCase {

	protected IAccountManager&MockObject $accountManager;
	protected IUserManager&MockObject $userManager;
	protected IConfig&MockObject $config;
	protected IDBConnection&MockObject $connection;
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;

	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->getMockBuilder(IAccountManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->connection = $this->getMockBuilder(IDBConnection::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function getCommand(array $methods = []) {
		if (empty($methods)) {
			return new Setting($this->userManager, $this->accountManager, $this->config);
		} else {
			$mock = $this->getMockBuilder(Setting::class)
				->setConstructorArgs([
					$this->userManager,
					$this->accountManager,
					$this->config,
				])
				->onlyMethods($methods)
				->getMock();
			return $mock;
		}
	}

	public static function dataCheckInput(): array {
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
				'The user "username" does not exist.',
			],

			[
				[['uid', 'username'], ['key', 'configkey']],
				[['ignore-missing-user', true]],
				[['--default-value', false, true]],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', '']],
				[['ignore-missing-user', true]],
				[['--default-value', false, true]],
				false,
				'The "default-value" option can only be used when specifying a key.',
			],

			[
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[['ignore-missing-user', true]],
				[],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', ''], ['value', '']],
				[['ignore-missing-user', true]],
				[],
				false,
				'The value argument can only be used when specifying a key.',
			],
			[
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[['ignore-missing-user', true]],
				[['--default-value', false, true]],
				false,
				'The value argument can not be used together with "default-value".',
			],
			[
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[['ignore-missing-user', true], ['update-only', true]],
				[],
				false,
				false,
			],
			[
				[['uid', 'username'], ['key', 'configkey'], ['value', null]],
				[['ignore-missing-user', true], ['update-only', true]],
				[],
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
				[['--default-value', false, true]],
				false,
				'The "delete" option can not be used together with "default-value".',
			],
			[
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[['ignore-missing-user', true], ['delete', true]],
				[],
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
	 * @param array $arguments
	 * @param array $options
	 * @param array $parameterOptions
	 * @param mixed $user
	 * @param string $expectedException
	 */
	public function testCheckInput($arguments, $options, $parameterOptions, $user, $expectedException): void {
		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap($arguments);
		$this->consoleInput->expects($this->any())
			->method('getOption')
			->willReturnMap($options);
		$this->consoleInput->expects($this->any())
			->method('hasParameterOption')
			->willReturnCallback(function (string|array $config, bool $default = false) use ($parameterOptions): bool {
				foreach ($parameterOptions as $parameterOption) {
					if ($config === $parameterOption[0]
						// Check the default value if the maps has 3 entries
						&& (!isset($parameterOption[2]) || $default === $parameterOption[1])) {
						return end($parameterOption);
					}
				}
				return false;
			});

		if ($user !== false) {
			$this->userManager->expects($this->once())
				->method('get')
				->willReturn($user);
		} else {
			$this->userManager->expects($this->never())
				->method('get');
		}

		$command = $this->getCommand();
		try {
			$this->invokePrivate($command, 'checkInput', [$this->consoleInput]);
			$this->assertFalse($expectedException);
		} catch (\InvalidArgumentException $e) {
			$this->assertEquals($expectedException, $e->getMessage());
		}
	}

	public function testCheckInputExceptionCatch(): void {
		$command = $this->getCommand(['checkInput']);
		$command->expects($this->once())
			->method('checkInput')
			->willThrowException(new \InvalidArgumentException('test'));

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with('<error>test</error>');

		$this->assertEquals(1, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public static function dataExecuteDeleteProfileProperty(): array {
		return [
			['address', 'Berlin', false, null, 0],
			['address', 'Berlin', true, null, 0],
			['address', '', false, null, 0],
			['address', '', true, '<error>The setting does not exist for user "username".</error>', 1],
		];
	}

	/**
	 * Tests the deletion mechanism on profile settings.
	 *
	 * @dataProvider dataExecuteDeleteProfileProperty
	 *
	 * @param string $configKey
	 * @param string|null $value
	 * @param bool $errorIfNotExists
	 * @param string $expectedLine
	 * @param int $expectedReturn
	 */
	public function testExecuteDeleteProfileProperty($configKey, $value, $errorIfNotExists, $expectedLine, $expectedReturn): void {
		$uid = 'username';
		$appName = 'profile';
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['app', $appName],
				['key', $configKey],
			]);

		$command->expects($this->once())
			->method('checkInput');

		$mocks = $this->setupProfilePropertiesMock($uid, [$configKey => $value]);

		$this->consoleInput->expects($this->atLeastOnce())
			->method('hasParameterOption')
			->willReturnMap([
				['--delete', false, true],
				['--error-if-not-exists', false, $errorIfNotExists],
			]);

		if ($expectedLine === null) {
			$this->consoleOutput->expects($this->never())
				->method('writeln');
			$mocks['profilePropertiesMocks'][0]->expects($this->once())
				->method('setValue')
				->with('');
			$this->accountManager->expects($this->once())
				->method('updateAccount')
				->with($mocks['accountMock']);
		} else {
			$this->consoleOutput->expects($this->once())
				->method('writeln')
				->with($expectedLine);
			$this->accountManager->expects($this->never())
				->method('updateAccount');
		}

		$this->config->expects($this->never())
			->method('deleteUserValue');

		$this->assertEquals($expectedReturn, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public static function dataExecuteDelete(): array {
		return [
			['config', false, null, 0],
			['config', true, null, 0],
			[null, false, null, 0],
			[null, true, '<error>The setting does not exist for user "username".</error>', 1],
		];
	}

	/**
	 * @dataProvider dataExecuteDelete
	 *
	 * @param string|null $value
	 * @param bool $errorIfNotExists
	 * @param string $expectedLine
	 * @param int $expectedReturn
	 */
	public function testExecuteDelete($value, $errorIfNotExists, $expectedLine, $expectedReturn): void {
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
			'getUserSettings',
		]);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap([
				['uid', 'username'],
				['app', 'appname'],
				['key', 'configkey'],
			]);

		$command->expects($this->once())
			->method('checkInput');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('username', 'appname', 'configkey', null)
			->willReturn($value);

		$this->consoleInput->expects($this->atLeastOnce())
			->method('hasParameterOption')
			->willReturnMap([
				['--delete', false, true],
				['--error-if-not-exists', false, $errorIfNotExists],
			]);

		if ($expectedLine === null) {
			$this->consoleOutput->expects($this->never())
				->method('writeln');
			$this->config->expects($this->once())
				->method('deleteUserValue')
				->with('username', 'appname', 'configkey');
		} else {
			$this->consoleOutput->expects($this->once())
				->method('writeln')
				->with($expectedLine);
			$this->config->expects($this->never())
				->method('deleteUserValue');
		}

		$this->assertEquals($expectedReturn, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public function testExecuteSetProfileProperty() {
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$uid = 'username';
		$appName = 'profile';
		$propertyKey = 'address';
		$propertyValue = 'Barcelona';

		$this->consoleInput->expects($this->atLeast(4))
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['app', $appName],
				['key', $propertyKey],
				['value', $propertyValue],
			]);

		$command->expects($this->once())
			->method('checkInput');

		// profile properties are not stored in user settings
		$this->config->expects($this->never())
			->method('getUserValue');

		$mocks = $this->setupProfilePropertiesMock($uid, [$propertyKey => $propertyValue]);

		$mocks['profilePropertiesMocks'][0]->expects($this->once())
			->method('setValue')
			->with($propertyValue);
		$this->accountManager->expects($this->once())
			->method('updateAccount')
			->with($mocks['accountMock']);

		$this->config->expects($this->never())
			->method('setUserValue');

		$this->assertEquals(0, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public static function dataExecuteSet(): array {
		return [
			['config', false, null, 0],
			['config', true, null, 0],
			[null, false, null, 0],
			[null, true, '<error>The setting does not exist for user "username".</error>', 1],
		];
	}

	/**
	 * @dataProvider dataExecuteSet
	 *
	 * @param string|null $value
	 * @param bool $updateOnly
	 * @param string $expectedLine
	 * @param int $expectedReturn
	 */
	public function testExecuteSet($value, $updateOnly, $expectedLine, $expectedReturn): void {
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
			'getUserSettings',
		]);

		$this->consoleInput->expects($this->atLeast(4))
			->method('getArgument')
			->willReturnMap([
				['uid', 'username'],
				['app', 'appname'],
				['key', 'configkey'],
				['value', 'setValue'],
			]);

		$command->expects($this->once())
			->method('checkInput');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('username', 'appname', 'configkey', null)
			->willReturn($value);

		$this->consoleInput->expects($this->atLeastOnce())
			->method('hasParameterOption')
			->willReturnMap([
				['--update-only', false, $updateOnly],
			]);

		if ($expectedLine === null) {
			$this->consoleOutput->expects($this->never())
				->method('writeln');

			$this->consoleInput->expects($this->never())
				->method('getOption');

			$this->config->expects($this->once())
				->method('setUserValue')
				->with('username', 'appname', 'configkey', 'setValue');
		} else {
			$this->consoleOutput->expects($this->once())
				->method('writeln')
				->with($expectedLine);
			$this->config->expects($this->never())
				->method('setUserValue');
		}

		$this->assertEquals($expectedReturn, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public static function dataExecuteGet(): array {
		return [
			['appname', 'configkey', 'config', null, 'config', 0],
			['appname', 'configkey', null, 'config', 'config', 0],
			['appname', 'configkey', null, null, '<error>The setting does not exist for user "username".</error>', 1],
			['profile', 'configkey', 'config', null, 'config', 0],
			['profile', 'configkey', '', 'config', 'config', 0],
			['profile', 'configkey', '', null, '<error>The setting does not exist for user "username".</error>', 1],
		];
	}

	/**
	 * @dataProvider dataExecuteGet
	 *
	 * @param string|null $value
	 * @param string|null $defaultValue
	 * @param string $expectedLine
	 * @param int $expectedReturn
	 */
	public function testExecuteGet($app, $key, $value, $defaultValue, $expectedLine, $expectedReturn): void {
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$uid = 'username';

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['app', $app],
				['key', $key],
			]);

		$command->expects($this->once())
			->method('checkInput');

		if ($app === 'profile') {
			$this->setupProfilePropertiesMock($uid, [$key => $value]);
		} else {
			$this->config->expects($this->once())
				->method('getUserValue')
				->with('username', $app, $key, null)
				->willReturn($value);
		}

		if ($value === null || $value === '') {
			if ($defaultValue === null) {
				$this->consoleInput->expects($this->atLeastOnce())
					->method('hasParameterOption')
					->willReturn(false);
			} else {
				$this->consoleInput->expects($this->atLeastOnce())
					->method('hasParameterOption')
					->willReturnCallback(fn (string|array $values): bool => $values === '--default-value');
				$this->consoleInput->expects($this->once())
					->method('getOption')
					->with('default-value')
					->willReturn($defaultValue);
			}
		}

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($expectedLine);

		$this->assertEquals($expectedReturn, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public function testExecuteList(): void {
		$uid = 'username';
		$userDisplayName = 'display name';
		$profileData = [
			'pronouns' => 'they/them',
			'address' => 'Berlin',
		];
		$settingsData = ['appname' => [
			'settings1' => 'value1',
			'settings2' => 'value2',
		]];

		$expectedOutputSettings = [
			'appname' => $settingsData['appname'],
			'settings' => [
				'display_name' => $userDisplayName,
			],
			'profile' => $profileData
		];

		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['app', ''],
				['key', ''],
			]);

		$command->expects($this->once())
			->method('checkInput');

		$this->config->expects($this->once())
			->method('getAllUserValues')
			->with('username')
			->willReturn($settingsData);

		$mocks = $this->setupProfilePropertiesMock($uid, ['address' => $profileData['address'], 'pronouns' => $profileData['pronouns']]);

		$mocks['userMock']->expects($this->once())
			->method('getDisplayName')
			->willReturn($userDisplayName);


		$command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with($this->consoleInput, $this->consoleOutput, $expectedOutputSettings);


		$this->assertEquals(0, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	/**
	 * Helper to avoid boilerplate in tests in this file when mocking objects
	 * of IAccountProperty type.
	 *
	 * @param string $uid
	 * @param array<string, string> $properties the properties to be set up as key => value
	 * @return array{
	 *     userMock: IUser&MockObject,
	 *     accountMock: IAccount&MockObject,
	 *     profilePropertiesMocks: IAccountProperty&MockObject[]
	 * }
	 */
	private function setupProfilePropertiesMock(string $uid, array $properties): array {
		$userMock = $this->getMockForClass(IUser::class);
		$accountMock = $this->getMockForClass(IAccount::class);
		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with($uid)
			->willReturn($userMock);
		$this->accountManager->expects($this->atLeastOnce())
			->method('getAccount')
			->willReturn($accountMock);

		/** @var IAccountProperty&MockObject[] $propertiesMocks */
		$propertiesMocks = [];
		foreach ($properties as $key => $value) {
			$propertiesMocks[] = $this->getAccountPropertyMock($key, $value);
		}

		if (count($properties) === 1) {
			$accountMock->expects($this->atLeastOnce())
				->method('getProperty')
				->with(array_keys($properties)[0])
				->willReturn($propertiesMocks[array_key_first($propertiesMocks)]);
		} else {
			$accountMock->expects($this->atLeastOnce())
				->method('getAllProperties')
				->willReturnCallback(function () use ($propertiesMocks) {
					foreach ($propertiesMocks as $property) {
						yield $property;
					}
				});
		}

		return [
			'userMock' => $userMock,
			'accountMock' => $accountMock,
			'profilePropertiesMocks' => $propertiesMocks,
		];
	}

	private function getMockForClass(string $className): MockObject {
		return $this->getMockBuilder($className)
			->disableOriginalConstructor()
			->getMock();
	}

	private function getAccountPropertyMock(string $name, string $value): IAccountProperty&MockObject {
		$propertyMock = $this->getMockBuilder(IAccountProperty::class)
			->disableOriginalConstructor()
			->getMock();
		$propertyMock->expects($this->any())
			->method('getName')
			->willReturn($name);
		$propertyMock->expects($this->any())
			->method('getValue')
			->willReturn($value);

		return $propertyMock;
	}
}
