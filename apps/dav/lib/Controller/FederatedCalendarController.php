<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarInvitationService;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\ResponseDefinitions;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type DAVPendingFederatedCalendar from ResponseDefinitions
 */
class FederatedCalendarController extends OCSController {
	public function __construct(
		IRequest $request,
		private readonly ?string $userId,
		private readonly FederatedCalendarMapper $mapper,
		private readonly FederatedCalendarInvitationService $invitationService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Get the pending federated calendar invitations of the current user
	 *
	 * @return DataResponse<Http::STATUS_OK, list<DAVPendingFederatedCalendar>, array{}>|DataResponse<Http::STATUS_UNAUTHORIZED, null, array{}>
	 *
	 * 200: Pending federated calendar invitations
	 * 401: When the user is not logged in
	 */
	#[NoAdminRequired]
	public function getPending(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$calendars = $this->mapper->findByPrincipalUri(
			'principals/users/' . $this->userId,
			FederatedCalendarEntity::STATE_PENDING,
		);

		return new DataResponse(array_values(array_map(static fn (FederatedCalendarEntity $calendar) => [
			'id' => $calendar->getId(),
			'displayName' => $calendar->getDisplayName(),
			'color' => $calendar->getColor(),
			'sharedBy' => $calendar->getSharedBy(),
			'sharedByDisplayName' => $calendar->getSharedByDisplayName(),
			'remoteUrl' => $calendar->getRemoteUrl(),
			'permissions' => $calendar->getPermissions(),
			'components' => $calendar->getComponents(),
		], $calendars)));
	}

	/**
	 * Accept a pending federated calendar invitation
	 *
	 * @param int $id The id of the pending federated calendar
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSNotFoundException The federated calendar was not found
	 *
	 * 200: Invitation accepted successfully
	 */
	#[NoAdminRequired]
	public function accept(int $id): DataResponse {
		$this->invitationService->accept($this->getPendingCalendar($id));
		return new DataResponse(null);
	}

	/**
	 * Decline a pending federated calendar invitation
	 *
	 * @param int $id The id of the pending federated calendar
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSNotFoundException The federated calendar was not found
	 *
	 * 200: Invitation declined successfully
	 */
	#[NoAdminRequired]
	public function decline(int $id): DataResponse {
		$this->invitationService->decline($this->getPendingCalendar($id));
		return new DataResponse(null);
	}

	/**
	 * @throws OCSNotFoundException
	 */
	private function getPendingCalendar(int $id): FederatedCalendarEntity {
		if ($this->userId === null) {
			throw new OCSNotFoundException();
		}

		try {
			$calendar = $this->mapper->find($id);
		} catch (DoesNotExistException) {
			throw new OCSNotFoundException();
		}

		// Also return 404 for foreign calendars to not leak their existence
		if ($calendar->getPrincipaluri() !== 'principals/users/' . $this->userId
			|| $calendar->getState() !== FederatedCalendarEntity::STATE_PENDING) {
			throw new OCSNotFoundException();
		}

		return $calendar;
	}
}
