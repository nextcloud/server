<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\User;

use OCP\IUser;
use OCP\User\IOutOfOfficeData;

class OutOfOfficeData implements IOutOfOfficeData {
	public function __construct(private string $id,
		private IUser $user,
		private int $startDate,
		private int $endDate,
		private string $shortMessage,
		private string $message) {
	}

	public function getId(): string {
		return $this->id;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function getStartDate(): int {
		return $this->startDate;
	}

	public function getEndDate(): int {
		return $this->endDate;
	}

	public function getShortMessage(): string {
		return $this->shortMessage;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'userId' => $this->getUser()->getUID(),
			'startDate' => $this->getStartDate(),
			'endDate' => $this->getEndDate(),
			'shortMessage' => $this->getShortMessage(),
			'message' => $this->getMessage(),
		];
	}
}
