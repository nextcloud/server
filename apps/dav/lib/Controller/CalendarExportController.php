<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Export\ExportService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamGeneratorResponse;
use OCP\Calendar\CalendarExportOptions;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\IManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class CalendarExportController extends ApiController {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IManager $calendarManager,
		private ExportService $exportService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}
	
	/**
	 * Export calendar data
	 *
	 * @param string $id calendar id
	 * @param string|null $format data format
	 * @param array<string,mixed> $options configuration options
	 * @param string|null $user system user id
	 *
	 * @return DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED,array{error?:non-empty-string},array{}>|\OCP\AppFramework\Http\StreamGeneratorResponse
	 *
	 * 200: calendar data
	 * 401: user not authorized
	 * 400: invalid parameters
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	#[ApiRoute(verb: 'GET', url: '/export', root: '/calendar')]
	#[ApiRoute(verb: 'POST', url: '/export', root: '/calendar')]
	#[UserRateLimit(limit: 1, period: 60)]
	#[NoAdminRequired]
	public function index(string $id, ?string $format = null, ?array $options = null, ?string $user = null) {
		$userId = $user;
		$calendarId = $id;
		$rangeStart = isset($options['rangeStart']) ? (string)$options['rangeStart'] : null;
		$rangeCount = isset($options['rangeCount']) ? (int)$options['rangeCount'] : null;
		// evaluate if user is logged in and has permissions
		if (!$this->userSession->isLoggedIn()) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if ($userId !== null) {
			if ($this->userSession->getUser()->getUID() !== $userId &&
				$this->groupManager->isAdmin($this->userSession->getUser()->getUID()) === false) {
				return new DataResponse([], Http::STATUS_UNAUTHORIZED);
			}
			if (!$this->userManager->userExists($userId)) {
				return new DataResponse(['error' => 'user not found'], Http::STATUS_BAD_REQUEST);
			}
		} else {
			$userId = $this->userSession->getUser()->getUID();
		}
		// retrieve calendar and evaluate if export is supported
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId, [$calendarId]);
		if ($calendars === []) {
			return new DataResponse(['error' => 'calendar not found'], Http::STATUS_BAD_REQUEST);
		}
		$calendar = $calendars[0];
		if (!$calendar instanceof ICalendarExport) {
			return new DataResponse(['error' => 'calendar export not supported'], Http::STATUS_BAD_REQUEST);
		}
		// construct options object
		$options = new CalendarExportOptions();
		$options->setRangeStart($rangeStart);
		$options->setRangeCount($rangeCount);
		// evaluate if provided format is supported
		if ($format !== null && !in_array($format, $this->exportService::FORMATS, true)) {
			return new DataResponse(['error' => "Format <$format> is not valid."], Http::STATUS_BAD_REQUEST);
		} else {
			$options->setFormat($format ?? 'ical');
		}
		$contentType = match (strtolower($options->getFormat())) {
			'jcal' => 'application/calendar+json; charset=UTF-8',
			'xcal' => 'application/calendar+xml; charset=UTF-8',
			default => 'text/calendar; charset=UTF-8'
		};

		return new StreamGeneratorResponse($this->exportService->export($calendar, $options), $contentType);
	}
}
