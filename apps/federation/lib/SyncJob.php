<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class SyncJob extends TimedJob {
	public function __construct(
		protected SyncFederationAddressBooks $syncService,
		protected LoggerInterface $logger,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		// Run once a day
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		$this->syncService->syncThemAll(function ($url, $ex): void {
			if ($ex instanceof \Exception) {
				$this->logger->error("Error while syncing $url.", [
					'exception' => $ex,
				]);
			}
		});
	}
}
