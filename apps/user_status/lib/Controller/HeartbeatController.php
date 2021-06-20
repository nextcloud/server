<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IUserStatus;

class HeartbeatController extends Controller {

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
	 * @NoAdminRequired
	 *
	 * @param string $status
	 * @return JSONResponse
	 */
	public function heartbeat(string $status): JSONResponse {
		if (!\in_array($status, [IUserStatus::ONLINE, IUserStatus::AWAY], true)) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$this->eventDispatcher->dispatchTyped(
			new UserLiveStatusEvent(
				$user,
				$status,
				$this->timeFactory->getTime()
			)
		);

		try {
			$userStatus = $this->service->findByUserId($user->getUID());
		} catch (DoesNotExistException $ex) {
			return new JSONResponse([], Http::STATUS_NO_CONTENT);
		}

		return new JSONResponse($this->formatStatus($userStatus));
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
