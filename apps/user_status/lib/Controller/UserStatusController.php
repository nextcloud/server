<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Simon Spannagel <simonspa@kth.se>
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

use OCA\DAV\CalDAV\Status\StatusService as CalendarStatusService;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Exception\InvalidClearAtException;
use OCA\UserStatus\Exception\InvalidMessageIdException;
use OCA\UserStatus\Exception\InvalidStatusIconException;
use OCA\UserStatus\Exception\InvalidStatusTypeException;
use OCA\UserStatus\Exception\StatusMessageTooLongException;
use OCA\UserStatus\ResponseDefinitions;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type UserStatusType from ResponseDefinitions
 * @psalm-import-type UserStatusPrivate from ResponseDefinitions
 */
class UserStatusController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private string $userId,
		private LoggerInterface $logger,
		private StatusService $service,
		private CalendarStatusService $calendarStatusService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the status of the current user
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSNotFoundException The user was not found
	 *
	 * 200: The status was found successfully
	 */
	public function getStatus(): DataResponse {
		try {
			$this->calendarStatusService->processCalendarStatus($this->userId);
			$userStatus = $this->service->findByUserId($this->userId);
		} catch (DoesNotExistException $ex) {
			throw new OCSNotFoundException('No status for the current user');
		}

		return new DataResponse($this->formatStatus($userStatus));
	}

	/**
	 * Update the status type of the current user
	 *
	 * @NoAdminRequired
	 *
	 * @param string $statusType The new status type
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSBadRequestException The status type is invalid
	 *
	 * 200: The status was updated successfully
	 */
	public function setStatus(string $statusType): DataResponse {
		try {
			$status = $this->service->setStatus($this->userId, $statusType, null, true);

			$this->service->removeBackupUserStatus($this->userId);
			return new DataResponse($this->formatStatus($status));
		} catch (InvalidStatusTypeException $ex) {
			$this->logger->debug('New user-status for "' . $this->userId . '" was rejected due to an invalid status type "' . $statusType . '"');
			throw new OCSBadRequestException($ex->getMessage(), $ex);
		}
	}

	/**
	 * Set the message to a predefined message for the current user
	 *
	 * @NoAdminRequired
	 *
	 * @param string $messageId ID of the predefined message
	 * @param int|null $clearAt When the message should be cleared
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSBadRequestException The clearAt or message-id is invalid
	 *
	 * 200: The message was updated successfully
	 */
	public function setPredefinedMessage(string $messageId,
		?int $clearAt): DataResponse {
		try {
			$status = $this->service->setPredefinedMessage($this->userId, $messageId, $clearAt);
			$this->service->removeBackupUserStatus($this->userId);
			return new DataResponse($this->formatStatus($status));
		} catch (InvalidClearAtException $ex) {
			$this->logger->debug('New user-status for "' . $this->userId . '" was rejected due to an invalid clearAt value "' . $clearAt . '"');
			throw new OCSBadRequestException($ex->getMessage(), $ex);
		} catch (InvalidMessageIdException $ex) {
			$this->logger->debug('New user-status for "' . $this->userId . '" was rejected due to an invalid message-id "' . $messageId . '"');
			throw new OCSBadRequestException($ex->getMessage(), $ex);
		}
	}

	/**
	 * Set the message to a custom message for the current user
	 *
	 * @NoAdminRequired
	 *
	 * @param string|null $statusIcon Icon of the status
	 * @param string|null $message Message of the status
	 * @param int|null $clearAt When the message should be cleared
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSBadRequestException The clearAt or icon is invalid or the message is too long
	 *
	 * 200: The message was updated successfully
	 */
	public function setCustomMessage(?string $statusIcon,
		?string $message,
		?int $clearAt): DataResponse {
		try {
			if (($message !== null && $message !== '') || ($clearAt !== null && $clearAt !== 0)) {
				$status = $this->service->setCustomMessage($this->userId, $statusIcon, $message, $clearAt);
			} else {
				$this->service->clearMessage($this->userId);
				$status = $this->service->findByUserId($this->userId);
			}
			$this->service->removeBackupUserStatus($this->userId);
			return new DataResponse($this->formatStatus($status));
		} catch (InvalidClearAtException $ex) {
			$this->logger->debug('New user-status for "' . $this->userId . '" was rejected due to an invalid clearAt value "' . $clearAt . '"');
			throw new OCSBadRequestException($ex->getMessage(), $ex);
		} catch (InvalidStatusIconException $ex) {
			$this->logger->debug('New user-status for "' . $this->userId . '" was rejected due to an invalid icon value "' . $statusIcon . '"');
			throw new OCSBadRequestException($ex->getMessage(), $ex);
		} catch (StatusMessageTooLongException $ex) {
			$this->logger->debug('New user-status for "' . $this->userId . '" was rejected due to a too long status message.');
			throw new OCSBadRequestException($ex->getMessage(), $ex);
		}
	}

	/**
	 * Clear the message of the current user
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 *
	 * 200: Message cleared successfully
	 */
	public function clearMessage(): DataResponse {
		$this->service->clearMessage($this->userId);
		return new DataResponse([]);
	}

	/**
	 * Revert the status to the previous status
	 *
	 * @NoAdminRequired
	 *
	 * @param string $messageId ID of the message to delete
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate|array<empty>, array{}>
	 *
	 * 200: Status reverted
	 */
	public function revertStatus(string $messageId): DataResponse {
		$backupStatus = $this->service->revertUserStatus($this->userId, $messageId, true);
		if ($backupStatus) {
			return new DataResponse($this->formatStatus($backupStatus));
		}
		return new DataResponse([]);
	}

	/**
	 * @param UserStatus $status
	 * @return UserStatusPrivate
	 */
	private function formatStatus(UserStatus $status): array {
		/** @var UserStatusType $visibleStatus */
		$visibleStatus = $status->getStatus();
		return [
			'userId' => $status->getUserId(),
			'message' => $status->getCustomMessage(),
			'messageId' => $status->getMessageId(),
			'messageIsPredefined' => $status->getMessageId() !== null,
			'icon' => $status->getCustomIcon(),
			'clearAt' => $status->getClearAt(),
			'status' => $visibleStatus,
			'statusIsUserDefined' => $status->getIsUserDefined(),
		];
	}
}
