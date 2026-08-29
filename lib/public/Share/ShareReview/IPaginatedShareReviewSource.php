<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Share\IShare;

/**
 * A share-review source that can list its shares page by page, with sorting,
 * search, filters and counts evaluated by the app itself — in SQL, so that
 * instances with very many shares stay usable.
 *
 * Extends {@see IShareReviewSource}, which listed everything at once; a
 * share-review app discovers this capability through `instanceof` and falls
 * back to {@see IShareReviewSource::getShares()} for sources that do not
 * implement it yet. Implementations register exactly like before, through
 * {@see RegisterShareReviewSourceEvent}.
 *
 * Contract for all query methods (see {@see ShareReviewQuery} for the full
 * field-by-field semantics):
 *  - Display enrichment (display names, path resolution) must only ever run
 *    for the entries a page actually returns, never for counted or filtered
 *    rows.
 *  - The sort and pagination fields of the query are ignored by the count
 *    methods; all search and filter fields apply to every method.
 *  - Sort fields resolve through a fixed whitelist, substring inputs are
 *    escaped for LIKE, and every value is bound as a query parameter.
 *
 * @since 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IPaginatedShareReviewSource extends IShareReviewSource {
	/**
	 * Localized label of this source, e.g. for a tab title. Unlike
	 * {@see IShareReviewSource::getName()}, which must stay a stable,
	 * non-translated id, this may be translated.
	 *
	 * @since 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * Return one page of shares matching the query, with the counts of the
	 * whole query.
	 *
	 * @since 36.0.0
	 */
	public function queryShares(ShareReviewQuery $query): ShareReviewPage;

	/**
	 * Return the total and filtered counts for the query without fetching any
	 * rows. The query's limit, offset, sortField and sortDescending are
	 * ignored.
	 *
	 * @since 36.0.0
	 */
	public function countShares(ShareReviewQuery $query): ShareReviewCounts;

	/**
	 * Return the filtered count per share type in one grouped scan. All
	 * search and filter fields of the query apply (including shareTypes);
	 * limit, offset and sorting are ignored. Types with a count of zero are
	 * omitted.
	 *
	 * @return array<IShare::TYPE_*, int> map of share type to filtered count
	 *
	 * @since 36.0.0
	 */
	public function countSharesByType(ShareReviewQuery $query): array;

	/**
	 * Look up a single share by its deletion identifier — the value of
	 * {@see ShareReviewEntry::$action} if non-empty, else
	 * {@see ShareReviewEntry::$id} — i.e. the same id
	 * {@see IShareReviewSource::deleteShare()} accepts.
	 *
	 * @return ShareReviewEntry|null null if no such share exists
	 *
	 * @since 36.0.0
	 */
	public function getShare(string $shareId): ?ShareReviewEntry;
}
