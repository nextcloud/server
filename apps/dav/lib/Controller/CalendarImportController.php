<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use InvalidArgumentException;
/**
 * @psalm-type CalendarImportResult = array{items: list<string>, total: non-negative-int}
 */
use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamGeneratorResponse;
use OCP\AppFramework\OCSController;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\IManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IUserManager;
use OCP\IUserSession;

class CalendarImportController extends OCSController {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private ITempManager $tempManager,
		private IManager $calendarManager,
		private ImportService $importService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Import calendar data
	 *
	 * @param string $transaction client generated transaction id
	 * @param string $target calendar id
	 * @param array{format?:string, validation?:0|1|2, errors?:0|1, supersede?:bool, showCreated?:bool, showUpdated?:bool, showSkipped?:bool, showErrors?:bool} $options configuration options
	 * @param string $data calendar data
	 * @param string|null $user system user id
	 *
	 * @return StreamGeneratorResponse<Http::STATUS_OK, array{Content-Type:'application/x-ndjson'}> | DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED, array{error?: non-empty-string}, array{}>
	 *
	 * 200: NDJSON stream of import event objects
	 * 400: invalid parameters
	 * 401: user not authorized
	 */
	#[ApiRoute(verb: 'POST', url: '/import', root: '/calendar')]
	#[UserRateLimit(limit: 10, period: 3600)]
	#[NoAdminRequired]
	public function import(string $transaction, string $target, array $options, string $data, ?string $user = null): DataResponse|StreamGeneratorResponse {
		$calendarId = $target;
		$format = isset($options['format']) ? $options['format'] : null;
		$validation = isset($options['validation']) ? (int)$options['validation'] : null;
		$errors = isset($options['errors']) ? (int)$options['errors'] : null;
		$supersede = $options['supersede'] ?? false;
		// evaluate if user is logged in and has permissions
		if (!$this->userSession->isLoggedIn()) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if ($user !== null) {
			if ($this->userSession->getUser()->getUID() !== $user
				&& $this->groupManager->isAdmin($this->userSession->getUser()->getUID()) === false) {
				return new DataResponse([], Http::STATUS_UNAUTHORIZED);
			}
			if (!$this->userManager->userExists($user)) {
				return new DataResponse(['error' => 'user not found'], Http::STATUS_BAD_REQUEST);
			}
			$userId = $user;
		} else {
			$userId = $this->userSession->getUser()->getUID();
		}
		// retrieve calendar and evaluate if import is supported and writeable
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId, [$calendarId]);
		if ($calendars === []) {
			return new DataResponse(['error' => "Calendar <$calendarId> not found"], Http::STATUS_BAD_REQUEST);
		}
		$calendar = $calendars[0];
		if (!$calendar instanceof CalendarImpl) {
			return new DataResponse(['error' => "Calendar <$calendarId> does not support this function"], Http::STATUS_BAD_REQUEST);
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
			try {
				$options->setErrors($errors);
			} catch (InvalidArgumentException) {
				return new DataResponse(['error' => 'Invalid errors option specified'], Http::STATUS_BAD_REQUEST);
			}
		}
		if ($validation !== null) {
			try {
				$options->setValidate($validation);
			} catch (InvalidArgumentException) {
				return new DataResponse(['error' => 'Invalid validation option specified'], Http::STATUS_BAD_REQUEST);
			}
		}
		try {
			$options->setFormat($format ?? 'ical');
		} catch (InvalidArgumentException) {
			return new DataResponse(['error' => 'Invalid format option specified'], Http::STATUS_BAD_REQUEST);
		}
		$options->setCounts(true);
		// process the data
		$tempPath = $this->tempManager->getTemporaryFile();
		$tempFile = fopen($tempPath, 'w+');
		fwrite($tempFile, $data);
		unset($data);
		fseek($tempFile, 0);

		$importGenerator = $this->importService->import($tempFile, $calendar, $options);
		$stream = (function () use ($importGenerator, $tempFile, $transaction): \Generator {
			yield json_encode(['type' => 'control', 'transaction' => $transaction, 'disposition' => 'start']) . "\n";
			try {
				foreach ($importGenerator as $result) {
					$data = $result->jsonSerialize();
					$data['transaction'] = $transaction;
					yield json_encode($data) . PHP_EOL;
				}
			} finally {
				yield json_encode(['type' => 'control', 'transaction' => $transaction, 'disposition' => 'end']) . "\n";
				fclose($tempFile);
			}
		})();

		return new StreamGeneratorResponse($stream, 'application/x-ndjson');
	}
}
