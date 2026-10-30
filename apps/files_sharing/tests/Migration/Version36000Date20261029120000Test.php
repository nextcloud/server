<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Migration;

use OC\Core\AppInfo\ConfigLexicon as CoreConfigLexicon;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCA\Files_Sharing\Migration\Version36000Date20261029120000;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class Version36000Date20261029120000Test extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private Version36000Date20261029120000 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->migration = new Version36000Date20261029120000($this->appConfig);
	}

	private function runMigration(bool $hasScope, bool $hasDeprecatedValue, ?IOutput $output = null): void {
		$this->appConfig->method('hasKey')
			->willReturnMap([
				['core', CoreConfigLexicon::SHARE_HIDE_DISABLED_USER_SHARES_SCOPE, null, $hasScope],
				['files_sharing', ConfigLexicon::HIDE_DISABLED_USER_SHARES, null, $hasDeprecatedValue],
			]);

		$this->migration->postSchemaChange($output ?? $this->createMock(IOutput::class), static fn () => null, []);
	}

	public static function dataMigrateDeprecatedValue(): array {
		return [
			[true, 'all'],
			[false, 'external'],
		];
	}

	#[DataProvider('dataMigrateDeprecatedValue')]
	public function testMigrateDeprecatedValue(bool $deprecatedValue, string $expectedScope): void {
		$this->appConfig->method('getValueBool')
			->willReturnMap([
				['files_sharing', ConfigLexicon::HIDE_DISABLED_USER_SHARES, $deprecatedValue],
			]);
		$this->appConfig->expects($this->once())
			->method('setValueString')
			->with('core', CoreConfigLexicon::SHARE_HIDE_DISABLED_USER_SHARES_SCOPE, $expectedScope);

		$this->runMigration(false, true);
	}

	public static function dataNoMigration(): array {
		return [
			[true, true],
			[true, false],
			[false, false],
		];
	}

	#[DataProvider('dataNoMigration')]
	public function testNoMigration(bool $hasScope, bool $hasDeprecatedValue): void {
		$this->appConfig->expects($this->never())
			->method('setValueString');

		$this->runMigration($hasScope, $hasDeprecatedValue);
	}

	public function testDeprecatedValueWithTypeConflict(): void {
		$this->appConfig->method('getValueBool')
			->willThrowException(new AppConfigTypeConflictException());
		$this->appConfig->expects($this->never())
			->method('setValueString');

		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('warning');

		$this->runMigration(false, true, $output);
	}
}
