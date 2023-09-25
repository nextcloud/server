<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\UserStatus\Service;

use OCA\DAV\CalDAV\Status\Status as CalendarStatus;
use OCA\DAV\CalDAV\Status\StatusService as CalendarStatusService;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Exception\InvalidClearAtException;
use OCA\UserStatus\Exception\InvalidMessageIdException;
use OCA\UserStatus\Exception\InvalidStatusIconException;
use OCA\UserStatus\Exception\InvalidStatusTypeException;
use OCA\UserStatus\Exception\StatusMessageTooLongException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ISchedulingInformation;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IEmojiHelper;
use OCP\IUserManager;
use OCP\UserStatus\IUserStatus;
use function in_array;

/**
 * Class StatusService
 *
 * @package OCA\UserStatus\Service
 */
class StatusService {
	private bool $shareeEnumeration;
	private bool $shareeEnumerationInGroupOnly;
	private bool $shareeEnumerationPhone;

	/**
	 * List of priorities ordered by their priority
	 */
	public const PRIORITY_ORDERED_STATUSES = [
		IUserStatus::ONLINE,
		IUserStatus::AWAY,
		IUserStatus::DND,
		IUserStatus::BUSY,
		IUserStatus::INVISIBLE,
		IUserStatus::OFFLINE,
	];

	/**
	 * List of statuses that persist the clear-up
	 * or UserLiveStatusEvents
	 */
	public const PERSISTENT_STATUSES = [
		IUserStatus::AWAY,
		IUserStatus::BUSY,
		IUserStatus::DND,
		IUserStatus::INVISIBLE,
	];

	/** @var int */
	public const INVALIDATE_STATUS_THRESHOLD = 15 /* minutes */ * 60 /* seconds */;

	/** @var int */
	public const MAXIMUM_MESSAGE_LENGTH = 80;

	public function __construct(private UserStatusMapper $mapper,
								private ITimeFactory $timeFactory,
								private PredefinedStatusService $predefinedStatusService,
								private IEmojiHelper $emojiHelper,
								private IConfig $config,
								private IUserManager $userManager,
								private CalendarStatusService $calendarStatusService) {
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$this->shareeEnumerationInGroupOnly = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$this->shareeEnumerationPhone = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return UserStatus[]
	 */
	public function findAll(?int $limit = null, ?int $offset = null): array {
		// Return empty array if user enumeration is disabled or limited to groups
		// TODO: find a solution that scales to get only users from common groups if user enumeration is limited to
		//       groups. See discussion at https://github.com/nextcloud/server/pull/27879#discussion_r729715936
		if (!$this->shareeEnumeration || $this->shareeEnumerationInGroupOnly || $this->shareeEnumerationPhone) {
			return [];
		}

		return array_map(function ($status) {
			return $this->processStatus($status);
		}, $this->mapper->findAll($limit, $offset));
	}

	/**
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findAllRecentStatusChanges(?int $limit = null, ?int $offset = null): array {
		// Return empty array if user enumeration is disabled or limited to groups
		// TODO: find a solution that scales to get only users from common groups if user enumeration is limited to
		//       groups. See discussion at https://github.com/nextcloud/server/pull/27879#discussion_r729715936
		if (!$this->shareeEnumeration || $this->shareeEnumerationInGroupOnly || $this->shareeEnumerationPhone) {
			return [];
		}

		return array_map(function ($status) {
			return $this->processStatus($status);
		}, $this->mapper->findAllRecent($limit, $offset));
	}

	/**
	 * @param string $userId
	 * @return UserStatus
	 * @throws DoesNotExistException
	 */
	public function findByUserId(string $userId): UserStatus {
		$userStatus = $this->mapper->findByUserId($userId);
		// If the status is user-defined and one of the persistent status, we
		// will not override it.
		if ($userStatus->getIsUserDefined() && \in_array($userStatus->getStatus(), StatusService::PERSISTENT_STATUSES, true)) {
			return $this->processStatus($userStatus);
		}

		$calendarStatus = $this->getCalendarStatus($userId);
		// We found no status from the calendar, proceed with the existing status
		if($calendarStatus === null) {
			return $this->processStatus($userStatus);
		}

		// if we have the same status result for the calendar and the current status,
		// and a custom message to boot, we leave the existing status alone
		// as to not overwrite a custom message / emoji
		if($userStatus->getIsUserDefined()
			&& $calendarStatus->getStatus() === $userStatus->getStatus()
			&& !empty($userStatus->getCustomMessage())) {
			return $this->processStatus($userStatus);
		}

		// If the new status is null, there's already an identical status in place
		$newUserStatus = $this->setUserStatus($userId,
			$calendarStatus->getStatus(),
			$calendarStatus->getMessage() ?? IUserStatus::MESSAGE_AVAILABILITY,
			true,
			$calendarStatus->getCustomMessage() ?? '');

		return $newUserStatus === null ? $this->processStatus($userStatus) : $this->processStatus($newUserStatus);
	}

	/**
	 * @param array $userIds
	 * @return UserStatus[]
	 */
	public function findByUserIds(array $userIds):array {
		return array_map(function ($status) {
			return $this->processStatus($status);
		}, $this->mapper->findByUserIds($userIds));
	}

	/**
	 * @param string $userId
	 * @param string $status
	 * @param int|null $statusTimestamp
	 * @param bool $isUserDefined
	 * @return UserStatus
	 * @throws InvalidStatusTypeException
	 */
	public function setStatus(string $userId,
							  string $status,
							  ?int $statusTimestamp,
							  bool $isUserDefined): UserStatus {
		try {
			$userStatus = $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException $ex) {
			$userStatus = new UserStatus();
			$userStatus->setUserId($userId);
		}

		// Check if status-type is valid
		if (!in_array($status, self::PRIORITY_ORDERED_STATUSES, true)) {
			throw new InvalidStatusTypeException('Status-type "' . $status . '" is not supported');
		}



		if ($statusTimestamp === null) {
			$statusTimestamp = $this->timeFactory->getTime();
		}

		$userStatus->setStatus($status);
		$userStatus->setStatusTimestamp($statusTimestamp);
		$userStatus->setIsUserDefined($isUserDefined);
		$userStatus->setIsBackup(false);

		if ($userStatus->getId() === null) {
			return $this->mapper->insert($userStatus);
		}

		return $this->mapper->update($userStatus);
	}

	/**
	 * @param string $userId
	 * @param string $messageId
	 * @param int|null $clearAt
	 * @return UserStatus
	 * @throws InvalidMessageIdException
	 * @throws InvalidClearAtException
	 */
	public function setPredefinedMessage(string $userId,
										 string $messageId,
										 ?int $clearAt): UserStatus {
		try {
			$userStatus = $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException $ex) {
			$userStatus = new UserStatus();
			$userStatus->setUserId($userId);
			$userStatus->setStatus(IUserStatus::OFFLINE);
			$userStatus->setStatusTimestamp(0);
			$userStatus->setIsUserDefined(false);
			$userStatus->setIsBackup(false);
		}

		if (!$this->predefinedStatusService->isValidId($messageId)) {
			throw new InvalidMessageIdException('Message-Id "' . $messageId . '" is not supported');
		}

		// Check that clearAt is in the future
		if ($clearAt !== null && $clearAt < $this->timeFactory->getTime()) {
			throw new InvalidClearAtException('ClearAt is in the past');
		}

		$userStatus->setMessageId($messageId);
		$userStatus->setCustomIcon(null);
		$userStatus->setCustomMessage(null);
		$userStatus->setClearAt($clearAt);
		$userStatus->setStatusMessageTimestamp($this->timeFactory->now()->getTimestamp());

		if ($userStatus->getId() === null) {
			return $this->mapper->insert($userStatus);
		}

		return $this->mapper->update($userStatus);
	}

	/**
	 * @param string $userId
	 * @param string $status
	 * @param string $messageId
	 * @param bool $createBackup
	 * @throws InvalidStatusTypeException
	 * @throws InvalidMessageIdException
	 */
	public function setUserStatus(string $userId,
									string $status,
									string $messageId,
								 	bool $createBackup,
									string $customMessage = null): ?UserStatus {
		// Check if status-type is valid
		if (!in_array($status, self::PRIORITY_ORDERED_STATUSES, true)) {
			throw new InvalidStatusTypeException('Status-type "' . $status . '" is not supported');
		}

		if (!$this->predefinedStatusService->isValidId($messageId)) {
			throw new InvalidMessageIdException('Message-Id "' . $messageId . '" is not supported');
		}

		if ($createBackup) {
			if ($this->backupCurrentStatus($userId) === false) {
				return null; // Already a status set automatically => abort.
			}

			// If we just created the backup
			$userStatus = new UserStatus();
			$userStatus->setUserId($userId);
		} else {
			try {
				$userStatus = $this->mapper->findByUserId($userId);
			} catch (DoesNotExistException $ex) {
				$userStatus = new UserStatus();
				$userStatus->setUserId($userId);
			}
		}

		$userStatus->setStatus($status);
		$userStatus->setStatusTimestamp($this->timeFactory->getTime());
		$userStatus->setIsUserDefined(true);
		$userStatus->setIsBackup(false);
		$userStatus->setMessageId($messageId);
		$userStatus->setCustomIcon(null);
		$userStatus->setCustomMessage($customMessage);
		$userStatus->setClearAt(null);
		$userStatus->setStatusMessageTimestamp($this->timeFactory->now()->getTimestamp());

		if ($userStatus->getId() !== null) {
			return $this->mapper->update($userStatus);
		}
		return $this->mapper->insert($userStatus);
	}

	/**
	 * @param string $userId
	 * @param string|null $statusIcon
	 * @param string|null $message
	 * @param int|null $clearAt
	 * @return UserStatus
	 * @throws InvalidClearAtException
	 * @throws InvalidStatusIconException
	 * @throws StatusMessageTooLongException
	 */
	public function setCustomMessage(string $userId,
									 ?string $statusIcon,
									 ?string $message,
									 ?int $clearAt): UserStatus {
		try {
			$userStatus = $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException $ex) {
			$userStatus = new UserStatus();
			$userStatus->setUserId($userId);
			$userStatus->setStatus(IUserStatus::OFFLINE);
			$userStatus->setStatusTimestamp(0);
			$userStatus->setIsUserDefined(false);
		}

		// Check if statusIcon contains only one character
		if ($statusIcon !== null && !$this->emojiHelper->isValidSingleEmoji($statusIcon)) {
			throw new InvalidStatusIconException('Status-Icon is longer than one character');
		}
		// Check for maximum length of custom message
		if ($message !== null && \mb_strlen($message) > self::MAXIMUM_MESSAGE_LENGTH) {
			throw new StatusMessageTooLongException('Message is longer than supported length of ' . self::MAXIMUM_MESSAGE_LENGTH . ' characters');
		}
		// Check that clearAt is in the future
		if ($clearAt !== null && $clearAt < $this->timeFactory->getTime()) {
			throw new InvalidClearAtException('ClearAt is in the past');
		}

		$userStatus->setMessageId(null);
		$userStatus->setCustomIcon($statusIcon);
		$userStatus->setCustomMessage($message);
		$userStatus->setClearAt($clearAt);
		$userStatus->setStatusMessageTimestamp($this->timeFactory->now()->getTimestamp());

		if ($userStatus->getId() === null) {
			return $this->mapper->insert($userStatus);
		}

		return $this->mapper->update($userStatus);
	}

	/**
	 * @param string $userId
	 * @return bool
	 */
	public function clearStatus(string $userId): bool {
		try {
			$userStatus = $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException $ex) {
			// if there is no status to remove, just return
			return false;
		}

		$userStatus->setStatus(IUserStatus::OFFLINE);
		$userStatus->setStatusTimestamp(0);
		$userStatus->setIsUserDefined(false);

		$this->mapper->update($userStatus);
		return true;
	}

	/**
	 * @param string $userId
	 * @return bool
	 */
	public function clearMessage(string $userId): bool {
		try {
			$userStatus = $this->mapper->findByUserId($userId);
		} catch (DoesNotExistException $ex) {
			// if there is no status to remove, just return
			return false;
		}

		$userStatus->setMessageId(null);
		$userStatus->setCustomMessage(null);
		$userStatus->setCustomIcon(null);
		$userStatus->setClearAt(null);
		$userStatus->setStatusMessageTimestamp(0);

		$this->mapper->update($userStatus);
		return true;
	}

	/**
	 * @param string $userId
	 * @return bool
	 */
	public function removeUserStatus(string $userId): bool {
		try {
			$userStatus = $this->mapper->findByUserId($userId, false);
		} catch (DoesNotExistException $ex) {
			// if there is no status to remove, just return
			return false;
		}

		$this->mapper->delete($userStatus);
		return true;
	}

	public function removeBackupUserStatus(string $userId): bool {
		try {
			$userStatus = $this->mapper->findByUserId($userId, true);
		} catch (DoesNotExistException $ex) {
			// if there is no status to remove, just return
			return false;
		}

		$this->mapper->delete($userStatus);
		return true;
	}

	/**
	 * Processes a status to check if custom message is still
	 * up to date and provides translated default status if needed
	 *
	 * @param UserStatus $status
	 * @return UserStatus
	 */
	private function processStatus(UserStatus $status): UserStatus {
		$clearAt = $status->getClearAt();

		if ($status->getStatusTimestamp() < $this->timeFactory->getTime() - self::INVALIDATE_STATUS_THRESHOLD
			&& (!$status->getIsUserDefined() || $status->getStatus() === IUserStatus::ONLINE)) {
			$this->cleanStatus($status);
		}
		if ($clearAt !== null && $clearAt < $this->timeFactory->getTime()) {
			$this->cleanStatus($status);
			$this->cleanStatusMessage($status);
		}
		if ($status->getMessageId() !== null) {
			$this->addDefaultMessage($status);
		}

		return $status;
	}

	/**
	 * @param UserStatus $status
	 */
	private function cleanStatus(UserStatus $status): void {
		if ($status->getStatus() === IUserStatus::OFFLINE && !$status->getIsUserDefined()) {
			return;
		}

		$status->setStatus(IUserStatus::OFFLINE);
		$status->setStatusTimestamp($this->timeFactory->getTime());
		$status->setIsUserDefined(false);

		$this->mapper->update($status);
	}

	/**
	 * @param UserStatus $status
	 */
	private function cleanStatusMessage(UserStatus $status): void {
		$status->setMessageId(null);
		$status->setCustomIcon(null);
		$status->setCustomMessage(null);
		$status->setClearAt(null);
		$status->setStatusMessageTimestamp(0);

		$this->mapper->update($status);
	}

	/**
	 * @param UserStatus $status
	 */
	private function addDefaultMessage(UserStatus $status): void {
		// If the message is predefined, insert the translated message and icon
		$predefinedMessage = $this->predefinedStatusService->getDefaultStatusById($status->getMessageId());
		if ($predefinedMessage !== null) {
			$status->setCustomMessage($predefinedMessage['message']);
			$status->setCustomIcon($predefinedMessage['icon']);
			$status->setStatusMessageTimestamp($this->timeFactory->now()->getTimestamp());
		}
	}

	/**
	 * @return bool false if there is already a backup. In this case abort the procedure.
	 */
	public function backupCurrentStatus(string $userId): bool {
		try {
			$this->mapper->createBackupStatus($userId);
			return true;
		} catch (Exception $ex) {
			if ($ex->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				return false;
			}
			throw $ex;
		}
	}

	public function revertUserStatus(string $userId, string $messageId, bool $revertedManually = false): ?UserStatus {
		try {
			/** @var UserStatus $userStatus */
			$backupUserStatus = $this->mapper->findByUserId($userId, true);
		} catch (DoesNotExistException $ex) {
			// No user status to revert, do nothing
			return null;
		}

		$deleted = $this->mapper->deleteCurrentStatusToRestoreBackup($userId, $messageId);
		if (!$deleted) {
			// Another status is set automatically or no status, do nothing
			return null;
		}

		if ($revertedManually && $backupUserStatus->getStatus() === IUserStatus::OFFLINE) {
			// When the user reverts the status manually they are online
			$backupUserStatus->setStatus(IUserStatus::ONLINE);
		}

		$backupUserStatus->setIsBackup(false);
		// Remove the underscore prefix added when creating the backup
		$backupUserStatus->setUserId(substr($backupUserStatus->getUserId(), 1));
		$this->mapper->update($backupUserStatus);

		return $backupUserStatus;
	}

	public function revertMultipleUserStatus(array $userIds, string $messageId): void {
		// Get all user statuses and the backups
		$findById = $userIds;
		foreach ($userIds as $userId) {
			$findById[] = '_' . $userId;
		}
		$userStatuses = $this->mapper->findByUserIds($findById);

		$backups = $restoreIds = $statuesToDelete = [];
		foreach ($userStatuses as $userStatus) {
			if (!$userStatus->getIsBackup()
				&& $userStatus->getMessageId() === $messageId) {
				$statuesToDelete[$userStatus->getUserId()] = $userStatus->getId();
			} else if ($userStatus->getIsBackup()) {
				$backups[$userStatus->getUserId()] = $userStatus->getId();
			}
		}

		// For users with both (normal and backup) delete the status when matching
		foreach ($statuesToDelete as $userId => $statusId) {
			$backupUserId = '_' . $userId;
			if (isset($backups[$backupUserId])) {
				$restoreIds[] = $backups[$backupUserId];
			}
		}

		$this->mapper->deleteByIds(array_values($statuesToDelete));

		// For users that matched restore the previous status
		$this->mapper->restoreBackupStatuses($restoreIds);
	}

	/**
	 * Calculate a users' status according to their availabilit settings and their calendar
	 * events
	 *
	 * There are 4 predefined types of FBTYPE - 'FREE', 'BUSY', 'BUSY-UNAVAILABLE', 'BUSY-TENTATIVE',
	 * but 'X-' properties are possible
	 *
	 * @link https://icalendar.org/iCalendar-RFC-5545/3-2-9-free-busy-time-type.html
	 *
	 * The status will be changed for types
	 *  - 'BUSY'
	 *  - 'BUSY-UNAVAILABLE' (ex.: when a VAVILABILITY setting is in effect)
	 *  - 'BUSY-TENTATIVE' (ex.: an event has been accepted tentatively)
	 * and all FREEBUSY components without a type (implicitly a 'BUSY' status)
	 *
	 * 'X-' properties are not handled for now
	 *
	 * @param string $userId
	 * @return CalendarStatus|null
	 */
	public function getCalendarStatus(string $userId): ?CalendarStatus {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return null;
		}

		$availability = $this->mapper->getAvailabilityFromPropertiesTable($userId);

		return $this->calendarStatusService->processCalendarAvailability($user, $availability);
	}
}
