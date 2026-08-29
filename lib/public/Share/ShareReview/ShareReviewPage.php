<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;

/**
 * One page of shares as returned by
 * {@see IPaginatedShareReviewSource::queryShares()}, together with the counts
 * of the underlying query so a table draw needs no second call.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class ShareReviewPage {
	/**
	 * @param list<ShareReviewEntry> $entries The shares of this page, in the
	 *                                        requested sort order.
	 * @param ShareReviewCounts $counts Total and filtered counts of the query
	 *                                  that produced this page.
	 *
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly array $entries,
		public readonly ShareReviewCounts $counts,
	) {
	}
}
