<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Core\Command\User;

use OC\Core\Command\User\Profile;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ProfileTest extends TestCase {

	protected IAccountManager&MockObject $accountManager;
	protected IUserManager&MockObject $userManager;
	protected IDBConnection&MockObject $connection;
	protected InputInterface&MockObject $consoleInput;
	protected OutputInterface&MockObject $consoleOutput;

	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);
	}

	public function getCommand(array $methods = []): Profile|MockObject {
		if (empty($methods)) {
			return new Profile($this->userManager, $this->accountManager);
		} else {
			return $this->getMockBuilder(Profile::class)
				->setConstructorArgs([
					$this->userManager,
					$this->accountManager,
				])
				->onlyMethods($methods)
				->getMock();
		}
	}

	public static function dataCheckInput(): array {
		return [
			'Call with existing user should pass check' => [
				[['uid', 'username']],
				[],
				[],
				true,
				null,
			],
			'Call with non-existing user should fail check' => [
				[['uid', 'username']],
				[],
				[],
				false,
				'The user "username" does not exist.',
			],

			'Call with uid, key and --default value should pass check' => [
				[['uid', 'username'], ['key', 'configkey']],
				[],
				[['--default-value', false, true]],
				true,
				null,
			],
			'Call with uid and empty key with default-value option should fail check' => [
				[['uid', 'username'], ['key', '']],
				[],
				[['--default-value', false, true]],
				true,
				'The "default-value" option can only be used when specifying a key.',
			],

			'Call with uid, key, value should pass check' => [
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[],
				[],
				true,
				null,
			],
			'Call with uid, empty key and empty value should fail check' => [
				[['uid', 'username'], ['key', ''], ['value', '']],
				[],
				[],
				true,
				'The value argument can only be used when specifying a key.',
			],
			'Call with uid, key, empty value and default-value option should fail check' => [
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[],
				[['--default-value', false, true]],
				true,
				'The value argument can not be used together with "default-value".',
			],
			'Call with uid, key, empty value and update-only option should pass check' => [
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[['update-only', true]],
				[],
				true,
				null,
			],
			'Call with uid, key, null value and update-only option should fail check' => [
				[['uid', 'username'], ['key', 'configkey'], ['value', null]],
				[['update-only', true]],
				[],
				true,
				'The "update-only" option can only be used together with "value".',
			],

			'Call with uid, key and delete option should pass check' => [
				[['uid', 'username'], ['key', 'configkey']],
				[['delete', true]],
				[],
				true,
				null,
			],
			'Call with uid, empty key and delete option should fail check' => [
				[['uid', 'username'], ['key', '']],
				[['delete', true]],
				[],
				true,
				'The "delete" option can only be used when specifying a key.',
			],
			'Call with uid, key, delete option and default-value should fail check' => [
				[['uid', 'username'], ['key', 'configkey']],
				[['delete', true]],
				[['--default-value', false, true]],
				true,
				'The "delete" option can not be used together with "default-value".',
			],
			'Call with uid, key, empty value and delete option should fail check' => [
				[['uid', 'username'], ['key', 'configkey'], ['value', '']],
				[['delete', true]],
				[],
				true,
				'The "delete" option can not be used together with "value".',
			],
			'Call with uid, key, delete and error-if-not-exists should pass check' => [
				[['uid', 'username'], ['key', 'configkey']],
				[['delete', true], ['error-if-not-exists', true]],
				[],
				true,
				null,
			],
			'Call with uid, key and error-if-not-exists should fail check' => [
				[['uid', 'username'], ['key', 'configkey']],
				[['delete', false], ['error-if-not-exists', true]],
				[],
				true,
				'The "error-if-not-exists" option can only be used together with "delete".',
			],
		];
	}

	/**
	 * @dataProvider dataCheckInput
	 */
	public function testCheckInput(array $arguments, array $options, array $parameterOptions, bool $existingUser, ?string $expectedException): void {
		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap($arguments);
		$this->consoleInput->expects($this->any())
			->method('getOption')
			->willReturnMap($options);
		$this->consoleInput->expects($this->any())
			->method('hasParameterOption')
			->willReturnCallback(function (string|array $values, bool $onlyParams = false) use ($parameterOptions): bool {
				$arguments = func_get_args();
				foreach ($parameterOptions as $parameterOption) {
					// check the arguments of the function, if they are the same, return the mocked value
					if (array_diff($arguments, $parameterOption) === []) {
						return end($parameterOption);
					}
				}

				return false;
			});

		$returnedUser = null;
		if ($existingUser) {
			$mockUser = $this->createMock(IUser::class);
			$mockUser->expects($this->once())->method('getUID')->willReturn('user');
			$returnedUser = $mockUser;
		}
		$this->userManager->expects($this->once())
			->method('get')
			->willReturn($returnedUser);

		$command = $this->getCommand();
		try {
			$this->invokePrivate($command, 'checkInput', [$this->consoleInput]);
			$this->assertNull($expectedException);
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
			'Deleting existing property should succeed' => ['address', 'Berlin', false, null, Command::SUCCESS],
			'Deleting existing property with error-if-not-exists should succeed' => ['address', 'Berlin', true, null, Command::SUCCESS],
			'Deleting non-existing property should succeed' => ['address', '', false, null, Command::SUCCESS],
			'Deleting non-existing property with error-if-not-exists should fail' => ['address', '', true, '<error>The property does not exist for user "username".</error>', Command::FAILURE],
		];
	}

	/**
	 * Tests the deletion mechanism on profile settings.
	 *
	 * @dataProvider dataExecuteDeleteProfileProperty
	 */
	public function testExecuteDeleteProfileProperty(string $configKey, string $value, bool $errorIfNotExists, ?string $expectedLine, int $expectedReturn): void {
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

		$mocks = $this->setupProfilePropertiesMock([$configKey => $value]);

		$command->expects($this->once())
			->method('checkInput')
			->willReturn($mocks['userMock']);

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

		$this->assertEquals($expectedReturn, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public function testExecuteSetProfileProperty(): void {
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$uid = 'username';
		$propertyKey = 'address';
		$propertyValue = 'Barcelona';

		$this->consoleInput->expects($this->atLeast(3))
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['key', $propertyKey],
				['value', $propertyValue],
			]);

		$mocks = $this->setupProfilePropertiesMock([$propertyKey => $propertyValue]);

		$command->expects($this->once())
			->method('checkInput')
			->willReturn($mocks['userMock']);

		$mocks['profilePropertiesMocks'][0]->expects($this->once())
			->method('setValue')
			->with($propertyValue);
		$this->accountManager->expects($this->once())
			->method('updateAccount')
			->with($mocks['accountMock']);

		$this->assertEquals(0, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	public static function dataExecuteGet(): array {
		return [
			'Get property with set value should pass' => ['configkey', 'value', null, 'value', Command::SUCCESS],
			'Get property with empty value and default-value option should pass' => ['configkey', '', 'default-value', 'default-value', Command::SUCCESS],
			'Get property with empty value should fail' => ['configkey', '', null, '<error>The property does not exist for user "username".</error>', Command::FAILURE],
		];
	}

	/**
	 * @dataProvider dataExecuteGet
	 */
	public function testExecuteGet(string $key, string $value, ?string $defaultValue, string $expectedLine, int $expectedReturn): void {
		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$uid = 'username';

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['key', $key],
			]);

		$mocks = $this->setupProfilePropertiesMock([$key => $value]);

		$command->expects($this->once())
			->method('checkInput')
			->willReturn($mocks['userMock']);

		if ($value === '') {
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
		$profileData = [
			'pronouns' => 'they/them',
			'address' => 'Berlin',
		];

		$command = $this->getCommand([
			'writeArrayInOutputFormat',
			'checkInput',
		]);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->willReturnMap([
				['uid', $uid],
				['key', ''],
			]);

		$mocks = $this->setupProfilePropertiesMock(['address' => $profileData['address'], 'pronouns' => $profileData['pronouns']]);

		$command->expects($this->once())
			->method('checkInput')
			->willReturn($mocks['userMock']);

		$command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with($this->consoleInput, $this->consoleOutput, $profileData);


		$this->assertEquals(0, $this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}

	/**
	 * Helper to avoid boilerplate in tests in this file when mocking objects
	 * of IAccountProperty type.
	 *
	 * @param array<string, string> $properties the properties to be set up as key => value
	 * @return array{
	 *     userMock: IUser&MockObject,
	 *     accountMock: IAccount&MockObject,
	 *     profilePropertiesMocks: IAccountProperty&MockObject[]
	 * }
	 */
	private function setupProfilePropertiesMock(array $properties): array {
		$userMock = $this->createMock(IUser::class);
		$accountMock = $this->createMock(IAccount::class);
		$this->accountManager->expects($this->atLeastOnce())
			->method('getAccount')
			->with($userMock)
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
