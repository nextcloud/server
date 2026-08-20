<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Controller;

use OC\AppFramework\Http\PaginationTrait;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\ResponseDefinitions;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\UserStatus\IUserStatus;

/**
 * @psalm-import-type UserStatusType from ResponseDefinitions
 * @psalm-import-type UserStatusPublic from ResponseDefinitions
 */
class StatusesController extends OCSController {
	use PaginationTrait;

	/**
	 * StatusesController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param StatusService $service
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private StatusService $service,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Find statuses of users
	 *
	 * @param int|null $limit Maximum number of statuses to find
	 * @param non-negative-int|null $offset Offset for finding statuses
	 * @param non-negative-int|null $lastId Id of the last status returned by the previous page;
	 *                                      when given, keyset pagination is used and $offset is ignored
	 * @return DataResponse<Http::STATUS_OK, list<UserStatusPublic>, array{Link?: string}>
	 *
	 * @note Prefer $lastId over $offset: it does not require the database to scan and discard
	 *       every preceding row on every call.
	 *
	 * 200: Statuses returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/statuses')]
	public function findAll(?int $limit = null, ?int $offset = null, ?int $lastId = null): DataResponse {
		if ($lastId !== null) {
			$allStatuses = $this->service->findAllAfterId($limit, $lastId);
		} else {
			$allStatuses = $this->service->findAll($limit, $offset);
		}
		if ($lastId !== null) {
			$lastStatus = end($allStatuses);
			$headers = $this->buildCursorNextPageLinkHeader($allStatuses, [], $limit, $lastStatus !== false ? $lastStatus->getId() : null);
		} else {
			$headers = $this->buildOffsetNextPageLinkHeader($allStatuses, [], $limit, $offset ?? 0);
		}
		return new DataResponse(array_values(array_map(function ($userStatus) {
			return $this->formatStatus($userStatus);
		}, $allStatuses)), headers: $headers);
	}

	/**
	 * Find the status of a user
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, UserStatusPublic, array{}>
	 * @throws OCSNotFoundException The user was not found
	 *
	 * 200: Status returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/statuses/{userId}')]
	public function find(string $userId): DataResponse {
		try {
			$userStatus = $this->service->findByUserId($userId);
		} catch (DoesNotExistException $ex) {
			throw new OCSNotFoundException('No status for the requested userId');
		}

		return new DataResponse($this->formatStatus($userStatus));
	}

	/**
	 * @param UserStatus $status
	 * @return UserStatusPublic
	 */
	private function formatStatus(UserStatus $status): array {
		/** @var UserStatusType $visibleStatus */
		$visibleStatus = $status->getStatus();
		if ($visibleStatus === IUserStatus::INVISIBLE) {
			$visibleStatus = IUserStatus::OFFLINE;
		}

		return [
			'userId' => $status->getUserId(),
			'message' => $status->getCustomMessage(),
			'icon' => $status->getCustomIcon(),
			'clearAt' => $status->getClearAt(),
			'status' => $visibleStatus,
		];
	}
}
