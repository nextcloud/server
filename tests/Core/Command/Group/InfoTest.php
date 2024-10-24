<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\Group;

use OC\Core\Command\Group\Info;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class InfoTest extends TestCase {
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var Info|\PHPUnit\Framework\MockObject\MockObject */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->command = $this->getMockBuilder(Info::class)
			->setConstructorArgs([$this->groupManager])
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

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
		$this->groupManager->method('get')
			->with($gid)
			->willReturn(null);

		$this->output->expects($this->once())
			->method('writeln')
			->with($this->equalTo('<error>Group "' . $gid . '" does not exist.</error>'));

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testInfo(): void {
		$gid = 'myGroup';
		$this->input->method('getArgument')
			->willReturnCallback(function ($arg) use ($gid) {
				if ($arg === 'groupid') {
					return $gid;
				}
				throw new \Exception();
			});

		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($gid);
		$group->method('getDisplayName')
			->willReturn('My Group');
		$group->method('getBackendNames')
			->willReturn(['Database']);

		$this->groupManager->method('get')
			->with($gid)
			->willReturn($group);

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with(
				$this->equalTo($this->input),
				$this->equalTo($this->output),
				[
					'groupID' => 'myGroup',
					'displayName' => 'My Group',
					'backends' => ['Database'],
				]
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
