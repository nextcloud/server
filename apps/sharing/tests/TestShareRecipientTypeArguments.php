<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Tests;

use OCP\IUser;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Recipient\IShareRecipientType;

final class TestShareRecipientTypeArguments implements IShareRecipientType {
	#[\Override]
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', self::class);
		return end($parts);
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		return true;
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (is_string($arguments)) {
			return [$arguments];
		}

		return [];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		return null;
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}
}
