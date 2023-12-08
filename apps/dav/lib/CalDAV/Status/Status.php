<?php

declare(strict_types=1);

/**
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *
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

namespace OCA\DAV\CalDAV\Status;

class Status {
	public function __construct(private string $status = '', private ?string $message = null, private ?string $customMessage = null, private ?int $timestamp = null, private ?string $customEmoji = null) {
	}

	public function getStatus(): string {
		return $this->status;
	}

	public function setStatus(string $status): void {
		$this->status = $status;
	}

	public function getMessage(): ?string {
		return $this->message;
	}

	public function setMessage(?string $message): void {
		$this->message = $message;
	}

	public function getCustomMessage(): ?string {
		return $this->customMessage;
	}

	public function setCustomMessage(?string $customMessage): void {
		$this->customMessage = $customMessage;
	}

	public function setEndTime(?int $timestamp): void {
		$this->timestamp = $timestamp;
	}

	public function getEndTime(): ?int {
		return $this->timestamp;
	}

	public function getCustomEmoji(): ?string {
		return $this->customEmoji;
	}

	public function setCustomEmoji(?string $emoji): void {
		$this->customEmoji = $emoji;
	}
}
