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
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\UserStatus\IManager;
use OCP\UserStatus\IUserStatus;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\Available;
use Sabre\VObject\Component\VAvailability;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\RRuleIterator;

class UserStatusAutomation extends TimedJob {
	protected IDBConnection $connection;
	protected IJobList $jobList;
	protected LoggerInterface $logger;
	protected IManager $manager;
	protected IConfig $config;

	public function __construct(ITimeFactory $timeFactory,
								IDBConnection $connection,
								IJobList $jobList,
								LoggerInterface $logger,
								IManager $manager,
								IConfig $config) {
		parent::__construct($timeFactory);
		$this->connection = $connection;
		$this->jobList = $jobList;
		$this->logger = $logger;
		$this->manager = $manager;
		$this->config = $config;

		// Interval 0 might look weird, but the last_checked is always moved
		// to the next time we need this and then it's 0 seconds ago.
		$this->setInterval(0);
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

		$property = $this->getAvailabilityFromPropertiesTable($userId);

		if (!$property) {
			$this->logger->info('Removing ' . self::class . ' background job for user "' . $userId . '" because the user has no availability settings');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$isCurrentlyAvailable = false;
		$nextPotentialToggles = [];

		$now = $this->time->getDateTime();
		$lastMidnight = (clone $now)->setTime(0, 0);

		$vObject = Reader::read($property);
		foreach ($vObject->getComponents() as $component) {
			if ($component->name !== 'VAVAILABILITY') {
				continue;
			}
			/** @var VAvailability $component */
			$availables = $component->getComponents();
			foreach ($availables as $available) {
				/** @var Available $available */
				if ($available->name === 'AVAILABLE') {
					/** @var \DateTimeImmutable $originalStart */
					/** @var \DateTimeImmutable $originalEnd */
					[$originalStart, $originalEnd] = $available->getEffectiveStartEnd();

					// Little shenanigans to fix the automation on the day the rules were adjusted
					// Otherwise the $originalStart would match rules for Thursdays on a Friday, etc.
					// So we simply wind back a week and then fastForward to the next occurrence
					// since today's midnight, which then also accounts for the week days.
					$effectiveStart = \DateTime::createFromImmutable($originalStart)->sub(new \DateInterval('P7D'));
					$effectiveEnd = \DateTime::createFromImmutable($originalEnd)->sub(new \DateInterval('P7D'));

					try {
						$it = new RRuleIterator((string) $available->RRULE, $effectiveStart);
						$it->fastForward($lastMidnight);

						$startToday = $it->current();
						if ($startToday && $startToday <= $now) {
							$duration = $effectiveStart->diff($effectiveEnd);
							$endToday = $startToday->add($duration);
							if ($endToday > $now) {
								// User is currently available
								// Also queuing the end time as next status toggle
								$isCurrentlyAvailable = true;
								$nextPotentialToggles[] = $endToday->getTimestamp();
							}

							// Availability enabling already done for today,
							// so jump to the next recurrence to find the next status toggle
							$it->next();
						}

						if ($it->current()) {
							$nextPotentialToggles[] = $it->current()->getTimestamp();
						}
					} catch (\Exception $e) {
						$this->logger->error($e->getMessage(), ['exception' => $e]);
					}
				}
			}
		}

		if (empty($nextPotentialToggles)) {
			$this->logger->info('Removing ' . self::class . ' background job for user "' . $userId . '" because the user has no valid availability rules set');
			$this->jobList->remove(self::class, $argument);
			$this->manager->revertUserStatus($userId, IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
			return;
		}

		$nextAutomaticToggle = min($nextPotentialToggles);
		$this->setLastRunToNextToggleTime($userId, $nextAutomaticToggle - 1);

		if ($isCurrentlyAvailable) {
			$this->logger->debug('User is currently available, reverting DND status if applicable');
			$this->manager->revertUserStatus($userId, IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
		} else {
			$this->logger->debug('User is currently NOT available, reverting call status if applicable and then setting DND');
			// The DND status automation is more important than the "Away - In call" so we also restore that one if it exists.
			$this->manager->revertUserStatus($userId, IUserStatus::MESSAGE_CALL, IUserStatus::AWAY);
			$this->manager->setUserStatus($userId, IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND, true);
		}
		$this->logger->debug('User status automation ran');
	}

	protected function setLastRunToNextToggleTime(string $userId, int $timestamp): void {
		$query = $this->connection->getQueryBuilder();

		$query->update('jobs')
			->set('last_run', $query->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('id', $query->createNamedParameter($this->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$this->logger->debug('Updated user status automation last_run to ' . $timestamp . ' for user ' . $userId);
	}

	/**
	 * @param string $userId
	 * @return false|string
	 */
	protected function getAvailabilityFromPropertiesTable(string $userId) {
		$propertyPath = 'calendars/' . $userId . '/inbox';
		$propertyName = '{' . Plugin::NS_CALDAV . '}calendar-availability';

		$query = $this->connection->getQueryBuilder();
		$query->select('propertyvalue')
			->from('properties')
			->where($query->expr()->eq('userid', $query->createNamedParameter($userId)))
			->andWhere($query->expr()->eq('propertypath', $query->createNamedParameter($propertyPath)))
			->andWhere($query->expr()->eq('propertyname', $query->createNamedParameter($propertyName)))
			->setMaxResults(1);

		$result = $query->executeQuery();
		$property = $result->fetchOne();
		$result->closeCursor();

		return $property;
	}
}
