<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\IShare;
use OCP\Share\ShareReview\ShareReviewQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShareReviewQueryTest extends TestCase {

	public function testDefaults(): void {
		$query = new ShareReviewQuery();

		$this->assertSame(100, $query->limit);
		$this->assertSame(0, $query->offset);
		$this->assertNull($query->search);
		$this->assertSame(ShareReviewQuery::SORT_TIME, $query->sortField);
		$this->assertTrue($query->sortDescending);
		$this->assertNull($query->modifiedSinceTimestamp);
		$this->assertNull($query->shareTypes);
		$this->assertNull($query->hasPassword);
		$this->assertNull($query->hasExpiration);
		$this->assertNull($query->expiresAfterTimestamp);
		$this->assertNull($query->expiresBeforeTimestamp);
		$this->assertNull($query->initiatorSearch);
		$this->assertNull($query->recipientSearch);
		$this->assertNull($query->objectSearch);
		$this->assertNull($query->objectSearchAny);
		$this->assertNull($query->initiatorIds);
		$this->assertNull($query->recipientIds);
		$this->assertNull($query->permissionIds);
		$this->assertNull($query->tokens);
		$this->assertFalse($query->isFiltered());
	}

	public function testHoldsAllFields(): void {
		$query = new ShareReviewQuery(
			limit: 25,
			offset: 50,
			search: 'report',
			sortField: ShareReviewQuery::SORT_OBJECT,
			sortDescending: false,
			modifiedSinceTimestamp: 1783764000,
			shareTypes: [IShare::TYPE_LINK, IShare::TYPE_EMAIL],
			hasPassword: false,
			hasExpiration: true,
			expiresAfterTimestamp: 1785837600,
			expiresBeforeTimestamp: 1786442400,
			initiatorSearch: 'ali',
			recipientSearch: 'bo',
			objectSearch: '.xlsx',
			objectSearchAny: ['salary', '.pem'],
			initiatorIds: ['alice'],
			recipientIds: ['bob', 'developers'],
			permissionIds: ['files:reshare'],
			tokens: ['sToKeN'],
		);

		$this->assertSame(25, $query->limit);
		$this->assertSame(50, $query->offset);
		$this->assertSame('report', $query->search);
		$this->assertSame(ShareReviewQuery::SORT_OBJECT, $query->sortField);
		$this->assertFalse($query->sortDescending);
		$this->assertSame(1783764000, $query->modifiedSinceTimestamp);
		$this->assertSame([IShare::TYPE_LINK, IShare::TYPE_EMAIL], $query->shareTypes);
		$this->assertFalse($query->hasPassword);
		$this->assertTrue($query->hasExpiration);
		$this->assertSame(1785837600, $query->expiresAfterTimestamp);
		$this->assertSame(1786442400, $query->expiresBeforeTimestamp);
		$this->assertSame('ali', $query->initiatorSearch);
		$this->assertSame('bo', $query->recipientSearch);
		$this->assertSame('.xlsx', $query->objectSearch);
		$this->assertSame(['salary', '.pem'], $query->objectSearchAny);
		$this->assertSame(['alice'], $query->initiatorIds);
		$this->assertSame(['bob', 'developers'], $query->recipientIds);
		$this->assertSame(['files:reshare'], $query->permissionIds);
		$this->assertSame(['sToKeN'], $query->tokens);
		$this->assertTrue($query->isFiltered());
	}

	public function testSortFieldsListsEveryConstant(): void {
		$this->assertSame([
			ShareReviewQuery::SORT_TIME,
			ShareReviewQuery::SORT_OBJECT,
			ShareReviewQuery::SORT_INITIATOR,
			ShareReviewQuery::SORT_RECIPIENT,
			ShareReviewQuery::SORT_TYPE,
		], ShareReviewQuery::SORTABLE_FIELDS);
		foreach (ShareReviewQuery::SORTABLE_FIELDS as $sortField) {
			$this->assertSame($sortField, (new ShareReviewQuery(sortField: $sortField))->sortField);
		}
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function singleFilterProvider(): array {
		return [
			'search' => [['search' => 'x']],
			'modifiedSince' => [['modifiedSinceTimestamp' => 1]],
			'shareTypes' => [['shareTypes' => [IShare::TYPE_LINK]]],
			'hasPassword false' => [['hasPassword' => false]],
			'hasExpiration false' => [['hasExpiration' => false]],
			'expiresAfter' => [['expiresAfterTimestamp' => 1]],
			'expiresBefore' => [['expiresBeforeTimestamp' => 1]],
			'initiatorSearch' => [['initiatorSearch' => 'a']],
			'recipientSearch' => [['recipientSearch' => 'b']],
			'objectSearch' => [['objectSearch' => 'c']],
			'objectSearchAny' => [['objectSearchAny' => []]],
			'initiatorIds' => [['initiatorIds' => []]],
			'recipientIds' => [['recipientIds' => []]],
			'permissionIds' => [['permissionIds' => []]],
			'tokens' => [['tokens' => []]],
		];
	}

	/**
	 * @param array<string, mixed> $arguments
	 */
	#[DataProvider('singleFilterProvider')]
	public function testEverySingleFilterCountsAsFiltered(array $arguments): void {
		$this->assertTrue((new ShareReviewQuery(...$arguments))->isFiltered());
	}

	public function testPaginationAndSortDoNotCountAsFiltered(): void {
		$query = new ShareReviewQuery(limit: 1, offset: 999, sortField: ShareReviewQuery::SORT_TYPE, sortDescending: false);

		$this->assertFalse($query->isFiltered());
	}

	public function testLimitBounds(): void {
		$this->assertSame(1, (new ShareReviewQuery(limit: 1))->limit);
		$this->assertSame(ShareReviewQuery::MAX_LIMIT, (new ShareReviewQuery(limit: ShareReviewQuery::MAX_LIMIT))->limit);
	}

	public function testRejectsZeroLimit(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewQuery(limit: 0);
	}

	public function testRejectsLimitAboveMaximum(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewQuery(limit: ShareReviewQuery::MAX_LIMIT + 1);
	}

	public function testRejectsNegativeOffset(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewQuery(offset: -1);
	}

	public function testRejectsUnknownSortField(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewQuery(sortField: 'permissions');
	}
}
