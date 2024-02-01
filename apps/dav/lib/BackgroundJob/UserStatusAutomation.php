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
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\IAvailabilityCoordinator;
use OCP\User\IOutOfOfficeData;
use OCP\UserStatus\IManager;
use OCP\UserStatus\IUserStatus;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\Available;
use Sabre\VObject\Component\VAvailability;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\RRuleIterator;

class UserStatusAutomation extends TimedJob {
	public function __construct(private ITimeFactory $timeFactory,
		private IDBConnection $connection,
		private IJobList $jobList,
		private LoggerInterface $logger,
		private IManager $manager,
		private IConfig $config,
		private IAvailabilityCoordinator $coordinator,
		private IUserManager $userManager) {
		parent::__construct($timeFactory);

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
		$user = $this->userManager->get($userId);
		if($user === null) {
			return;
		}

		$ooo = $this->coordinator->getCurrentOutOfOfficeData($user);

		$continue = $this->processOutOfOfficeData($user, $ooo);
		if($continue === false) {
			return;
		}

		$property = $this->getAvailabilityFromPropertiesTable($userId);
		$hasDndForOfficeHours = $this->config->getUserValue($userId, 'dav', 'user_status_automation', 'no') === 'yes';

		if (!$property) {
			// We found no ooo data and no availability settings, so we need to delete the job because there is no next runtime
			$this->logger->info('Removing ' . self::class . ' background job for user "' . $userId . '" because the user has no valid availability rules and no OOO data set');
			$this->jobList->remove(self::class, $argument);
			$this->manager->revertUserStatus($user->getUID(), IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
			$this->manager->revertUserStatus($user->getUID(), IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::DND);
			return;
		}

		$this->processAvailability($property, $user->getUID(), $hasDndForOfficeHours);
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

	/**
	 * @param string $property
	 * @param $userId
	 * @param $argument
	 * @return void
	 */
	private function processAvailability(string $property, string $userId, bool $hasDndForOfficeHours): void {
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
						$it = new RRuleIterator((string)$available->RRULE, $effectiveStart);
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
			$this->jobList->remove(self::class, ['userId' => $userId]);
			$this->manager->revertUserStatus($userId, IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
			return;
		}

		$nextAutomaticToggle = min($nextPotentialToggles);
		$this->setLastRunToNextToggleTime($userId, $nextAutomaticToggle - 1);

		if ($isCurrentlyAvailable) {
			$this->logger->debug('User is currently available, reverting DND status if applicable');
			$this->manager->revertUserStatus($userId, IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
			$this->logger->debug('User status automation ran');
			return;
		}

		if(!$hasDndForOfficeHours) {
			// Office hours are not set to DND, so there is nothing to do.
			return;
		}

		$this->logger->debug('User is currently NOT available, reverting call status if applicable and then setting DND');
		// The DND status automation is more important than the "Away - In call" so we also restore that one if it exists.
		$this->manager->revertUserStatus($userId, IUserStatus::MESSAGE_CALL, IUserStatus::AWAY);
		$this->manager->setUserStatus($userId, IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND, true);
		$this->logger->debug('User status automation ran');
	}

	private function processOutOfOfficeData(IUser $user, ?IOutOfOfficeData $ooo): bool {
		if(empty($ooo)) {
			// Reset the user status if the absence doesn't exist
			$this->logger->debug('User has no OOO period in effect, reverting DND status if applicable');
			$this->manager->revertUserStatus($user->getUID(), IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::DND);
			// We need to also run the availability automation
			return true;
		}

		if(!$this->coordinator->isInEffect($ooo)) {
			// Reset the user status if the absence is (no longer) in effect
			$this->logger->debug('User has no OOO period in effect, reverting DND status if applicable');
			$this->manager->revertUserStatus($user->getUID(), IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::DND);

			if($ooo->getStartDate() > $this->time->getTime()) {
				// Set the next run to take place at the start of the ooo period if it is in the future
				// This might be overwritten if there is an availability setting, but we can't determine
				// if this is the case here
				$this->setLastRunToNextToggleTime($user->getUID(), $ooo->getStartDate());
			}
			return true;
		}

		$this->logger->debug('User is currently in an OOO period, reverting other automated status and setting OOO DND status');
		// Revert both a possible 'CALL - away' and 'office hours - DND' status
		$this->manager->revertUserStatus($user->getUID(), IUserStatus::MESSAGE_CALL, IUserStatus::DND);
		$this->manager->revertUserStatus($user->getUID(), IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
		$this->manager->setUserStatus($user->getUID(), IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::DND, true, $ooo->getShortMessage());
		// Run at the end of an ooo period to return to availability / regular user status
		// If it's overwritten by a custom status in the meantime, there's nothing we can do about it
		$this->setLastRunToNextToggleTime($user->getUID(), $ooo->getEndDate());
		return false;
	}
}
