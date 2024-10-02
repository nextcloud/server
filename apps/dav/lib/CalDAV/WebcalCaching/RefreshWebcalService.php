<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Component;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\NoInstancesException;
use Sabre\VObject\Splitter\ICalendar;
use Sabre\VObject\UUIDUtil;
use function count;

class RefreshWebcalService {

	public const REFRESH_RATE = '{http://apple.com/ns/ical/}refreshrate';
	public const STRIP_ALARMS = '{http://calendarserver.org/ns/}subscribed-strip-alarms';
	public const STRIP_ATTACHMENTS = '{http://calendarserver.org/ns/}subscribed-strip-attachments';
	public const STRIP_TODOS = '{http://calendarserver.org/ns/}subscribed-strip-todos';

	public function __construct(
		private CalDavBackend $calDavBackend,
		private LoggerInterface $logger,
		private Connection $connection,
		private ITimeFactory $time,
	) {
	}

	public function refreshSubscription(string $principalUri, string $uri) {
		$subscription = $this->getSubscription($principalUri, $uri);
		$mutations = [];
		if (!$subscription) {
			return;
		}

		// Check the refresh rate if there is any
		if (!empty($subscription['{http://apple.com/ns/ical/}refreshrate'])) {
			// add the refresh interval to the lastmodified timestamp
			$refreshInterval = new \DateInterval($subscription['{http://apple.com/ns/ical/}refreshrate']);
			$updateTime = $this->time->getDateTime();
			$updateTime->setTimestamp($subscription['lastmodified'])->add($refreshInterval);
			if ($updateTime->getTimestamp() > $this->time->getTime()) {
				return;
			}
		}


		$webcalData = $this->connection->queryWebcalFeed($subscription);
		if (!$webcalData) {
			return;
		}

		$localData = $this->calDavBackend->getLimitedCalendarObjects((int)$subscription['id'], CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION);

		$stripTodos = ($subscription[self::STRIP_TODOS] ?? 1) === 1;
		$stripAlarms = ($subscription[self::STRIP_ALARMS] ?? 1) === 1;
		$stripAttachments = ($subscription[self::STRIP_ATTACHMENTS] ?? 1) === 1;

		try {
			$splitter = new ICalendar($webcalData, Reader::OPTION_FORGIVING);

			while ($vObject = $splitter->getNext()) {
				/** @var Component $vObject */
				$compName = null;
				$uid = null;

				foreach ($vObject->getComponents() as $component) {
					if ($component->name === 'VTIMEZONE') {
						continue;
					}

					$compName = $component->name;

					if ($stripAlarms) {
						unset($component->{'VALARM'});
					}
					if ($stripAttachments) {
						unset($component->{'ATTACH'});
					}

					$uid = $component->{ 'UID' }->getValue();
				}

				if ($stripTodos && $compName === 'VTODO') {
					continue;
				}

				if (!isset($uid)) {
					continue;
				}

				try {
					$denormalized = $this->calDavBackend->getDenormalizedData($vObject->serialize());
				} catch (InvalidDataException|Forbidden $ex) {
					$this->logger->warning('Unable to denormalize calendar object from subscription {subscriptionId}', ['exception' => $ex, 'subscriptionId' => $subscription['id'], 'source' => $subscription['source']]);
					continue;
				}

				// Find all identical sets and remove them from the update
				if (isset($localData[$uid]) && $denormalized['etag'] === $localData[$uid]['etag']) {
					unset($localData[$uid]);
					continue;
				}

				$vObjectCopy = clone $vObject;
				$identical = isset($localData[$uid]) && $this->compareWithoutDtstamp($vObjectCopy, $localData[$uid]);
				if ($identical) {
					unset($localData[$uid]);
					continue;
				}

				// Find all modified sets and update them
				if (isset($localData[$uid]) && $denormalized['etag'] !== $localData[$uid]['etag']) {
					$this->calDavBackend->updateCalendarObject($subscription['id'], $localData[$uid]['uri'], $vObject->serialize(), CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION);
					unset($localData[$uid]);
					continue;
				}

				// Only entirely new events get created here
				try {
					$objectUri = $this->getRandomCalendarObjectUri();
					$this->calDavBackend->createCalendarObject($subscription['id'], $objectUri, $vObject->serialize(), CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION);
				} catch (NoInstancesException|BadRequest $ex) {
					$this->logger->warning('Unable to create calendar object from subscription {subscriptionId}', ['exception' => $ex, 'subscriptionId' => $subscription['id'], 'source' => $subscription['source']]);
				}
			}

			$ids = array_map(static function ($dataSet): int {
				return (int)$dataSet['id'];
			}, $localData);
			$uris = array_map(static function ($dataSet): string {
				return $dataSet['uri'];
			}, $localData);

			if (!empty($ids) && !empty($uris)) {
				// Clean up on aisle 5
				// The only events left over in the $localData array should be those that don't exist upstream
				// All deleted VObjects from upstream are removed
				$this->calDavBackend->purgeCachedEventsForSubscription($subscription['id'], $ids, $uris);
			}

			$newRefreshRate = $this->checkWebcalDataForRefreshRate($subscription, $webcalData);
			if ($newRefreshRate) {
				$mutations[self::REFRESH_RATE] = $newRefreshRate;
			}

			$this->updateSubscription($subscription, $mutations);
		} catch (ParseException $ex) {
			$this->logger->error('Subscription {subscriptionId} could not be refreshed due to a parsing error', ['exception' => $ex, 'subscriptionId' => $subscription['id']]);
		}
	}

	/**
	 * loads subscription from backend
	 */
	public function getSubscription(string $principalUri, string $uri): ?array {
		$subscriptions = array_values(array_filter(
			$this->calDavBackend->getSubscriptionsForUser($principalUri),
			function ($sub) use ($uri) {
				return $sub['uri'] === $uri;
			}
		));

		if (count($subscriptions) === 0) {
			return null;
		}

		return $subscriptions[0];
	}


	/**
	 * check if:
	 *  - current subscription stores a refreshrate
	 *  - the webcal feed suggests a refreshrate
	 *  - return suggested refreshrate if user didn't set a custom one
	 *
	 */
	private function checkWebcalDataForRefreshRate(array $subscription, string $webcalData): ?string {
		// if there is no refreshrate stored in the database, check the webcal feed
		// whether it suggests any refresh rate and store that in the database
		if (isset($subscription[self::REFRESH_RATE]) && $subscription[self::REFRESH_RATE] !== null) {
			return null;
		}

		/** @var Component\VCalendar $vCalendar */
		$vCalendar = Reader::read($webcalData);

		$newRefreshRate = null;
		if (isset($vCalendar->{'X-PUBLISHED-TTL'})) {
			$newRefreshRate = $vCalendar->{'X-PUBLISHED-TTL'}->getValue();
		}
		if (isset($vCalendar->{'REFRESH-INTERVAL'})) {
			$newRefreshRate = $vCalendar->{'REFRESH-INTERVAL'}->getValue();
		}

		if (!$newRefreshRate) {
			return null;
		}

		// check if new refresh rate is even valid
		try {
			DateTimeParser::parseDuration($newRefreshRate);
		} catch (InvalidDataException $ex) {
			return null;
		}

		return $newRefreshRate;
	}

	/**
	 * update subscription stored in database
	 * used to set:
	 *  - refreshrate
	 *  - source
	 *
	 * @param array $subscription
	 * @param array $mutations
	 */
	private function updateSubscription(array $subscription, array $mutations) {
		if (empty($mutations)) {
			return;
		}

		$propPatch = new PropPatch($mutations);
		$this->calDavBackend->updateSubscription($subscription['id'], $propPatch);
		$propPatch->commit();
	}

	/**
	 * Returns a random uri for a calendar-object
	 *
	 * @return string
	 */
	public function getRandomCalendarObjectUri():string {
		return UUIDUtil::getUUID() . '.ics';
	}

	private function compareWithoutDtstamp(Component $vObject, array $calendarObject): bool {
		foreach ($vObject->getComponents() as $component) {
			unset($component->{'DTSTAMP'});
		}

		$localVobject = Reader::read($calendarObject['calendardata']);
		foreach ($localVobject->getComponents() as $component) {
			unset($component->{'DTSTAMP'});
		}

		return strcasecmp($localVobject->serialize(), $vObject->serialize()) === 0;
	}
}
