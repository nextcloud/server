<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\ICalendarImport;
use OCP\Calendar\ICalendarIsWritable;
use OCP\Calendar\IManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class CalendarImportController extends ApiController {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IManager $calendarManager,
		private ImportService $importService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}
	
	/**
	 * Import calendar data
	 *
	 * @param string $id calendar id
	 * @param array $options<format: string, validation: int, errors: int, supersede: bool> configuration options
	 * @param string $data calendar data
	 * @param string|null $user system user id
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_INTERNAL_SERVER_ERROR,array{created?:int<0,max>,error?:string,errors?:int<0,max>,skipped?:int<0,max>,time?:float,updated?:int<0,max>},array<never,never>>
	 *
	 * 200: calendar data
	 * 401: user not authorized
	 * 404: calendar not found
	 * 404: format not found
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	#[ApiRoute(verb: 'POST', url: '/import', root: '/calendar')]
	#[UserRateLimit(limit: 1, period: 60)]
	#[NoAdminRequired]
	public function index(string $id, array $options, string $data, ?string $user = null): DataResponse {
		$userId = $user;
		$calendarId = $id;
		$format = isset($options['format']) ? $options['format'] : null;
		$validation = isset($options['validation']) ? (int)$options['validation'] : null;
		$errors = isset($options['errors']) ? (int)$options['errors'] : null;
		$supersede = (bool)$options['supersede'] === true ? true : false;
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
		if (!$calendar instanceof ICalendarImport) {
			return new DataResponse(['error' => 'calendar export not supported'], Http::STATUS_BAD_REQUEST);
		}
		// evaluate if requested format is supported and convert to output content type
		if ($format !== null && !in_array($format, $this->importService::FORMATS, true)) {
			return new DataResponse(['error' => 'format invalid'], Http::STATUS_BAD_REQUEST);
		} elseif ($format === null) {
			$format = 'ical';
		}
		// retrieve calendar and evaluate if import is supported and writeable
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId, [$calendarId]);
		if ($calendars === []) {
			return new DataResponse(['error' => "Calendar <$calendarId> not found"], Http::STATUS_BAD_REQUEST);
		}
		$calendar = $calendars[0];
		if (!$calendar instanceof ICalendarImport || !$calendar instanceof ICalendarIsWritable) {
			return new DataResponse(['error' => "Calendar <$calendarId> dose support this function"], Http::STATUS_BAD_REQUEST);
		}
		if (!$calendar->isWritable()) {
			return new DataResponse(['error' => "Calendar <$calendarId> is not writeable"], Http::STATUS_BAD_REQUEST);
		}
		if ($calendar->isDeleted()) {
			return new DataResponse(['error' => "Calendar <$calendarId> is deleted"], Http::STATUS_BAD_REQUEST);
		}
		// construct options object
		$options = new CalendarImportOptions();
		$options->setSupersede($supersede);
		if ($errors !== null) {
			if ($errors < 0 || $errors > 1) {
				return new DataResponse(['error' => 'Invalid errors option specified'], Http::STATUS_BAD_REQUEST);
			}
			$options->setErrors($errors);
		}
		if ($validation !== null) {
			if ($validation < 0 || $validation > 2) {
				return new DataResponse(['error' => 'Invalid validation option specified'], Http::STATUS_BAD_REQUEST);
			}
			$options->setValidate($validation);
		}
		// evaluate if provided format is supported
		if ($format !== null && !in_array($format, $this->importService::FORMATS)) {
			return new DataResponse(['error' => "Format <$format> is not valid."], Http::STATUS_BAD_REQUEST);
		} else {
			$options->setFormat($format ?? 'ical');
		}
		// process the data
		$timeStarted = microtime(true);
		try {
			$temp = tmpfile();
			fwrite($temp, $data);
			unset($data);
			fseek($temp, 0);
			$outcome = $this->importService->import($temp, $calendar, $options);
		} catch (\Throwable $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		} finally {
			fclose($temp);
		}
		$timeFinished = microtime(true);

		$totalCreated = 0;
		$totalUpdated = 0;
		$totalSkipped = 0;
		$totalErrors = 0;

		if ($outcome !== []) {
			foreach ($outcome as $id => $result) {
				if (isset($result['outcome'])) {
					switch ($result['outcome']) {
						case 'created':
							$totalCreated++;
							break;
						case 'updated':
							$totalUpdated++;
							break;
						case 'exists':
							$totalSkipped++;
							break;
						case 'error':
							$totalErrors++;
							break;
					}
				}
				
			}
		}

		$summary = [
			'time' => ($timeFinished - $timeStarted),
			'created' => $totalCreated,
			'updated' => $totalUpdated,
			'skipped' => $totalSkipped,
			'errors' => $totalErrors,
		];

		return new DataResponse($summary, Http::STATUS_OK);

	}
}
