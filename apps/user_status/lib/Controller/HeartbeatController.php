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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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

	public function __construct(
		string $appName,
		IRequest $request,
		private IEventDispatcher $eventDispatcher,
		private IUserSession $userSession,
		private ITimeFactory $timeFactory,
		private StatusService $service,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Keep the status alive
	 *
	 * @param string $status Only online, away
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_INTERNAL_SERVER_ERROR|Http::STATUS_NO_CONTENT, list<empty>, array{}>
	 *
	 * 200: Status successfully updated
	 * 204: User has no status to keep alive
	 * 400: Invalid status to update
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/heartbeat')]
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
