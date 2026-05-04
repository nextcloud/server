<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\IShareRecipientTypePublicSecret;

final readonly class TestShareRecipientTypePublicSecret implements IShareRecipientType, IShareRecipientTypePublicSecret {
	public function __construct(
		/** @var array<string, non-empty-string> $validRecipients */
		private array $validRecipients,
		/** @var array<non-empty-string, list<non-empty-string>> $recipientValues */
		private array $recipientValues,
		private bool $isSecretPublic,
		private bool $isSecretUpdatable,
	) {
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', self::class);
		return end($parts);
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		return array_key_exists($recipient, $this->validRecipients);
	}

	/**
	 * @return list<string>
	 */
	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return $this->recipientValues[$currentUser->getUID()] ?? [];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		return $this->validRecipients[$recipient];
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): ShareIconSVG|ShareIconURL {
		return match ($recipient) {
			'url' => new ShareIconURL('https://example.com/light.png', 'https://example.com/dark.png'),
			default => new ShareIconSVG('<svg/>'),
		};
	}

	#[\Override]
	public function isSecretPublic(string $recipient): bool {
		return $this->isSecretPublic;
	}

	#[\Override]
	public function isSecretUpdatable(string $recipient): bool {
		return $this->isSecretUpdatable;
	}
}
