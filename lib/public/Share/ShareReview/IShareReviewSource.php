<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Interface to be implemented by apps that want to expose their app-managed
 * shares to a share-review app. Implementations are registered through
 * {@see RegisterShareReviewSourceEvent} and resolved from the dependency
 * injection container.
 *
 * Sources with many shares should implement the extending
 * {@see IPaginatedShareReviewSource}, which lists shares page by page with
 * sorting, search, filters and counts instead of everything at once.
 *
 * @since 34.0.2
 */
#[Implementable(since: '34.0.2')]
interface IShareReviewSource {
	/**
	 * Stable, non-translated identifier of this source, e.g. 'Deck'. Used as
	 * the key of the source in the review app (tab id, per-source review
	 * state) and as the source name of
	 * {@see Events\ShareReviewAccessCheckEvent}, so it must never change and
	 * must not be translated. Use
	 * {@see IPaginatedShareReviewSource::getDisplayName()} for a localized
	 * label.
	 *
	 * @since 34.0.2
	 */
	public function getName(): string;

	/**
	 * Return all app-specific shares.
	 *
	 * The app name is added by the share-review app from getName(). A source
	 * implementing {@see IPaginatedShareReviewSource} is queried page by page
	 * through queryShares() instead; it may implement this method by iterating
	 * over all pages.
	 *
	 * @return list<ShareReviewEntry>
	 *
	 * @since 34.0.2
	 */
	public function getShares(): array;

	/**
	 * Delete an app-specific share.
	 *
	 * @since 34.0.2
	 */
	public function deleteShare(string $shareId): bool;
}
