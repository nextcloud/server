<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Command\Group;

use OC\Core\Command\Group\ListCommand;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ListCommandTest extends TestCase {
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var ListCommand|\PHPUnit\Framework\MockObject\MockObject */
	private $command;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $input;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $output;

	protected function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->command = $this->getMockBuilder(ListCommand::class)
			->setConstructorArgs([$this->groupManager])
			->setMethods(['writeArrayInOutputFormat'])
			->getMock();

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecute(): void {
		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')->willReturn('group2');
		$group3 = $this->createMock(IGroup::class);
		$group3->method('getGID')->willReturn('group3');

		$user = $this->createMock(IUser::class);

		$this->groupManager->method('search')
			->with(
				'',
				100,
				42,
			)->willReturn([$group1, $group2, $group3]);

		$group1->method('getUsers')
			->willReturn([
				'user1' => $user,
				'user2' => $user,
			]);

		$group2->method('getUsers')
			->willReturn([
			]);

		$group3->method('getUsers')
			->willReturn([
				'user1' => $user,
				'user3' => $user,
			]);

		$this->input->method('getOption')
			->willReturnCallback(function ($arg) {
				if ($arg === 'limit') {
					return '100';
				} elseif ($arg === 'offset') {
					return '42';
				} elseif ($arg === 'info') {
					return null;
				}
				throw new \Exception();
			});

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with(
				$this->equalTo($this->input),
				$this->equalTo($this->output),
				$this->callback(
					fn ($iterator) => iterator_to_array($iterator) === [
						'group1' => [
							'user1',
							'user2',
						],
						'group2' => [
						],
						'group3' => [
							'user1',
							'user3',
						]
					]
				)
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testInfo(): void {
		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')->willReturn('group1');
		$group1->method('getDisplayName')->willReturn('Group 1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')->willReturn('group2');
		$group2->method('getDisplayName')->willReturn('Group 2');
		$group3 = $this->createMock(IGroup::class);
		$group3->method('getGID')->willReturn('group3');
		$group3->method('getDisplayName')->willReturn('Group 3');

		$user = $this->createMock(IUser::class);

		$this->groupManager->method('search')
			->with(
				'',
				100,
				42,
			)->willReturn([$group1, $group2, $group3]);

		$group1->method('getUsers')
			->willReturn([
				'user1' => $user,
				'user2' => $user,
			]);

		$group1->method('getBackendNames')
			->willReturn(['Database']);

		$group2->method('getUsers')
			->willReturn([
			]);

		$group2->method('getBackendNames')
			->willReturn(['Database']);

		$group3->method('getUsers')
			->willReturn([
				'user1' => $user,
				'user3' => $user,
			]);

		$group3->method('getBackendNames')
			->willReturn(['LDAP']);

		$this->input->method('getOption')
			->willReturnCallback(function ($arg) {
				if ($arg === 'limit') {
					return '100';
				} elseif ($arg === 'offset') {
					return '42';
				} elseif ($arg === 'info') {
					return true;
				}
				throw new \Exception();
			});

		$this->command->expects($this->once())
			->method('writeArrayInOutputFormat')
			->with(
				$this->equalTo($this->input),
				$this->equalTo($this->output),
				$this->callback(
					fn ($iterator) => iterator_to_array($iterator) === [
						'group1' => [
							'displayName' => 'Group 1',
							'backends' => ['Database'],
							'users' => [
								'user1',
								'user2',
							],
						],
						'group2' => [
							'displayName' => 'Group 2',
							'backends' => ['Database'],
							'users' => [],
						],
						'group3' => [
							'displayName' => 'Group 3',
							'backends' => ['LDAP'],
							'users' => [
								'user1',
								'user3',
							],
						]
					]
				)
			);

		$this->invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}
}
