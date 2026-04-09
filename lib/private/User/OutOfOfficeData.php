<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User;

use OCP\IUser;
use OCP\User\IOutOfOfficeData;

class OutOfOfficeData implements IOutOfOfficeData {
	public function __construct(
		private string $id,
		private IUser $user,
		private int $startDate,
		private int $endDate,
		private string $shortMessage,
		private string $message,
		private ?string $replacementUserId,
		private ?string $replacementUserDisplayName,
	) {
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

	public function getReplacementUserId(): ?string {
		return $this->replacementUserId;
	}

	public function getReplacementUserDisplayName(): ?string {
		return $this->replacementUserDisplayName;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'userId' => $this->getUser()->getUID(),
			'startDate' => $this->getStartDate(),
			'endDate' => $this->getEndDate(),
			'shortMessage' => $this->getShortMessage(),
			'message' => $this->getMessage(),
			'replacementUserId' => $this->getReplacementUserId(),
			'replacementUserDisplayName' => $this->getReplacementUserDisplayName(),
		];
	}
}
