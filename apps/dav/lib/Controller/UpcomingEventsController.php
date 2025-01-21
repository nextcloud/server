<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\UpcomingEvent;
use OCA\DAV\CalDAV\UpcomingEventsService;
use OCA\DAV\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type DAVUpcomingEvent from ResponseDefinitions
 */
class UpcomingEventsController extends OCSController {
	public function __construct(
		IRequest $request,
		private ?string $userId,
		private UpcomingEventsService $service,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Get information about upcoming events
	 *
	 * @param string|null $location location/URL to filter by
	 * @return DataResponse<Http::STATUS_OK, array{events: list<DAVUpcomingEvent>}, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED, null, array{}>
	 *
	 * 200: Upcoming events
	 * 401: When not authenticated
	 */
	#[NoAdminRequired]
	public function getEvents(?string $location = null): DataResponse {
		if ($this->userId === null) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		return new DataResponse([
			'events' => array_values(array_map(fn (UpcomingEvent $e) => $e->jsonSerialize(), $this->service->getEvents(
				$this->userId,
				$location,
			))),
		]);
	}

}
