<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use OCA\DAV\CardDAV\SyncService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CreateSystemAddressBookStep implements IRepairStep {

	public function __construct(
		private SyncService $syncService,
	) {
	}

	public function getName(): string {
		return 'Create system address book';
	}

	public function run(IOutput $output): void {
		$this->syncService->ensureLocalSystemAddressBookExists();
	}
}
