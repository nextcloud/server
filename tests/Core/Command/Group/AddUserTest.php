<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\Group;

use OC\Core\Command\Group\AddUser;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class AddUserTest extends TestCase {
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var AddUser */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->command = new AddUser($this->userManager, $this->groupManager);

		$this->input = $this->createMock(InputInterface::class);
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) {
				if ($arg === 'group') {
					return 'myGroup';
				} elseif ($arg === 'user') {
					return 'myUser';
				}
				throw new \Exception();
			});
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testNoGroup(): void {
		$this->groupManager->method('get')
			->with('myGroup')
			->willReturn(null);

		$this->output->expects($this->once())
			->method('writeln')
			->with('<error>group not found</error>');

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testNoUser(): void {
		$group = $this->createMock(IGroup::class);
		$this->groupManager->method('get')
			->with('myGroup')
			->willReturn($group);

		$this->userManager->method('get')
			->with('myUser')
			->willReturn(null);

		$this->output->expects($this->once())
			->method('writeln')
			->with('<error>user not found</error>');

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testAdd(): void {
		$group = $this->createMock(IGroup::class);
		$this->groupManager->method('get')
			->with('myGroup')
			->willReturn($group);

		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')
			->with('myUser')
			->willReturn($user);

		$group->expects($this->once())
			->method('addUser')
			->with($user);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
