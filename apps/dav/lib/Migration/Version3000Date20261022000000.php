<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\CardDAV\SyncService;
use OCP\IAppConfig;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Rewrite the system address book cards as vCard 4.0, with LANG and TZ
 */
class Version3000Date20261022000000 extends SimpleMigrationStep {
	public function __construct(
		private SyncService $syncService,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private IAppConfig $appConfig,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if ($this->userManager->countSeenUsers() > 100 || $this->userManager->countUsersTotal(100) >= 100) {
			$this->appConfig->setValueString('dav', 'needs_system_address_book_sync', 'yes');
			$output->info('Could not update the system address book during update - too many user records have been found. Please call occ dav:sync-system-addressbook manually.');
			return;
		}

		try {
			$this->syncService->syncInstance();
			$this->appConfig->setValueString('dav', 'needs_system_address_book_sync', 'no');
		} catch (Throwable $e) {
			$this->appConfig->setValueString('dav', 'needs_system_address_book_sync', 'yes');
			$this->logger->error('Could not update the system address book during update', [
				'exception' => $e,
			]);
			$output->warning('System address book update failed. See logs for details');
		}
	}
}
