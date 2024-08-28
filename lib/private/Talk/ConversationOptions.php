<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Talk;

use OCP\Talk\IConversationOptions;

class ConversationOptions implements IConversationOptions {
	private bool $isPublic;

	private function __construct(bool $isPublic) {
		$this->isPublic = $isPublic;
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
}
