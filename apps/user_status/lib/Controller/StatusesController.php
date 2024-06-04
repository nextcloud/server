<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Controller;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\ResponseDefinitions;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\UserStatus\IUserStatus;

/**
 * @psalm-import-type UserStatusType from ResponseDefinitions
 * @psalm-import-type UserStatusPublic from ResponseDefinitions
 */
class StatusesController extends OCSController {

	/** @var StatusService */
	private $service;

	/**
	 * StatusesController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param StatusService $service
	 */
	public function __construct(string $appName,
		IRequest $request,
		StatusService $service) {
		parent::__construct($appName, $request);
		$this->service = $service;
	}

	/**
	 * Find statuses of users
	 *
	 * @NoAdminRequired
	 *
	 * @param int|null $limit Maximum number of statuses to find
	 * @param int|null $offset Offset for finding statuses
	 * @return DataResponse<Http::STATUS_OK, UserStatusPublic[], array{}>
	 *
	 * 200: Statuses returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/statuses')]
	public function findAll(?int $limit = null, ?int $offset = null): DataResponse {
		$allStatuses = $this->service->findAll($limit, $offset);

		return new DataResponse(array_map(function ($userStatus) {
			return $this->formatStatus($userStatus);
		}, $allStatuses));
	}

	/**
	 * Find the status of a user
	 *
	 * @NoAdminRequired
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, UserStatusPublic, array{}>
	 * @throws OCSNotFoundException The user was not found
	 *
	 * 200: Status returned
	 */
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
