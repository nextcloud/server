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

	#[\Override]
	public function getId(): string {
		return $this->id;
	}

	#[\Override]
	public function getUser(): IUser {
		return $this->user;
	}

	#[\Override]
	public function getStartDate(): int {
		return $this->startDate;
	}

	#[\Override]
	public function getEndDate(): int {
		return $this->endDate;
	}

	#[\Override]
	public function getShortMessage(): string {
		return $this->shortMessage;
	}

	#[\Override]
	public function getMessage(): string {
		return $this->message;
	}

	#[\Override]
	public function getReplacementUserId(): ?string {
		return $this->replacementUserId;
	}

	#[\Override]
	public function getReplacementUserDisplayName(): ?string {
		return $this->replacementUserDisplayName;
	}

	#[\Override]
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
