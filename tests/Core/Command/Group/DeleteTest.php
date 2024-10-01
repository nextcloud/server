<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\Group;

use OC\Core\Command\Group\Delete;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DeleteTest extends TestCase {
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var Delete */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->command = new Delete($this->groupManager);

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testDoesNotExists(): void {
		$gid = 'myGroup';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});
		$this->groupManager->method('groupExists')
			->with($gid)
			->willReturn(false);

		$this->groupManager->expects($this->never())
			->method('get');
		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('<error>Group "' . $gid . '" does not exist.</error>'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testDeleteAdmin(): void {
		$gid = 'admin';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});

		$this->groupManager->expects($this->never())
			->method($this->anything());
		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('<error>Group "' . $gid . '" could not be deleted.</error>'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testDeleteFailed(): void {
		$gid = 'myGroup';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});
		$group = $this->createMock(IGroup::class);
		$group->method('delete')
			->willReturn(false);
		$this->groupManager->method('groupExists')
			->with($gid)
			->willReturn(true);
		$this->groupManager->method('get')
			->with($gid)
			->willReturn($group);

		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('<error>Group "' . $gid . '" could not be deleted. Please check the logs.</error>'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testDelete(): void {
		$gid = 'myGroup';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});
		$group = $this->createMock(IGroup::class);
		$group->method('delete')
			->willReturn(true);
		$this->groupManager->method('groupExists')
			->with($gid)
			->willReturn(true);
		$this->groupManager->method('get')
			->with($gid)
			->willReturn($group);

		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('Group "' . $gid . '" was removed'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
