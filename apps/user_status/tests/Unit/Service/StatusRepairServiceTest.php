<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Tests\Service;

use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusRepairService;
use OCA\UserStatus\Service\StatusService;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StatusRepairServiceTest extends TestCase {
	private UserStatusMapper&MockObject $mapper;
	private StatusRepairService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(UserStatusMapper::class);
		$this->service = new StatusRepairService($this->mapper);
	}

	/** The reason this class exists. */
	public function testStrandedIsAlwaysDecidedAgainstTheAutomatedMessageIds(): void {
		$this->mapper->expects($this->once())
			->method('findStrandedBackupIds')
			->with(StatusService::AUTOMATED_MESSAGE_IDS)
			->willReturn([1, 2]);
		$this->mapper->expects($this->once())
			->method('deleteStrandedBackups')
			->with(StatusService::AUTOMATED_MESSAGE_IDS)
			->willReturn(2);
		$this->mapper->expects($this->once())
			->method('findOrphanedAutomatedStatusIds')
			->with(StatusService::AUTOMATED_MESSAGE_IDS)
			->willReturn([3]);

		self::assertSame([1, 2], $this->service->findStrandedBackupIds());
		self::assertSame(2, $this->service->deleteStrandedBackups());
		self::assertSame([3], $this->service->findOrphanedAutomatedStatusIds());
	}

	public function testAutomatedMessageIdsAreNeverEmpty(): void {
		self::assertNotEmpty(StatusService::AUTOMATED_MESSAGE_IDS);
	}
}
