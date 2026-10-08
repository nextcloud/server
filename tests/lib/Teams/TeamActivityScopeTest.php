<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\Teams\TeamActivityScope;
use Test\TestCase;

class TeamActivityScopeTest extends TestCase {
	public function testGettersReturnConstructorValues(): void {
		$scope = new TeamActivityScope('files', [1, 2, 3]);

		$this->assertSame('files', $scope->getObjectType());
		$this->assertSame([1, 2, 3], $scope->getObjectIds());
	}

	public function testDeduplicatesObjectIds(): void {
		$scope = new TeamActivityScope('files', [1, 2, 2, 3, 1]);

		$this->assertSame([1, 2, 3], $scope->getObjectIds());
	}

	public function testRejectsEmptyObjectType(): void {
		$this->expectException(\InvalidArgumentException::class);

		new TeamActivityScope('', [1]);
	}

	public static function invalidObjectIdProvider(): array {
		return [
			'zero' => [0],
			'negative' => [-1],
			'non-integer' => ['1'],
		];
	}

	/**
	 * @dataProvider invalidObjectIdProvider
	 */
	public function testRejectsInvalidObjectIds(mixed $objectId): void {
		$this->expectException(\InvalidArgumentException::class);

		new TeamActivityScope('files', [$objectId]);
	}
}
