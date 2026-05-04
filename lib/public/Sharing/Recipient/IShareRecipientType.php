<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Recipient;

use OCP\AppFramework\Attribute\Implementable;
use OCP\IUser;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface IShareRecipientType {
	/**
	 * Returns a user friendly display name for this recipient type.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;

	/**
	 * Validate that a recipient exists.
	 *
	 * @param non-empty-string $recipient
	 */
	public function validateRecipient(IUser $owner, string $recipient): bool;

	/**
	 * Get possible recipient values for the current user.
	 *
	 * @return list<string>
	 */
	// TODO: Add inverse of this method to get users for a recipient
	public function getRecipients(?IUser $currentUser, mixed $arguments): array;

	/**
	 * @param non-empty-string $recipient
	 * @return ?non-empty-string
	 */
	public function getRecipientDisplayName(string $recipient): ?string;

	/**
	 * @param non-empty-string $recipient
	 */
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL;
}
