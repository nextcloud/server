<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Command\AdminDelegation;

use OC\Settings\AuthorizedGroup;
use OCA\Settings\Command\AdminDelegation\Add;
use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Service\ConflictException;
use OCA\Settings\Settings\Admin\Server;
use OCP\IGroupManager;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class AddTest extends TestCase {

	private IManager&MockObject $settingManager;
	private AuthorizedGroupService&MockObject $authorizedGroupService;
	private IGroupManager&MockObject $groupManager;
	private Add $command;
	private InputInterface&MockObject $input;
	private OutputInterface&MockObject $output;

	protected function setUp(): void {
		parent::setUp();

		$this->settingManager = $this->createMock(IManager::class);
		$this->authorizedGroupService = $this->createMock(AuthorizedGroupService::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->command = new Add(
			$this->settingManager,
			$this->authorizedGroupService,
			$this->groupManager
		);

		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testExecuteSuccessfulDelegation(): void {
		$settingClass = Server::class;
		$groupId = 'testgroup';

		// Mock valid delegated settings class
		$this->input->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['settingClass', $settingClass],
				['groupId', $groupId]
			]);

		// Mock group exists
		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(true);

		// Mock successful creation
		$authorizedGroup = new AuthorizedGroup();
		$authorizedGroup->setGroupId($groupId);
		$authorizedGroup->setClass($settingClass);

		$this->authorizedGroupService->expects($this->once())
			->method('create')
			->with($groupId, $settingClass)
			->willReturn($authorizedGroup);

		$result = $this->command->execute($this->input, $this->output);

		$this->assertEquals(0, $result);
	}

	public function testExecuteHandlesDuplicateAssignment(): void {
		$settingClass = 'OCA\\Settings\\Settings\\Admin\\Server';
		$groupId = 'testgroup';

		// Mock valid delegated settings class
		$this->input->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['settingClass', $settingClass],
				['groupId', $groupId]
			]);

		// Mock group exists
		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(true);

		// Mock ConflictException when trying to create duplicate
		$this->authorizedGroupService->expects($this->once())
			->method('create')
			->with($groupId, $settingClass)
			->willThrowException(new ConflictException('Group is already assigned to this class'));

		$result = $this->command->execute($this->input, $this->output);

		$this->assertEquals(4, $result, 'Duplicate assignment should return exit code 4');
	}

	public function testExecuteInvalidSettingClass(): void {
		// Use a real class that exists but doesn't implement IDelegatedSettings
		$settingClass = 'stdClass';

		$this->input->expects($this->once())
			->method('getArgument')
			->with('settingClass')
			->willReturn($settingClass);

		$result = $this->command->execute($this->input, $this->output);

		// Should return exit code 2 for invalid setting class
		$this->assertEquals(2, $result);
	}

	public function testExecuteNonExistentGroup(): void {
		$settingClass = Server::class;
		$groupId = 'nonexistentgroup';

		$this->input->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['settingClass', $settingClass],
				['groupId', $groupId]
			]);

		// Mock group does not exist
		$this->groupManager->expects($this->once())
			->method('groupExists')
			->with($groupId)
			->willReturn(false);

		$result = $this->command->execute($this->input, $this->output);

		// Should return exit code 3 for non-existent group
		$this->assertEquals(3, $result);
	}
}
