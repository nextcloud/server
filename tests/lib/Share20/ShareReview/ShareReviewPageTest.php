<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\IShare;
use OCP\Share\ShareReview\ShareReviewCounts;
use OCP\Share\ShareReview\ShareReviewEntry;
use OCP\Share\ShareReview\ShareReviewPage;
use PHPUnit\Framework\TestCase;

final class ShareReviewPageTest extends TestCase {

	public function testCountsHoldBothValues(): void {
		$counts = new ShareReviewCounts(1200, 37);

		$this->assertSame(1200, $counts->totalCount);
		$this->assertSame(37, $counts->filteredCount);
	}

	public function testNegativeCountsAreRejected(): void {
		$this->expectException(\InvalidArgumentException::class);
		new ShareReviewCounts(-1, 0);
	}

	public function testFilteredCountAboveTheTotalIsClampedToIt(): void {
		// two independent count queries can race a concurrent insert
		$counts = new ShareReviewCounts(100, 250);

		$this->assertSame(100, $counts->filteredCount);
	}

	public function testPageComposesEntriesAndCounts(): void {
		$entries = [
			new ShareReviewEntry('1', '/a.txt', 'alice', IShare::TYPE_USER, 'bob', 10),
			new ShareReviewEntry('2', '/b.txt', 'alice', IShare::TYPE_LINK, 'sToKeN', 20),
		];
		$counts = new ShareReviewCounts(2, 2);

		$page = new ShareReviewPage($entries, $counts);

		$this->assertSame($entries, $page->entries);
		$this->assertSame($counts, $page->counts);
	}

	public function testEmptyPage(): void {
		$page = new ShareReviewPage([], new ShareReviewCounts(0, 0));

		$this->assertSame([], $page->entries);
		$this->assertSame(0, $page->counts->totalCount);
		$this->assertSame(0, $page->counts->filteredCount);
	}
}
