<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\Group;

use OC\Core\Command\Group\Add;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class AddTest extends TestCase {
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var Add */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->command = new Add($this->groupManager);

		$this->input = $this->createMock(InputInterface::class);
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) {
				if ($arg === 'groupid') {
					return 'myGroup';
				}
				throw new \Exception();
			});
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testGroupExists(): void {
		$gid = 'myGroup';
		$group = $this->createMock(IGroup::class);
		$this->groupManager->method('get')
			->with($gid)
			->willReturn($group);

		$this->groupManager->expects($this->never())
			->method('createGroup');
		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('<error>Group "' . $gid . '" already exists.</error>'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testAdd(): void {
		$gid = 'myGroup';
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')
			->willReturn($gid);
		$this->groupManager->method('createGroup')
			->willReturn($group);

		$this->groupManager->expects($this->once())
			->method('createGroup')
			->with($this->equalTo($gid));
		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('Created group "' . $group->getGID() . '"'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
