<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CardDAV\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;

/**
 * Rewrites the system address book cards in batches, outside of the busy hours,
 * then removes itself. Queued by migrations that change the card format.
 */
class SyncSystemAddressBookJob extends TimedJob {
	public const BATCH_SIZE = 1000;
	public const CURSOR = 'system_address_book_sync_cursor';

	public function __construct(
		ITimeFactory $time,
		private SyncService $syncService,
		private IAppConfig $appConfig,
		private IJobList $jobList,
	) {
		parent::__construct($time);
		$this->setInterval(5 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$next = $this->syncService->updateSystemAddressBookBatch($this->appConfig->getValueInt(Application::APP_ID, self::CURSOR), self::BATCH_SIZE);
		if ($next !== null) {
			$this->appConfig->setValueInt(Application::APP_ID, self::CURSOR, $next);
			return;
		}
		$this->appConfig->deleteKey(Application::APP_ID, self::CURSOR);
		$this->appConfig->setValueString(Application::APP_ID, 'needs_system_address_book_sync', 'no');
		$this->jobList->remove(self::class);
	}
}
