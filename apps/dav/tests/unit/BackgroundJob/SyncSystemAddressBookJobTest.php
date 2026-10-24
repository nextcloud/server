<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\SyncSystemAddressBookJob;
use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SyncSystemAddressBookJobTest extends TestCase {
	private SyncService&MockObject $syncService;
	private IAppConfig&MockObject $appConfig;
	private IJobList&MockObject $jobList;
	private SyncSystemAddressBookJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->syncService = $this->createMock(SyncService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->job = new SyncSystemAddressBookJob($this->createMock(ITimeFactory::class), $this->syncService, $this->appConfig, $this->jobList);
	}

	public function testABatchGoesOnWhereTheLastOneStopped(): void {
		$this->appConfig->method('getValueInt')->with('dav', SyncSystemAddressBookJob::CURSOR)->willReturn(1000);
		$this->syncService->expects($this->once())->method('updateSystemAddressBookBatch')->with(1000, SyncSystemAddressBookJob::BATCH_SIZE)->willReturn(2000);
		$this->appConfig->expects($this->once())->method('setValueInt')->with('dav', SyncSystemAddressBookJob::CURSOR, 2000);
		$this->appConfig->expects($this->never())->method('setValueString');
		$this->jobList->expects($this->never())->method('remove');

		self::invokePrivate($this->job, 'run', [null]);
	}

	public function testTheLastBatchClearsTheFlagAndRemovesTheJob(): void {
		$this->appConfig->method('getValueInt')->willReturn(2000);
		$this->syncService->method('updateSystemAddressBookBatch')->willReturn(null);
		$this->appConfig->expects($this->never())->method('setValueInt');
		$this->appConfig->expects($this->once())->method('deleteKey')->with('dav', SyncSystemAddressBookJob::CURSOR);
		$this->appConfig->expects($this->once())->method('setValueString')->with('dav', 'needs_system_address_book_sync', 'no');
		$this->jobList->expects($this->once())->method('remove')->with(SyncSystemAddressBookJob::class);

		self::invokePrivate($this->job, 'run', [null]);
	}
}
