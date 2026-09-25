<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\User;

use OC\Core\Command\User\Disable;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DisableTest extends TestCase {
	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var Disable */
	protected $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = $this->createInstanceWithMocks(Disable::class);
	}

	public function testValidUser(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('setEnabled')
			->with(false);

		$this->mocks[IUserManager::class]
			->method('get')
			->with('user')
			->willReturn($user);

		$this->consoleInput
			->method('getArgument')
			->with('uid')
			->willReturn('user');

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains('The specified user is disabled'));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testInvalidUser(): void {
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('user')
			->willReturn(null);

		$this->consoleInput
			->method('getArgument')
			->with('uid')
			->willReturn('user');

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains('User does not exist'));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
