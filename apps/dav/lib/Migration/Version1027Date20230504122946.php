<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	public function __construct(private SyncService $syncService,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private IConfig $config) {
	}
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if($this->userManager->countSeenUsers() > 100 || array_sum($this->userManager->countUsers()) > 100) {
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
