<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use NCU\Sharing\Icon\ShareIconSVG;
use NCU\Sharing\Icon\ShareIconURL;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\IShareRecipientTypeSearch;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\ShareAccessContext;
use OCP\Interaction\InteractionReceiver;
use OCP\IUser;
use OCP\L10N\IFactory;

class TestShareRecipientType1 implements IShareRecipientType, IShareRecipientTypeSearch {
	public function __construct(
		/** @var array<string, non-empty-string> $validRecipients */
		private readonly array $validRecipients,
		/** @var array<non-empty-string, list<non-empty-string>> $recipientValues */
		private readonly array $recipientValues,
		/** @var list<ShareRecipient> $searchRecipients */
		public array $searchRecipients,
	) {
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function validateRecipient(string $recipient): bool {
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
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return match ($recipient) {
			'url' => new ShareIconURL('https://example.com/light.png', 'https://example.com/dark.png'),
			default => new ShareIconSVG('<svg/>'),
		};
	}

	#[\Override]
	public function getRecipientInteractionReceiver(string $recipient): InteractionReceiver {
		return new TestInteractionReceiver($recipient);
	}

	#[\Override]
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array {
		return array_slice($this->searchRecipients, $offset, $limit);
	}
}
