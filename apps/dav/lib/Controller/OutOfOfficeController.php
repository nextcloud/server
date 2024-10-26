<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use DateTimeImmutable;
use OCA\DAV\ResponseDefinitions;
use OCA\DAV\Service\AbsenceService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\IAvailabilityCoordinator;

/**
 * @psalm-import-type DAVOutOfOfficeData from ResponseDefinitions
 * @psalm-import-type DAVCurrentOutOfOfficeData from ResponseDefinitions
 */
class OutOfOfficeController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IUserManager $userManager,
		private ?IUserSession $userSession,
		private AbsenceService $absenceService,
		private IAvailabilityCoordinator $coordinator,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the currently configured out-of-office data of a user
	 *
	 * @param string $userId The user id to get out-of-office data for.
	 * @return DataResponse<Http::STATUS_OK, DAVCurrentOutOfOfficeData, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Out-of-office data
	 * 404: No out-of-office data was found
	 */
	#[NoAdminRequired]
	public function getCurrentOutOfOfficeData(string $userId): DataResponse {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		try {
			$data = $this->absenceService->getCurrentAbsence($user);
			if ($data === null) {
				return new DataResponse(null, Http::STATUS_NOT_FOUND);
			}
		} catch (DoesNotExistException) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($data->jsonSerialize());
	}

	/**
	 * Get the configured out-of-office data of a user.
	 *
	 * @param string $userId The user id to get out-of-office data for.
	 * @return DataResponse<Http::STATUS_OK, DAVOutOfOfficeData, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Out-of-office data
	 * 404: No out-of-office data was found
	 */
	#[NoAdminRequired]
	public function getOutOfOffice(string $userId): DataResponse {
		try {
			$data = $this->absenceService->getAbsence($userId);
			if ($data === null) {
				return new DataResponse(null, Http::STATUS_NOT_FOUND);
			}
		} catch (DoesNotExistException) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse([
			'id' => $data->getId(),
			'userId' => $data->getUserId(),
			'firstDay' => $data->getFirstDay(),
			'lastDay' => $data->getLastDay(),
			'status' => $data->getStatus(),
			'message' => $data->getMessage(),
			'replacementUserId' => $data->getReplacementUserId(),
			'replacementUserDisplayName' => $data->getReplacementUserDisplayName(),
		]);
	}

	/**
	 * Set out-of-office absence
	 *
	 * @param string $firstDay First day of the absence in format `YYYY-MM-DD`
	 * @param string $lastDay Last day of the absence in format `YYYY-MM-DD`
	 * @param string $status Short text that is set as user status during the absence
	 * @param string $message Longer multiline message that is shown to others during the absence
	 * @param ?string $replacementUserId User id of the replacement user
	 * @param ?string $replacementUserDisplayName Display name of the replacement user
	 * @return DataResponse<Http::STATUS_OK, DAVOutOfOfficeData, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'firstDay'}, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED, null, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Absence data
	 * 400: When the first day is not before the last day
	 * 401: When the user is not logged in
	 * 404: When the replacementUserId was provided but replacement user was not found
	 */
	#[NoAdminRequired]
	public function setOutOfOffice(
		string $firstDay,
		string $lastDay,
		string $status,
		string $message,
		?string $replacementUserId,
		?string $replacementUserDisplayName,

	): DataResponse {
		$user = $this->userSession?->getUser();
		if ($user === null) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		if ($replacementUserId !== null) {
			$replacementUser = $this->userManager->get($replacementUserId);
			if ($replacementUser === null) {
				return new DataResponse(null, Http::STATUS_NOT_FOUND);
			}
		}

		$parsedFirstDay = new DateTimeImmutable($firstDay);
		$parsedLastDay = new DateTimeImmutable($lastDay);
		if ($parsedFirstDay->getTimestamp() > $parsedLastDay->getTimestamp()) {
			return new DataResponse(['error' => 'firstDay'], Http::STATUS_BAD_REQUEST);
		}

		$data = $this->absenceService->createOrUpdateAbsence(
			$user,
			$firstDay,
			$lastDay,
			$status,
			$message,
			$replacementUserId,
			$replacementUserDisplayName
		);
		$this->coordinator->clearCache($user->getUID());

		return new DataResponse([
			'id' => $data->getId(),
			'userId' => $data->getUserId(),
			'firstDay' => $data->getFirstDay(),
			'lastDay' => $data->getLastDay(),
			'status' => $data->getStatus(),
			'message' => $data->getMessage(),
			'replacementUserId' => $data->getReplacementUserId(),
			'replacementUserDisplayName' => $data->getReplacementUserDisplayName(),
		]);
	}

	/**
	 * Clear the out-of-office
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_UNAUTHORIZED, null, array{}>
	 *
	 * 200: When the absence was cleared successfully
	 * 401: When the user is not logged in
	 */
	#[NoAdminRequired]
	public function clearOutOfOffice(): DataResponse {
		$user = $this->userSession?->getUser();
		if ($user === null) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$this->absenceService->clearAbsence($user);
		$this->coordinator->clearCache($user->getUID());
		return new DataResponse(null);
	}
}
