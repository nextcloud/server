<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OC\Share\Constants;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Recipient\IShareRecipientType;

// TODO: Redact token when getting shares as non-owner
final class TokenShareRecipientType implements IShareRecipientType {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Public link');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		return preg_match('/^[a-z0-9-]{' . Constants::MIN_TOKEN_LENGTH . ',' . Constants::MAX_TOKEN_LENGTH . '}$/i', $recipient) === 1;
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
