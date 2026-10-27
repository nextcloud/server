<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Share\IShare;

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
	 * @param IShare::TYPE_* $type {@see \OCP\Share\IShare} type of the share.
	 * @param string $recipient User ID of the owner or the token of a link.
	 * @param int $lastModifiedTimestamp Unix timestamp of the share's creation
	 *                                   or last modification, whichever is
	 *                                   later; used for sorting and for the
	 *                                   new-since-last-review filter. Pass 0
	 *                                   if the app tracks neither.
	 * @param list<ShareReviewPermission> $permissions Permissions granted by
	 *                                                 the share. An empty list
	 *                                                 means the share grants
	 *                                                 nothing beyond existing.
	 * @param string $action Optional deletion identifier override. An empty
	 *                       string means $id is used.
	 * @param bool $hasPassword Whether the share is password protected. Never
	 *                          the password itself.
	 * @param int|null $expirationTimestamp Optional expiration Unix timestamp
	 *                                      of the share.
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
		public readonly int $lastModifiedTimestamp,
		public readonly array $permissions = [],
		public readonly string $action = '',
		public readonly bool $hasPassword = false,
		public readonly ?int $expirationTimestamp = null,
		public readonly ?string $parent = null,
	) {
	}
}
