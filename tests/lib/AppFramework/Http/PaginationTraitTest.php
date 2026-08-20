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
	private IRequest&\PHPUnit\Framework\MockObject\MockObject $request;
	private IURLGenerator&\PHPUnit\Framework\MockObject\MockObject $urlGenerator;
	private object $subject;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->subject = new class($this->request, $this->urlGenerator) {
			use PaginationTrait;

			public function __construct(IRequest $request, IURLGenerator $urlGenerator) {
				$this->request = $request;
				$this->urlGenerator = $urlGenerator;
			}

			public function hasMore(array $items, ?int $limit): bool {
				return $this->hasMoreResults($items, $limit);
			}

			public function nextLink(array $params, int $limit, int $offset): string {
				return $this->buildOffsetNextPageLinkHeader($params, $limit, $offset);
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
		$this->request->method('getRequestUri')->willReturn('/ocs/v2.php/apps/provisioning_api/api/v1/groups?search=foo&limit=5&offset=0');

		$this->urlGenerator->method('getAbsoluteURL')
			->with('/ocs/v2.php/apps/provisioning_api/api/v1/groups')
			->willReturn('https://cloud.example.com/ocs/v2.php/apps/provisioning_api/api/v1/groups');

		$link = $this->subject->nextLink(['search' => 'foo'], 5, 0);

		$this->assertSame(
			'<https://cloud.example.com/ocs/v2.php/apps/provisioning_api/api/v1/groups?search=foo&limit=5&offset=5>; rel="next"',
			$link
		);
	}
}
