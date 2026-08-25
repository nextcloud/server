<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db\Migrations;

use OC\Core\Command\Db\Migrations\StatusCommand;
use OC\DB\Connection;
use OC\DB\MigrationService;
use OCP\App\IAppManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StatusCommandTest extends TestCase {
	private const VERSION_1 = '10000Date20160101000000';
	private const VERSION_2 = '20000Date20200101000000';
	private const VERSION_3 = '30000Date20240101000000';
	private const MISSING_VERSION = '25000Date20220101000000';

	private StatusCommand $command;
	private MigrationService&MockObject $migrationService;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->command = new StatusCommand(
			$this->createMock(Connection::class),
			$this->createMock(IAppManager::class),
		);

		$this->migrationService = $this->createMock(MigrationService::class);
		$this->migrationService->method('getApp')->willReturn('test');
		$this->migrationService->method('getMigrationsTableName')->willReturn('oc_migrations');
		$this->migrationService->method('getMigrationsNamespace')->willReturn('OCA\Test\Migration');
		$this->migrationService->method('getMigrationsDirectory')->willReturn('/tmp/test/lib/Migration');
		$this->migrationService->method('describeMigrationStep')->willReturn([]);
	}

	/**
	 * @param list<string> $executedMigrations
	 * @param list<string> $availableMigrations
	 * @param array<string, string> $expectedStatuses
	 */
	#[DataProvider('versionStatusProvider')]
	public function testVersionStatuses(
		array $executedMigrations,
		array $availableMigrations,
		array $expectedStatuses,
	): void {
		$this->migrationService
			->method('getMigratedVersions')
			->willReturn($executedMigrations);
		$this->migrationService
			->method('getAvailableVersions')
			->willReturn($availableMigrations);

		$infos = $this->command->getMigrationsInfos($this->migrationService);

		foreach ($expectedStatuses as $label => $expectedStatus) {
			$this->assertSame($expectedStatus, $infos[$label], $label);
		}
	}

	public static function versionStatusProvider(): array {
		$availableMigrations = [
			self::VERSION_1,
			self::VERSION_2,
			self::VERSION_3,
		];

		return [
			'no migrations recorded as executed' => [
				[],
				$availableMigrations,
				[
					'Previous Available' => 'None (no migrations recorded as executed)',
					'Last Recorded as Executed' => 'None (no migrations recorded as executed)',
					'Next Available' => self::VERSION_1,
					'Latest Available' => self::VERSION_3,
				],
			],
			'at first available migration' => [
				[self::VERSION_1],
				$availableMigrations,
				[
					'Previous Available' => 'None (at first available migration)',
					'Last Recorded as Executed' => self::VERSION_1,
					'Next Available' => self::VERSION_2,
					'Latest Available' => self::VERSION_3,
				],
			],
			'at intermediate migration' => [
				[self::VERSION_1, self::VERSION_2],
				$availableMigrations,
				[
					'Previous Available' => self::VERSION_1,
					'Last Recorded as Executed' => self::VERSION_2,
					'Next Available' => self::VERSION_3,
					'Latest Available' => self::VERSION_3,
				],
			],
			'at latest available migration' => [
				$availableMigrations,
				$availableMigrations,
				[
					'Previous Available' => self::VERSION_2,
					'Last Recorded as Executed' => self::VERSION_3,
					'Next Available' => 'None (at latest available migration)',
					'Latest Available' => self::VERSION_3,
				],
			],
			'last executed migration is missing from code' => [
				[self::VERSION_1, self::MISSING_VERSION],
				$availableMigrations,
				[
					'Previous Available' => 'Unknown (last executed migration is missing from code)',
					'Last Recorded as Executed' => self::MISSING_VERSION,
					'Next Available' => 'Unknown (last executed migration is missing from code)',
					'Latest Available' => self::VERSION_3,
				],
			],
			'no migration files found' => [
				[],
				[],
				[
					'Previous Available' => 'None (no migrations recorded as executed)',
					'Last Recorded as Executed' => 'None (no migrations recorded as executed)',
					'Next Available' => 'None (no migration files found)',
					'Latest Available' => 'None (no migration files found)',
				],
			],
		];
	}
}
