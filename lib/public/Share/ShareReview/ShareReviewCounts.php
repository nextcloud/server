<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The two row counts of a {@see IPaginatedShareReviewSource} for one
 * {@see ShareReviewQuery}: the total ignores the query's search and filters,
 * the filtered count applies them all. They map onto the recordsTotal and
 * recordsFiltered values of common server-side table protocols.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class ShareReviewCounts {
	/**
	 * @since 36.0.0
	 */
	public readonly int $filteredCount;

	/**
	 * @param int $totalCount Number of shares the source exposes, ignoring the
	 *                        query's search and filters (but honoring any
	 *                        exclusion the source applies always).
	 * @param int $filteredCount Number of shares after applying the query's
	 *                           search and all filters — a subset of the
	 *                           total, never larger. A larger value is clamped
	 *                           to the total: the two counts usually come from
	 *                           independent queries (or a cache), so a
	 *                           concurrent insert can briefly push the live
	 *                           filtered count past a stale total.
	 *
	 * @throws \InvalidArgumentException on a negative count
	 *
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly int $totalCount,
		int $filteredCount,
	) {
		if ($totalCount < 0 || $filteredCount < 0) {
			throw new \InvalidArgumentException('Counts must not be negative');
		}
		$this->filteredCount = min($filteredCount, $totalCount);
	}
}
