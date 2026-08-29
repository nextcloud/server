<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Optional capability of a share-review source: serialize a share before it
 * is deleted and re-create it from that snapshot later, so a share-review app
 * can offer a recycle bin for revoked shares.
 *
 * The snapshot is an opaque, app-owned and app-versioned string — the
 * share-review app stores it verbatim and never interprets it. An
 * implementation must gate restoreShare() exactly like
 * {@see IShareReviewSource::deleteShare()}: dispatch a
 * {@see \OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent} with action
 * {@see \OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent::ACTION_RESTORE}
 * first and refuse unless access was granted. serializeShare() is read-only
 * and needs no gate.
 *
 * @since 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IShareReviewSourceSnapshot extends IShareReviewSource {
	/**
	 * Serialize the share so it can be re-created after deletion.
	 *
	 * @param string $shareId The deletion identifier of the share, as accepted
	 *                        by {@see IShareReviewSource::deleteShare()}.
	 * @return string|null the opaque snapshot, or null if this share cannot be
	 *                     snapshotted (e.g. it no longer exists)
	 *
	 * @since 36.0.0
	 */
	public function serializeShare(string $shareId): ?string;

	/**
	 * Re-create a share from a snapshot produced by serializeShare(). The
	 * re-created share may receive a new id; a link share may receive a new
	 * token.
	 *
	 * @param string $snapshot An opaque snapshot from serializeShare().
	 * @return bool whether the share was re-created
	 *
	 * @since 36.0.0
	 */
	public function restoreShare(string $snapshot): bool;
}
