<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Component;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
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
		private ImportService $importService,
	) {
	}

	public function refreshSubscription(string $principalUri, string $uri) {
		$subscription = $this->getSubscription($principalUri, $uri);
		if (!$subscription) {
			return;
		}

		// Check the refresh rate if there is any
		if (!empty($subscription[self::REFRESH_RATE])) {
			// add the refresh interval to the last modified timestamp
			$refreshInterval = new \DateInterval($subscription[self::REFRESH_RATE]);
			$updateTime = $this->time->getDateTime();
			$updateTime->setTimestamp($subscription['lastmodified'])->add($refreshInterval);
			if ($updateTime->getTimestamp() > $this->time->getTime()) {
				return;
			}
		}

		$result = $this->connection->queryWebcalFeed($subscription);
		if (!$result) {
			return;
		}

		$data = $result['data'];
		$format = $result['format'];

		$stripTodos = ($subscription[self::STRIP_TODOS] ?? 1) === 1;
		$stripAlarms = ($subscription[self::STRIP_ALARMS] ?? 1) === 1;
		$stripAttachments = ($subscription[self::STRIP_ATTACHMENTS] ?? 1) === 1;

		try {
			$existingObjects = $this->calDavBackend->getLimitedCalendarObjects((int)$subscription['id'], CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION, ['id', 'uid', 'etag', 'uri']);

			$generator = match ($format) {
				'xcal' => $this->importService->importXml(...),
				'jcal' => $this->importService->importJson(...),
				default => $this->importService->importText(...)
			};

			foreach ($generator($data) as $vObject) {
				/** @var Component\VCalendar $vObject */
				$vBase = $vObject->getBaseComponent();

				if (!$vBase->UID) {
					continue;
				}

				// Some calendar providers (e.g. Google, MS) use very long UIDs
				if (strlen($vBase->UID->getValue()) > 512) {
					$this->logger->warning('Skipping calendar object with overly long UID from subscription "{subscriptionId}"', [
						'subscriptionId' => $subscription['id'],
						'uid' => $vBase->UID->getValue(),
					]);
					continue;
				}

				if ($stripTodos && $vBase->name === 'VTODO') {
					continue;
				}

				if ($stripAlarms || $stripAttachments) {
					foreach ($vObject->getComponents() as $component) {
						if ($component->name === 'VTIMEZONE') {
							continue;
						}
						if ($stripAlarms) {
							$component->remove('VALARM');
						}
						if ($stripAttachments) {
							$component->remove('ATTACH');
						}
					}
				}

				$sObject = $vObject->serialize();
				$uid = $vBase->UID->getValue();
				$etag = md5($sObject);

				// No existing object with this UID, create it
				if (!isset($existingObjects[$uid])) {
					try {
						$this->calDavBackend->createCalendarObject(
							$subscription['id'],
							UUIDUtil::getUUID() . '.ics',
							$sObject,
							CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION
						);
					} catch (\Exception $ex) {
						$this->logger->warning('Unable to create calendar object from subscription {subscriptionId}', [
							'exception' => $ex,
							'subscriptionId' => $subscription['id'],
							'source' => $subscription['source'],
						]);
					}
				} elseif ($existingObjects[$uid]['etag'] !== $etag) {
					// Existing object with this UID but different etag, update it
					$this->calDavBackend->updateCalendarObject(
						$subscription['id'],
						$existingObjects[$uid]['uri'],
						$sObject,
						CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION
					);
					unset($existingObjects[$uid]);
				} else {
					// Existing object with same etag, just remove from tracking
					unset($existingObjects[$uid]);
				}
			}

			// Clean up objects that no longer exist in the remote feed
			// The only events left over should be those not found upstream
			if (!empty($existingObjects)) {
				$ids = array_map('intval', array_column($existingObjects, 'id'));
				$uris = array_column($existingObjects, 'uri');
				$this->calDavBackend->purgeCachedEventsForSubscription((int)$subscription['id'], $ids, $uris);
			}

			// Update refresh rate from the last processed object
			if (isset($vObject)) {
				$this->updateRefreshRate($subscription, $vObject);
			}
		} catch (ParseException $ex) {
			$this->logger->error('Subscription {subscriptionId} could not be refreshed due to a parsing error', ['exception' => $ex, 'subscriptionId' => $subscription['id']]);
		} finally {
			// Close the data stream to free resources
			if (is_resource($data)) {
				fclose($data);
			}
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
	 * Update refresh rate from calendar object if:
	 *  - current subscription does not store a refreshrate
	 *  - the webcal feed suggests a valid refreshrate
	 */
	private function updateRefreshRate(array $subscription, Component\VCalendar $vCalendar): void {
		// if there is already a refreshrate stored in the database, don't override it
		if (!empty($subscription[self::REFRESH_RATE])) {
			return;
		}

		$refreshRate = $vCalendar->{'REFRESH-INTERVAL'}?->getValue()
			?? $vCalendar->{'X-PUBLISHED-TTL'}?->getValue();

		if ($refreshRate === null) {
			return;
		}

		// check if refresh rate is valid
		try {
			DateTimeParser::parseDuration($refreshRate);
		} catch (InvalidDataException) {
			return;
		}

		$propPatch = new PropPatch([self::REFRESH_RATE => $refreshRate]);
		$this->calDavBackend->updateSubscription($subscription['id'], $propPatch);
		$propPatch->commit();
	}

}
