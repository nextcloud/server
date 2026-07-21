<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Recipient;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Interaction\InteractionReceiver;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;

/**
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IShareRecipientType {
	/**
	 * Returns a user friendly display name for this recipient type.
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Validate that a recipient exists.
	 *
	 * Any check if the recipient is allowed to be shared to, must be implemented through {@see RestrictInteractionEvent}.
	 *
	 * @param non-empty-string $recipient
	 * @since 35.0.0
	 */
	public function validateRecipient(string $recipient): bool;

	/**
	 * Get possible recipient values for the current user.
	 *
	 * @return list<string>
	 * @since 35.0.0
	 */
	public function getRecipients(?IUser $currentUser, mixed $arguments): array;

	/**
	 * @param non-empty-string $recipient
	 * @return ?non-empty-string
	 * @since 35.0.0
	 */
	public function getRecipientDisplayName(string $recipient): ?string;

	/**
	 * @param non-empty-string $recipient
	 * @since 35.0.0
	 */
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL;

	/**
	 * @param non-empty-string $recipient
	 * @since 35.0.0
	 */
	public function getRecipientInteractionReceiver(string $recipient): InteractionReceiver;
}
