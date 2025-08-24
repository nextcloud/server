<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IDBConnection;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\OCS\IDiscoveryService;
use Psr\Log\LoggerInterface;

class FederatedSharesDiscoverJob extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private IDBConnection $connection,
		private IDiscoveryService $discoveryService,
		private IOCMDiscoveryService $ocmDiscoveryService,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument) {
		$qb = $this->connection->getQueryBuilder();

		$qb->selectDistinct('remote')
			->from('share_external');

		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$this->discoveryService->discover($row['remote'], 'FEDERATED_SHARING', true);
			try {
				$this->ocmDiscoveryService->discover($row['remote'], true);
			} catch (OCMProviderException $e) {
				$this->logger->info('exception while running files_sharing/lib/BackgroundJob/FederatedSharesDiscoverJob', ['exception' => $e]);
			}
		}
		$result->closeCursor();
	}
}
