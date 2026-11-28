<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Search;

use OC\Search\FilterCollection;
use OC\Search\SearchQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class SearchQueryTest extends TestCase {
	public function testEmptyStringCursorIsNormalizedToNull(): void {
		$query = new SearchQuery(new FilterCollection(), cursor: '');
		$this->assertNull($query->getCursor());
	}

	public static function cursorProvider(): array {
		return [
			'null' => [null],
			'zero int' => [0],
			'int' => [5],
			'numeric string' => ['10'],
			'opaque string' => ['cursor-abc'],
		];
	}

	#[DataProvider('cursorProvider')]
	public function testCursorIsPassedThrough(int|string|null $cursor): void {
		$query = new SearchQuery(new FilterCollection(), cursor: $cursor);
		$this->assertSame($cursor, $query->getCursor());
	}
}
