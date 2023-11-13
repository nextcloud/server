<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Controller;

use DateTimeImmutable;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\Service\AbsenceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IUserSession;

class AvailabilitySettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private ?IUserSession $userSession,
		private AbsenceService $absenceService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @throws \OCP\DB\Exception
	 * @throws \Exception
	 */
	#[NoAdminRequired]
	public function updateAbsence(
		string $firstDay,
		string $lastDay,
		string $status,
		string $message,
	): Response {
		$user = $this->userSession?->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$parsedFirstDay = new DateTimeImmutable($firstDay);
		$parsedLastDay = new DateTimeImmutable($lastDay);
		if ($parsedFirstDay->getTimestamp() >= $parsedLastDay->getTimestamp()) {
			throw new \Exception('First day is on or after last day');
		}

		$absence = $this->absenceService->createOrUpdateAbsence(
			$user,
			$firstDay,
			$lastDay,
			$status,
			$message,
		);
		return new JSONResponse($absence);
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	#[NoAdminRequired]
	public function clearAbsence(): Response {
		$user = $this->userSession?->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->absenceService->clearAbsence($user);
		return new JSONResponse([]);
	}

}
