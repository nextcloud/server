<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Migration;

use OCA\DAV\BackgroundJob\SyncSystemAddressBookJob;
use OCA\DAV\Migration\Version3000Date20261022000000;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use Test\TestCase;

class Version3000Date20261022000000Test extends TestCase {
	public function testLeavesTheRewriteToABackgroundJob(): void {
		$config = $this->createMock(IAppConfig::class);
		$jobList = $this->createMock(IJobList::class);

		$config->expects($this->once())->method('setValueString')->with('dav', 'needs_system_address_book_sync', 'yes');
		$config->expects($this->once())->method('deleteKey')->with('dav', SyncSystemAddressBookJob::CURSOR);
		$jobList->expects($this->once())->method('add')->with(SyncSystemAddressBookJob::class);

		(new Version3000Date20261022000000($config, $jobList))->postSchemaChange($this->createMock(IOutput::class), fn () => null, []);
	}
}
