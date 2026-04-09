<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Talk;

use OCP\Talk\IConversationOptions;

class ConversationOptions implements IConversationOptions {
	private function __construct(
		private bool $isPublic,
		private ?\DateTimeInterface $meetingStartDate = null,
		private ?\DateTimeInterface $meetingEndDate = null,
	) {
	}

	public static function default(): self {
		return new self(false);
	}

	public function setPublic(bool $isPublic = true): IConversationOptions {
		$this->isPublic = $isPublic;
		return $this;
	}

	public function isPublic(): bool {
		return $this->isPublic;
	}

	public function setMeetingDate(\DateTimeInterface $meetingStartDate, \DateTimeInterface $meetingEndDate): IConversationOptions {
		$this->meetingStartDate = $meetingStartDate;
		$this->meetingEndDate = $meetingEndDate;
		return $this;
	}

	public function getMeetingStartDate(): ?\DateTimeInterface {
		return $this->meetingStartDate;
	}

	public function getMeetingEndDate(): ?\DateTimeInterface {
		return $this->meetingEndDate;
	}
}
