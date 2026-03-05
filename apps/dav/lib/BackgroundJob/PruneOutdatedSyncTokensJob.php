<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class PruneOutdatedSyncTokensJob extends TimedJob {

	public function __construct(
		ITimeFactory $timeFactory,
		private CalDavBackend $calDavBackend,
		private CardDavBackend $cardDavBackend,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(60 * 60 * 24); // One day
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument) {
		$limit = max(1, (int)$this->config->getAppValue(Application::APP_ID, 'totalNumberOfSyncTokensToKeep', '10000'));
		$retention = max(7, (int)$this->config->getAppValue(Application::APP_ID, 'syncTokensRetentionDays', '60')) * 24 * 3600;

		$prunedCalendarSyncTokens = $this->calDavBackend->pruneOutdatedSyncTokens($limit, $retention);
		$prunedAddressBookSyncTokens = $this->cardDavBackend->pruneOutdatedSyncTokens($limit, $retention);

		$this->logger->info('Pruned {calendarSyncTokensNumber} calendar sync tokens and {addressBooksSyncTokensNumber} address book sync tokens', [
			'calendarSyncTokensNumber' => $prunedCalendarSyncTokens,
			'addressBooksSyncTokensNumber' => $prunedAddressBookSyncTokens
		]);
	}
}
