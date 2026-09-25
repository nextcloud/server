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
 *  - Grouped counts ({@see countSharesByType()}, {@see countSharesByInitiator()})
 *    are single GROUP BY scans over the filtered rows, never a count per group
 *    issued in a loop.
 *  - Display enrichment (display names, path resolution) must only ever run
 *    for the entries a page actually returns, never for counted or filtered
 *    rows.
 *  - The sort and pagination fields of the query are ignored by the count
 *    methods; all search and filter fields apply to every method.
 *  - Sort fields resolve through a fixed whitelist, substring inputs are
 *    escaped for LIKE, and every value is bound as a query parameter.
 *
 * @since 35.0.1
 */
#[Implementable(since: '35.0.1')]
interface IPaginatedShareReviewSource extends IShareReviewSource {
	/**
	 * Localized label of this source, e.g. for a tab title. Unlike
	 * {@see IShareReviewSource::getName()}, which must stay a stable,
	 * non-translated id, this may be translated. It is declared here because
	 * adding a method to the released {@see IShareReviewSource} would break its
	 * existing implementations.
	 *
	 * @since 35.0.1
	 */
	public function getDisplayName(): string;

	/**
	 * Return one page of shares matching the query, with the counts of the
	 * whole query.
	 *
	 * @since 35.0.1
	 */
	public function queryShares(ShareReviewQuery $query): ShareReviewPage;

	/**
	 * Return the total and filtered counts for the query without fetching any
	 * rows. The query's limit, offset, sortField and sortDescending are
	 * ignored.
	 *
	 * @since 35.0.1
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
	 * @since 35.0.1
	 */
	public function countSharesByType(ShareReviewQuery $query): array;

	/**
	 * Return the filtered count per initiator for the initiators with the most
	 * shares, in one grouped scan. All search and filter fields of the query
	 * apply; limit, offset and sorting are ignored. The result is ordered by
	 * count descending, then by initiator id ascending, and holds at most
	 * $limit entries; initiators without a matching share are omitted.
	 *
	 * Serves aggregate views such as "top sharers" and the discovery of
	 * initiators that no longer exist as users, which a consumer cannot derive
	 * from paging without reading every share.
	 *
	 * @param int $limit Maximum number of initiators returned,
	 *                   1..{@see ShareReviewQuery::MAX_LIMIT}.
	 * @return array<string, int> map of initiator id to filtered count
	 *
	 * @throws \InvalidArgumentException on a $limit outside 1..MAX_LIMIT
	 *
	 * @since 35.0.1
	 */
	public function countSharesByInitiator(ShareReviewQuery $query, int $limit): array;

	/**
	 * Look up a single share by its deletion identifier — the value of
	 * {@see ShareReviewEntry::$action} if non-empty, else
	 * {@see ShareReviewEntry::$id} — i.e. the same id
	 * {@see IShareReviewSource::deleteShare()} accepts.
	 *
	 * @return ShareReviewEntry|null null if no such share exists
	 *
	 * @since 35.0.1
	 */
	public function getShare(string $shareId): ?ShareReviewEntry;

	/**
	 * Delete a share, forwarding the action context into the access check.
	 * Identical to {@see IShareReviewSource::deleteShare()} except that the
	 * {@see \OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent} an
	 * implementation dispatches MUST carry $context->actingUserId and
	 * $context->scope. A null context means the session user acts as an
	 * operator, exactly like the 34.0.2 method.
	 *
	 * @param string $shareId The deletion identifier, as accepted by
	 *                        {@see IShareReviewSource::deleteShare()}.
	 * @param ShareReviewActionContext|null $context Who acts, and in which
	 *                                               scope.
	 * @return bool whether the share was deleted
	 *
	 * @since 35.0.1
	 */
	#[\Override]
	public function deleteShare(string $shareId, ?ShareReviewActionContext $context = null): bool;
}
