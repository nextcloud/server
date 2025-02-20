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
		private string $objectType,
		private string $objectId,
	) {
	}

	public static function default(): self {
		return new self(false, '', '');
	}

	public function setPublic(bool $isPublic = true): IConversationOptions {
		$this->isPublic = $isPublic;
		return $this;
	}

	public function isPublic(): bool {
		return $this->isPublic;
	}

	public function getObjectType(): string {
		return $this->objectType;
	}

	public function getObjectId(): string {
		return $this->objectId;
	}

	public function setObject(string $objectType, string $objectId): self {
		$this->objectType = $objectType;
		$this->objectId = $objectId;
		return $this;
	}
}
