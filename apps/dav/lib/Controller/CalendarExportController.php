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
	 * @param string $id
	 * @param string|null $fmt
	 * @param string|null $user
	 */
	#[ApiRoute(verb: 'GET', url: '/export', root: '/calendar')]
	#[ApiRoute(verb: 'POST', url: '/export', root: '/calendar')]
	#[UserRateLimit(limit: 1, period: 60)]
	#[NoAdminRequired]
	public function index(string $id, ?string $fmt = null, ?string $user = null) {
		$userId = $user;
		$calendarId = $id;
		$format = $fmt;
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
		// evaluate if provided format is supported
		if ($format !== null && !in_array($format, $this->exportService::FORMATS, true)) {
			throw new \InvalidArgumentException("Format <$format> is not valid.");
		} else {
			$options->setFormat($format ?? 'ical');
		}
		$contentType = match (strtolower($format)) {
			'jcal' => 'application/calendar+json; charset=UTF-8',
			'xcal' => 'application/calendar+xml; charset=UTF-8',
			default => 'text/calendar; charset=UTF-8'
		};

		return new StreamGeneratorResponse($this->exportService->export($calendar, $options), $contentType);
	}
}
