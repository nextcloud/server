<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Import;

use OC\Import\ImportScope;
use OCP\Import\IImportScope;
use OCP\Server;
use Test\TestCase;

class ImportScopeTest extends TestCase {
	private ImportScope $scope;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->scope = new ImportScope();
	}

	public function testInactiveOutsideRun(): void {
		$this->assertFalse($this->scope->isActive());
	}

	public function testActiveInsideRunAndReturnsResult(): void {
		$result = $this->scope->run(fn () => $this->scope->isActive() ? 'active' : 'inactive');

		$this->assertSame('active', $result);
		$this->assertFalse($this->scope->isActive());
	}

	public function testNestedRunsEndWithTheOutermost(): void {
		$states = $this->scope->run(function (): array {
			$inner = $this->scope->run(fn () => $this->scope->isActive());
			return [$inner, $this->scope->isActive()];
		});

		$this->assertSame([true, true], $states);
		$this->assertFalse($this->scope->isActive());
	}

	public function testEndsWhenTheCallbackThrows(): void {
		try {
			$this->scope->run(function (): void {
				throw new \RuntimeException('write failed');
			});
			$this->fail('The exception was swallowed');
		} catch (\RuntimeException $e) {
			$this->assertSame('write failed', $e->getMessage());
		}

		$this->assertFalse($this->scope->isActive());
	}

	public function testServerSharesOneScope(): void {
		$scope = Server::get(IImportScope::class);

		$this->assertSame($scope, Server::get(IImportScope::class));
		$this->assertTrue($scope->run(fn () => Server::get(IImportScope::class)->isActive()));
	}
}
