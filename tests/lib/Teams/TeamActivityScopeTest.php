<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;
use OCP\Teams\ITeamActivityResourceProvider;
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

	public function testAllowsEmptyObjectIds(): void {
		$scope = new TeamActivityScope('files', []);

		$this->assertSame([], $scope->getObjectIds());
	}

	public function testReturnedObjectIdsDoNotMutateScope(): void {
		$scope = new TeamActivityScope('files', [1, 2, 3]);
		$objectIds = $scope->getObjectIds();
		$objectIds[] = 4;

		$this->assertSame([1, 2, 3], $scope->getObjectIds());
	}

	public function testScopeIsFinalAndReadonly(): void {
		$class = new \ReflectionClass(TeamActivityScope::class);

		$this->assertTrue($class->isFinal());
		$this->assertTrue($class->isReadOnly());
	}

	public function testScopeIsConsumable(): void {
		$class = new \ReflectionClass(TeamActivityScope::class);
		$attributes = $class->getAttributes(Consumable::class);

		$this->assertCount(1, $attributes);
		$this->assertSame('36.0.0', $attributes[0]->newInstance()->getSince());
	}

	public function testProviderIsImplementable(): void {
		$class = new \ReflectionClass(ITeamActivityResourceProvider::class);
		$attributes = $class->getAttributes(Implementable::class);

		$this->assertCount(1, $attributes);
		$this->assertSame('36.0.0', $attributes[0]->newInstance()->getSince());
	}
}
