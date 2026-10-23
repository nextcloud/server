<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\BackgroundJob\SyncSystemAddressBookJob;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Rewrite the system address book cards as vCard 4.0, with LANG and TZ. On a
 * large instance this takes hours, so a background job does it later.
 */
class Version3000Date20261022000000 extends SimpleMigrationStep {
	public function __construct(
		private IAppConfig $appConfig,
		private IJobList $jobList,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$this->appConfig->setValueString('dav', 'needs_system_address_book_sync', 'yes');
		// Start over, also when a previous rewrite was interrupted
		$this->appConfig->deleteKey('dav', SyncSystemAddressBookJob::CURSOR);
		$this->jobList->add(SyncSystemAddressBookJob::class);
	}
}
