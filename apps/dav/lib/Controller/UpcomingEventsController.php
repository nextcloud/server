<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\IManager;
use OCP\IRequest;
use function array_map;

class UpcomingEventsController extends OCSController {
	private IManager $calendarManager;
	private ?string $userId;
	private ITimeFactory $timeFactory;

	public function __construct(
		IRequest $request,
		?string $userId,
		IManager $calendarManager,
		ITimeFactory $timeFactory) {
		parent::__construct(Application::APP_ID, $request);

		$this->userId = $userId;
		$this->calendarManager = $calendarManager;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * Get information about upcoming events
	 */
	#[NoAdminRequired]
	public function getEvents(string $location = null): DataResponse {
		if ($this->userId === null) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		// TODO: move logic into a service class
		$searchQuery = $this->calendarManager->newQuery('principals/users/' . $this->userId);
		if ($location !== null) {
			$searchQuery->addSearchProperty('LOCATION');
			$searchQuery->setSearchPattern($location);
		}
		$searchQuery->addType('VEVENT');
		$searchQuery->setLimit(5);
		$now = $this->timeFactory->now();
		$searchQuery->setTimerangeStart($now->modify('-1 minute'));
		$searchQuery->setTimerangeEnd($now->modify('+7 days'));

		$events = $this->calendarManager->searchForPrincipal($searchQuery);

		return new DataResponse([
			'events' => array_map(function ($event) {
				return [
					'uri' => $event['uri'],
					'calendar-id' => (int) $event['calendar-key'],
					'start' => $event['objects'][0]['DTSTART'][0]?->getTimestamp(),
					'summary' => $event['objects'][0]['SUMMARY'][0] ?? null,
					'location' => $event['objects'][0]['LOCATION'][0] ?? null,
				];
			}, $events),
		]);
	}

}
