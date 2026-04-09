<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Migration;

use OCA\TwoFactorBackupCodes\Migration\CheckBackupCodes;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CheckBackupCodeTest extends TestCase {
	private IJobList&MockObject $jobList;
	private CheckBackupCodes $checkBackupsCodes;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->checkBackupsCodes = new CheckBackupCodes($this->jobList);
	}

	public function testGetName(): void {
		$this->assertSame('Add background job to check for backup codes', $this->checkBackupsCodes->getName());
	}

	public function testRun(): void {
		$this->jobList->expects($this->once())
			->method('add')
			->with(
				$this->equalTo(\OCA\TwoFactorBackupCodes\BackgroundJob\CheckBackupCodes::class)
			);

		$this->checkBackupsCodes->run($this->createMock(IOutput::class));
	}
}
