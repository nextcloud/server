<?php

declare(strict_types=1);

/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class PruneOutdatedSyncTokensJob extends TimedJob {

	private IConfig $config;
	private LoggerInterface $logger;
	private CardDavBackend $cardDavBackend;
	private CalDavBackend $calDavBackend;

	public function __construct(ITimeFactory $timeFactory, CalDavBackend $calDavBackend, CardDavBackend $cardDavBackend, IConfig $config, LoggerInterface $logger) {
		parent::__construct($timeFactory);
		$this->calDavBackend = $calDavBackend;
		$this->cardDavBackend = $cardDavBackend;
		$this->config = $config;
		$this->logger = $logger;
		$this->setInterval(60 * 60 * 24); // One day
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument) {
		$limit = max(1, (int) $this->config->getAppValue(Application::APP_ID, 'totalNumberOfSyncTokensToKeep', '10000'));
		$retention = max(7, (int) $this->config->getAppValue(Application::APP_ID, 'syncTokensRetentionDays', '60')) * 24 * 3600;

		$prunedCalendarSyncTokens = $this->calDavBackend->pruneOutdatedSyncTokens($limit, $retention);
		$prunedAddressBookSyncTokens = $this->cardDavBackend->pruneOutdatedSyncTokens($limit, $retention);

		$this->logger->info('Pruned {calendarSyncTokensNumber} calendar sync tokens and {addressBooksSyncTokensNumber} address book sync tokens', [
			'calendarSyncTokensNumber' => $prunedCalendarSyncTokens,
			'addressBooksSyncTokensNumber' => $prunedAddressBookSyncTokens
		]);
	}
}
