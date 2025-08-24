<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Db;

use DateTime;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use OC\User\OutOfOfficeData;
use OCP\AppFramework\Db\Entity;
use OCP\IUser;
use OCP\User\IOutOfOfficeData;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getFirstDay()
 * @method void setFirstDay(string $firstDay)
 * @method string getLastDay()
 * @method void setLastDay(string $lastDay)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method string getMessage()
 * @method void setMessage(string $message)
 * @method string getReplacementUserId()
 * @method void setReplacementUserId(?string $replacementUserId)
 * @method string getReplacementUserDisplayName()
 * @method void setReplacementUserDisplayName(?string $replacementUserDisplayName)
 */
class Absence extends Entity implements JsonSerializable {
	protected string $userId = '';

	/** Inclusive, formatted as YYYY-MM-DD */
	protected string $firstDay = '';

	/** Inclusive, formatted as YYYY-MM-DD */
	protected string $lastDay = '';

	protected string $status = '';

	protected string $message = '';

	protected ?string $replacementUserId = null;

	protected ?string $replacementUserDisplayName = null;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('firstDay', 'string');
		$this->addType('lastDay', 'string');
		$this->addType('status', 'string');
		$this->addType('message', 'string');
		$this->addType('replacementUserId', 'string');
		$this->addType('replacementUserDisplayName', 'string');
	}

	public function toOutOufOfficeData(IUser $user, string $timezone): IOutOfOfficeData {
		if ($user->getUID() !== $this->getUserId()) {
			throw new InvalidArgumentException("The user doesn't match the user id of this absence! Expected " . $this->getUserId() . ', got ' . $user->getUID());
		}
		if ($this->getId() === null) {
			throw new Exception('Creating out-of-office data without ID');
		}

		$tz = new \DateTimeZone($timezone);
		$startDate = new DateTime($this->getFirstDay(), $tz);
		$endDate = new DateTime($this->getLastDay(), $tz);
		$endDate->setTime(23, 59);
		return new OutOfOfficeData(
			(string)$this->getId(),
			$user,
			$startDate->getTimestamp(),
			$endDate->getTimestamp(),
			$this->getStatus(),
			$this->getMessage(),
			$this->getReplacementUserId(),
			$this->getReplacementUserDisplayName(),
		);
	}

	public function jsonSerialize(): array {
		return [
			'userId' => $this->userId,
			'firstDay' => $this->firstDay,
			'lastDay' => $this->lastDay,
			'status' => $this->status,
			'message' => $this->message,
			'replacementUserId' => $this->replacementUserId,
			'replacementUserDisplayName' => $this->replacementUserDisplayName,
		];
	}
}
