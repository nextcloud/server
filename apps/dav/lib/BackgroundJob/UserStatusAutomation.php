<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\Schedule\Plugin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;

class UserStatusAutomation extends TimedJob {
	protected IDBConnection $connection;
	protected IJobList $jobList;
	protected LoggerInterface $logger;
	protected IConfig $config;

	public function __construct(ITimeFactory $timeFactory,
								IDBConnection $connection,
								IJobList $jobList,
								LoggerInterface $logger,
								IConfig $config) {
		parent::__construct($timeFactory);
		$this->connection = $connection;
		$this->jobList = $jobList;
		$this->logger = $logger;
		$this->config = $config;

		$this->setInterval(1); // FIXME $this->setInterval(240);
	}

	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		if (!isset($argument['userId'])) {
			$this->jobList->remove(self::class, $argument);
			$this->logger->info('Removing invalid ' . self::class . ' background job');
			return;
		}

		$userId = $argument['userId'];
		$automationEnabled = $this->config->getUserValue($userId, 'dav', 'user_status_automation', 'no') === 'yes';
		if (!$automationEnabled) {
			$this->logger->info('Removing ' . self::class . ' background job for user "' . $userId . '" because the setting is disabled');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$propertyPath = 'calendars/' . $userId . '/inbox';
		$propertyName = '{' . Plugin::NS_CALDAV . '}calendar-availability';

		$query = $this->connection->getQueryBuilder();
		$query->select('propertyvalue')
			->from('properties')
			->where($query->expr()->eq('userid', $query->createNamedParameter($userId)))
			->andWhere($query->expr()->eq('propertypath', $query->createNamedParameter($propertyPath)))
			->where($query->expr()->eq('propertyname', $query->createNamedParameter($propertyName)))
			->setMaxResults(1);

		$result = $query->executeQuery();
		$property = $result->fetchOne();
		$result->closeCursor();

		if (!$property) {
			$this->logger->info('Removing ' . self::class . ' background job for user "' . $userId . '" because the user has no availability settings');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$this->logger->debug('User status automation ran');
	}
}
