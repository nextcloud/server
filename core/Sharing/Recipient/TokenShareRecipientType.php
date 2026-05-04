<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\IShareRecipientTypePublicSecret;

final class TokenShareRecipientType implements IShareRecipientType, IShareRecipientTypePublicSecret {
	private ?IManager $legacyManager = null;

	private function getLegacyManager(): IManager {
		return $this->legacyManager ??= Server::get(IManager::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Public link');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		if (strlen($recipient) < 32 || strlen($recipient) > 255) {
			return false;
		}

		return $this->getLegacyManager()->shareApiAllowLinks($owner);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
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

	#[\Override]
	public function isSecretPublic(string $recipient): bool {
		return true;
	}

	#[\Override]
	public function isSecretUpdatable(string $recipient): bool {
		return $this->getLegacyManager()->allowCustomTokens();
	}
}
