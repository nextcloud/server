<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Tests\Command;

use OCA\UserStatus\Command\Repair;
use OCA\UserStatus\Service\StatusRepairService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class RepairTest extends TestCase {
	private StatusRepairService&MockObject $repairService;
	private CommandTester $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->repairService = $this->createMock(StatusRepairService::class);
		$this->tester = new CommandTester(new Repair($this->repairService));
	}

	public function testRepairsEverything(): void {
		$this->repairService->expects($this->once())
			->method('findStatusesWithoutBackupFlagIds')
			->willReturn([1, 2]);
		$this->repairService->expects($this->once())
			->method('normalizeBackupFlagByIds')
			->with([1, 2])
			->willReturn(2);

		$this->repairService->expects($this->once())
			->method('findOrphanedAutomatedStatusIds')
			->willReturn([7, 8, 9]);
		$this->repairService->expects($this->once())
			->method('findStrandedBackupIds')
			->willReturn([11, 12, 13, 14]);
		$this->repairService->expects($this->exactly(2))
			->method('deleteByIds')
			->willReturnCallback(static fn (array $ids): int => count($ids));

		self::assertSame(Command::SUCCESS, $this->tester->execute([]));

		$display = $this->tester->getDisplay();
		self::assertStringContainsString('2', $display);
		self::assertStringContainsString('3', $display);
		self::assertStringContainsString('4', $display);
	}

	public function testDryRunChangesNothing(): void {
		$this->repairService->method('findStatusesWithoutBackupFlagIds')->willReturn([1, 2]);
		$this->repairService->method('findOrphanedAutomatedStatusIds')->willReturn([7, 8, 9]);
		$this->repairService->method('findStrandedBackupIds')->willReturn([11, 12, 13, 14]);

		$this->repairService->expects($this->never())->method('normalizeBackupFlagByIds');
		$this->repairService->expects($this->never())->method('deleteByIds');
		$this->repairService->expects($this->never())->method('deleteStrandedBackups');

		self::assertSame(Command::SUCCESS, $this->tester->execute(['--dry-run' => true]));

		self::assertStringContainsString('dry run', strtolower($this->tester->getDisplay()));
	}

	public function testNothingToRepair(): void {
		$this->repairService->method('findStatusesWithoutBackupFlagIds')->willReturn([]);
		$this->repairService->method('findOrphanedAutomatedStatusIds')->willReturn([]);
		$this->repairService->method('findStrandedBackupIds')->willReturn([]);

		// Nothing to normalise and nothing to delete.
		$this->repairService->expects($this->never())->method('normalizeBackupFlagByIds');
		$this->repairService->expects($this->never())->method('deleteByIds');

		self::assertSame(Command::SUCCESS, $this->tester->execute([]));
	}
}
