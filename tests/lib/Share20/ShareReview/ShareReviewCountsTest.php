<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\ShareReview\ShareReviewCounts;
use PHPUnit\Framework\TestCase;

final class ShareReviewCountsTest extends TestCase {

	public function testHoldsBothValues(): void {
		$counts = new ShareReviewCounts(1200, 37);

		$this->assertSame(1200, $counts->totalCount);
		$this->assertSame(37, $counts->filteredCount);
	}

	public function testZeroCounts(): void {
		$counts = new ShareReviewCounts(0, 0);

		$this->assertSame(0, $counts->totalCount);
		$this->assertSame(0, $counts->filteredCount);
	}

	public function testRejectsNegativeTotal(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewCounts(-1, 0);
	}

	public function testRejectsNegativeFilteredCount(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewCounts(5, -1);
	}

	public function testFilteredCountAboveTheTotalIsClampedToIt(): void {
		// two independent count queries can race a concurrent insert
		$counts = new ShareReviewCounts(100, 250);

		$this->assertSame(100, $counts->filteredCount);
	}
}
