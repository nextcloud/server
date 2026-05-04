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
use OCP\Sharing\Recipient\IShareRecipientTypeSearch;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;

class TestShareRecipientType implements IShareRecipientType, IShareRecipientTypeSearch {
	public function __construct(
		/** @var array<string, non-empty-string> $validRecipients */
		private readonly array $validRecipients,
		/** @var list<non-empty-string> $recipients */
		private readonly array $recipients,
		/** @var list<ShareRecipient> $searchRecipients */
		public array $searchRecipients,
	) {
	}

	#[\Override]
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
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
		return $this->recipients;
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		return $this->validRecipients[$recipient];
	}

	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array {
		return array_slice($this->searchRecipients, $offset, $limit);
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return match ($recipient) {
			'svg' => new ShareIconSVG('<svg/>'),
			'url' => new ShareIconURL('https://example.com/light.png', 'https://example.com/dark.png'),
			default => null,
		};
	}
}
