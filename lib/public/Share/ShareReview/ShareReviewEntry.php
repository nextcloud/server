<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Holds a single app-managed share as exposed to a share-review app through
 * {@see IShareReviewSource::getShares()}.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class ShareReviewEntry {
	/**
	 * @param string $id Unique app-specific identifier for the share, passed
	 *                   to {@see IShareReviewSource::deleteShare()}.
	 * @param string $object Name or title of the shared object, such as a
	 *                       file path or report name.
	 * @param string $initiator User ID of the initiator.
	 * @param int $type {@see \OCP\Share\IShare} type of the share.
	 * @param string $recipient User ID of the owner or the token of a link.
	 * @param int $permissions Permissions level of the share.
	 * @param string $time Creation time of the share.
	 * @param string $action Optional deletion identifier override. An empty
	 *                       string means $id is used.
	 * @param int|null $timestamp Optional creation Unix timestamp, used for sorting.
	 * @param bool $password Whether the share is password protected. Never
	 *                       the password itself.
	 * @param string|null $expiration Optional expiration date displayed for the share.
	 * @param string|null $parent Optional identifier of the parent share.
	 *
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $object,
		public readonly string $initiator,
		public readonly int $type,
		public readonly string $recipient,
		public readonly int $permissions = 1,
		public readonly string $time = '1970-01-01 01:00:00',
		public readonly string $action = '',
		public readonly ?int $timestamp = null,
		public readonly bool $password = false,
		public readonly ?string $expiration = null,
		public readonly ?string $parent = null,
	) {
	}
}
