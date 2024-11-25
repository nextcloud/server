<?php

declare(strict_types=1);

/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

		$result = $qb->execute();
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
