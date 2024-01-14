<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IUserStatus;

/**
 * @psalm-import-type UserStatusPrivate from ResponseDefinitions
 */
class HeartbeatController extends OCSController {

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var IUserSession */
	private $userSession;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var StatusService */
	private $service;

	public function __construct(string $appName,
		IRequest $request,
		IEventDispatcher $eventDispatcher,
		IUserSession $userSession,
		ITimeFactory $timeFactory,
		StatusService $service) {
		parent::__construct($appName, $request);
		$this->eventDispatcher = $eventDispatcher;
		$this->userSession = $userSession;
		$this->timeFactory = $timeFactory;
		$this->service = $service;
	}

	/**
	 * Keep the status alive
	 *
	 * @NoAdminRequired
	 *
	 * @param string $status Only online, away
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NO_CONTENT, array<empty>, array{}>
	 * 200: Status successfully updated
	 * 204: User has no status to keep alive
	 * 400: Invalid status to update
	 */
	public function heartbeat(string $status): DataResponse {
		if (!\in_array($status, [IUserStatus::ONLINE, IUserStatus::AWAY], true)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$event = new UserLiveStatusEvent(
			$user,
			$status,
			$this->timeFactory->getTime()
		);

		$this->eventDispatcher->dispatchTyped($event);

		$userStatus = $event->getUserStatus();
		if (!$userStatus) {
			return new DataResponse([], Http::STATUS_NO_CONTENT);
		}

		/** @psalm-suppress UndefinedInterfaceMethod */
		return new DataResponse($this->formatStatus($userStatus->getInternal()));
	}

	private function formatStatus(UserStatus $status): array {
		return [
			'userId' => $status->getUserId(),
			'message' => $status->getCustomMessage(),
			'messageId' => $status->getMessageId(),
			'messageIsPredefined' => $status->getMessageId() !== null,
			'icon' => $status->getCustomIcon(),
			'clearAt' => $status->getClearAt(),
			'status' => $status->getStatus(),
			'statusIsUserDefined' => $status->getIsUserDefined(),
		];
	}
}
