<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Command;

use OCA\Files_External\Command\Applicable;
use OCP\IGroupManager;
use OCP\IUserManager;

class ApplicableTest extends CommandTest {
	private function getInstance($storageService) {
		/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject $userManager */
		$userManager = $this->createMock(IUserManager::class);
		/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject $groupManager */
		$groupManager = $this->createMock(IGroupManager::class);

		$userManager->expects($this->any())
			->method('userExists')
			->willReturn(true);

		$groupManager->expects($this->any())
			->method('groupExists')
			->willReturn(true);

		return new Applicable($storageService, $userManager, $groupManager);
	}

	public function testListEmpty(): void {
		$mount = $this->getMount(1, '', '');

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json'
		]);

		$result = json_decode($this->executeCommand($command, $input), true);

		$this->assertEquals(['users' => [], 'groups' => []], $result);
	}

	public function testList(): void {
		$mount = $this->getMount(1, '', '', '', [], [], ['test', 'asd']);

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json'
		]);

		$result = json_decode($this->executeCommand($command, $input), true);

		$this->assertEquals(['users' => ['test', 'asd'], 'groups' => []], $result);
	}

	public function testAddSingle(): void {
		$mount = $this->getMount(1, '', '', '', [], [], []);

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json',
			'add-user' => ['foo']
		]);

		$this->executeCommand($command, $input);

		$this->assertEquals(['foo'], $mount->getApplicableUsers());
	}

	public function testAddDuplicate(): void {
		$mount = $this->getMount(1, '', '', '', [], [], ['foo']);

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json',
			'add-user' => ['foo', 'bar']
		]);

		$this->executeCommand($command, $input);

		$this->assertEquals(['foo', 'bar'], $mount->getApplicableUsers());
	}

	public function testRemoveSingle(): void {
		$mount = $this->getMount(1, '', '', '', [], [], ['foo', 'bar']);

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json',
			'remove-user' => ['bar']
		]);

		$this->executeCommand($command, $input);

		$this->assertEquals(['foo'], $mount->getApplicableUsers());
	}

	public function testRemoveNonExisting(): void {
		$mount = $this->getMount(1, '', '', '', [], [], ['foo', 'bar']);

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json',
			'remove-user' => ['bar', 'asd']
		]);

		$this->executeCommand($command, $input);

		$this->assertEquals(['foo'], $mount->getApplicableUsers());
	}

	public function testRemoveAddRemove(): void {
		$mount = $this->getMount(1, '', '', '', [], [], ['foo', 'bar']);

		$storageService = $this->getGlobalStorageService([$mount]);
		$command = $this->getInstance($storageService);

		$input = $this->getInput($command, [
			'mount_id' => 1
		], [
			'output' => 'json',
			'remove-user' => ['bar', 'asd'],
			'add-user' => ['test']
		]);

		$this->executeCommand($command, $input);

		$this->assertEquals(['foo', 'test'], $mount->getApplicableUsers());
	}
}
