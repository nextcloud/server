<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\User;

use OC\Core\Command\User\LastSeen;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class LastSeenTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$userManager = $this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var IUserManager $userManager */
		$this->command = new LastSeen($userManager);
	}

	public static function validUserLastSeen(): array {
		return [
			[0, 'never logged in'],
			[time(), 'last login'],
		];
	}

	/**
	 *
	 * @param int $lastSeen
	 * @param string $expectedString
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('validUserLastSeen')]
	public function testValidUser($lastSeen, $expectedString): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn($lastSeen);

		$this->userManager->expects($this->once())
			->method('get')
			->with('user')
			->willReturn($user);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('uid')
			->willReturn('user');

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains($expectedString));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testInvalidUser(): void {
		$this->userManager->expects($this->once())
			->method('get')
			->with('user')
			->willReturn(null);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('uid')
			->willReturn('user');

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains('User does not exist'));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testAllUsersWithoutExcludeDisabled(): void {
		$enabledUser = $this->getMockBuilder(IUser::class)->getMock();
		$enabledUser->expects($this->once())
			->method('getLastLogin')
			->willReturn(time());
		$enabledUser->expects($this->once())
			->method('getUID')
			->willReturn('enabled_user');
		$enabledUser->expects($this->never())
			->method('isEnabled');

		$disabledUser = $this->getMockBuilder(IUser::class)->getMock();
		$disabledUser->expects($this->once())
			->method('getLastLogin')
			->willReturn(time());
		$disabledUser->expects($this->once())
			->method('getUID')
			->willReturn('disabled_user');
		$disabledUser->expects($this->never())
			->method('isEnabled');

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('uid')
			->willReturn(null);

		$this->consoleInput->expects($this->exactly(2))
			->method('getOption')
			->willReturnMap([
				['all', true],
				['exclude-disabled', false],
			]);

		$this->userManager->expects($this->once())
			->method('callForAllUsers')
			->willReturnCallback(function ($callback) use ($enabledUser, $disabledUser) {
				$callback($enabledUser);
				$callback($disabledUser);
			});

		$this->consoleOutput->expects($this->exactly(2))
			->method('writeln')
			->with($this->stringContains("'s last login:"));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testAllUsersWithExcludeDisabled(): void {
		$enabledUser = $this->getMockBuilder(IUser::class)->getMock();
		$enabledUser->expects($this->once())
			->method('getLastLogin')
			->willReturn(time());
		$enabledUser->expects($this->once())
			->method('getUID')
			->willReturn('enabled_user');
		$enabledUser->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$disabledUser = $this->getMockBuilder(IUser::class)->getMock();
		$disabledUser->expects($this->never())
			->method('getLastLogin');
		$disabledUser->expects($this->never())
			->method('getUID');
		$disabledUser->expects($this->once())
			->method('isEnabled')
			->willReturn(false);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('uid')
			->willReturn(null);

		$this->consoleInput->expects($this->exactly(2))
			->method('getOption')
			->willReturnMap([
				['all', true],
				['exclude-disabled', true],
			]);

		$this->userManager->expects($this->once())
			->method('callForAllUsers')
			->willReturnCallback(function ($callback) use ($enabledUser, $disabledUser) {
				$callback($enabledUser);
				$callback($disabledUser);
			});

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains("enabled_user's last login:"));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
