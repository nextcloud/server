<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Db;

use DateTime;
use DateTimeZone;
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
 */
class Absence extends Entity implements JsonSerializable {
	protected string $userId = '';

	/** Inclusive, formatted as YYYY-MM-DD */
	protected string $firstDay = '';

	/** Inclusive, formatted as YYYY-MM-DD */
	protected string $lastDay = '';

	protected string $status = '';
	protected string $message = '';

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('firstDay', 'string');
		$this->addType('lastDay', 'string');
		$this->addType('status', 'string');
		$this->addType('message', 'string');
	}

	public function toOutOufOfficeData(IUser $user, string $timezone): IOutOfOfficeData {
		if ($user->getUID() !== $this->getUserId()) {
			throw new InvalidArgumentException("The user doesn't match the user id of this absence! Expected " . $this->getUserId() . ", got " . $user->getUID());
		}
		if ($this->getId() === null) {
			throw new Exception('Creating out-of-office data without ID');
		}

		$tz = new DateTimeZone($timezone);
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
		);
	}

	public function jsonSerialize(): array {
		return [
			'userId' => $this->userId,
			'firstDay' => $this->firstDay,
			'lastDay' => $this->lastDay,
			'status' => $this->status,
			'message' => $this->message,
		];
	}
}
