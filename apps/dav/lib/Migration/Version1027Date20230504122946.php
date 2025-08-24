<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\CardDAV\SyncService;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;
use Throwable;

class Version1027Date20230504122946 extends SimpleMigrationStep {
	public function __construct(
		private SyncService $syncService,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private IConfig $config,
	) {
	}
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if ($this->userManager->countSeenUsers() > 100 || $this->userManager->countUsersTotal(100) >= 100) {
			$this->config->setAppValue('dav', 'needs_system_address_book_sync', 'yes');
			$output->info('Could not sync system address books during update - too many user records have been found. Please call occ dav:sync-system-addressbook manually.');
			return;
		}

		try {
			$this->syncService->syncInstance();
			$this->config->setAppValue('dav', 'needs_system_address_book_sync', 'no');
		} catch (Throwable $e) {
			$this->config->setAppValue('dav', 'needs_system_address_book_sync', 'yes');
			$this->logger->error('Could not sync system address books during update', [
				'exception' => $e,
			]);
			$output->warning('System address book sync failed. See logs for details');
		}
	}
}
