<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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
		private ?string $userId,
		private LoggerInterface $logger,
		private StatusService $service,
		private CalendarStatusService $calendarStatusService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the status of the current user
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSNotFoundException The user was not found
	 *
	 * 200: The status was found successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/user_status')]
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
	 * @param string $statusType The new status type
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSBadRequestException The status type is invalid
	 *
	 * 200: The status was updated successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/user_status/status')]
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
	 * @param string $messageId ID of the predefined message
	 * @param int|null $clearAt When the message should be cleared
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSBadRequestException The clearAt or message-id is invalid
	 *
	 * 200: The message was updated successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/user_status/message/predefined')]
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
	 * @param string|null $statusIcon Icon of the status
	 * @param string|null $message Message of the status
	 * @param int|null $clearAt When the message should be cleared
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate, array{}>
	 * @throws OCSBadRequestException The clearAt or icon is invalid or the message is too long
	 * @throws OCSNotFoundException No status for the current user
	 *
	 * 200: The message was updated successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/user_status/message/custom')]
	public function setCustomMessage(?string $statusIcon,
		?string $message,
		?int $clearAt): DataResponse {
		try {
			if (($statusIcon !== null && $statusIcon !== '') || ($message !== null && $message !== '') || ($clearAt !== null && $clearAt !== 0)) {
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
		} catch (DoesNotExistException $ex) {
			throw new OCSNotFoundException('No status for the current user');
		}
	}

	/**
	 * Clear the message of the current user
	 *
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 *
	 * 200: Message cleared successfully
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/user_status/message')]
	public function clearMessage(): DataResponse {
		$this->service->clearMessage($this->userId);
		return new DataResponse([]);
	}

	/**
	 * Revert the status to the previous status
	 *
	 * @param string $messageId ID of the message to delete
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPrivate|list<empty>, array{}>
	 *
	 * 200: Status reverted
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/user_status/revert/{messageId}')]
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
