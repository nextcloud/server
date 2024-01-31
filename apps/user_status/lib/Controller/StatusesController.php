<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\UserStatus\Controller;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\ResponseDefinitions;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
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
