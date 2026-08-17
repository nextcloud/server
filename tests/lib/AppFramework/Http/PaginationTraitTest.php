<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\PaginationTrait;
use OCP\IRequest;
use OCP\IURLGenerator;
use Test\TestCase;

class PaginationTraitTest extends TestCase {
	private object $subject;

	protected function setUp(): void {
		parent::setUp();

		$this->subject = new class {
			use PaginationTrait;

			public function hasMore(array $items, ?int $limit): bool {
				return $this->hasMoreResults($items, $limit);
			}

			public function nextLink(IRequest $request, IURLGenerator $urlGenerator, array $params): string {
				return $this->buildNextPageLinkHeader($request, $urlGenerator, $params);
			}
		};
	}

	public function testNullLimitNeverReportsMore(): void {
		$this->assertFalse($this->subject->hasMore(['a', 'b', 'c'], null));
	}

	public function testZeroLimitNeverReportsMore(): void {
		$this->assertFalse($this->subject->hasMore([], 0));
	}

	public function testFewerResultsThanLimitReportsNoMore(): void {
		$this->assertFalse($this->subject->hasMore(['a', 'b'], 5));
	}

	public function testExactlyLimitResultsReportsMore(): void {
		$this->assertTrue($this->subject->hasMore(['a', 'b', 'c'], 3));
	}

	public function testEmptyResultWithPositiveLimitReportsNoMore(): void {
		$this->assertFalse($this->subject->hasMore([], 5));
	}

	public function testAssociativeArrayIsCountedByEntries(): void {
		$this->assertTrue($this->subject->hasMore(['uid1' => 'Alice', 'uid2' => 'Bob'], 2));
	}

	public function testNextLinkKeepsRequestPathAndAppliesGivenQuery(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getRequestUri')->willReturn('/ocs/v2.php/apps/provisioning_api/api/v1/groups?search=foo&limit=5&offset=0');

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('getAbsoluteURL')
			->with('/ocs/v2.php/apps/provisioning_api/api/v1/groups')
			->willReturn('https://cloud.example.com/ocs/v2.php/apps/provisioning_api/api/v1/groups');

		$link = $this->subject->nextLink($request, $urlGenerator, ['search' => 'foo', 'limit' => 5, 'offset' => 5]);

		$this->assertSame(
			'<https://cloud.example.com/ocs/v2.php/apps/provisioning_api/api/v1/groups?search=foo&limit=5&offset=5>; rel="next"',
			$link
		);
	}
}
