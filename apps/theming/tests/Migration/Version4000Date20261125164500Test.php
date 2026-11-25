<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Migration;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Migration\Version4000Date20261125164500;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class Version4000Date20261125164500Test extends TestCase {
	private IConfig&MockObject $config;
	private Version4000Date20261125164500MigrationUnderTest $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->migration = new Version4000Date20261125164500MigrationUnderTest(
			$this->config,
			$this->createMock(IDBConnection::class),
		);
	}

	/**
	 * @param list<string> $themeIds
	 * @param list<string>|null $expected
	 */
	#[DataProvider(methodName: 'themeIdProvider')]
	public function testMigrateThemeIds(array $themeIds, ?array $expected): void {
		self::assertSame($expected, Version4000Date20261125164500::migrateThemeIds($themeIds));
	}

	/**
	 * @return list<array{list<string>, list<string>|null}>
	 */
	public static function themeIdProvider(): array {
		return [
			'light high contrast only' => [['light-highcontrast'], ['light', 'highcontrast']],
			'dark high contrast only' => [['dark-highcontrast'], ['dark', 'highcontrast']],
			'keeps themes around the replacement' => [['opendyslexic', 'dark-highcontrast'], ['opendyslexic', 'dark', 'highcontrast']],
			'replaces light high contrast in place' => [['light-highcontrast', 'opendyslexic'], ['light', 'highcontrast', 'opendyslexic']],
			'replaces each legacy theme' => [['light-highcontrast', 'dark-highcontrast'], ['light', 'highcontrast', 'dark']],
			'replaces a later light high contrast theme' => [['dark-highcontrast', 'light-highcontrast', 'opendyslexic'], ['dark', 'highcontrast', 'light', 'opendyslexic']],
			'keeps an existing color scheme' => [['default', 'light-highcontrast'], ['default', 'light', 'highcontrast']],
			'does not duplicate high contrast' => [['highcontrast', 'light-highcontrast'], ['highcontrast', 'light']],
			'leaves current themes untouched' => [['light', 'opendyslexic'], null],
			'leaves an empty list untouched' => [[], null],
		];
	}

	#[DataProvider(methodName: 'storedValueProvider')]
	public function testMigrateEnabledThemesValue(string $stored, ?string $expected): void {
		self::assertSame($expected, Version4000Date20261125164500::migrateEnabledThemesValue($stored));
	}

	/**
	 * @return list<array{string, string|null}>
	 */
	public static function storedValueProvider(): array {
		return [
			'json list' => ['["light-highcontrast"]', '["light","highcontrast"]'],
			'already migrated' => ['["light","highcontrast"]', null],
			'invalid json' => ['not-json', null],
			'json object' => ['{"theme":"light-highcontrast"}', null],
			'non-string entry' => ['["light-highcontrast",1]', null],
		];
	}

	#[DataProvider(methodName: 'enforcedThemeProvider')]
	public function testMigrateEnforcedThemeId(string $themeId, ?string $expected): void {
		self::assertSame($expected, Version4000Date20261125164500::migrateEnforcedThemeId($themeId));
	}

	/**
	 * @return list<array{string, string|null}>
	 */
	public static function enforcedThemeProvider(): array {
		return [
			'light high contrast' => ['light-highcontrast', 'highcontrast'],
			'dark high contrast' => ['dark-highcontrast', 'highcontrast'],
			'current theme' => ['dark', null],
			'empty' => ['', null],
		];
	}

	public function testPostSchemaChangeRewritesPreferencesAndEnforcedTheme(): void {
		$this->migration->preferences = [
			['userid' => 'alice', 'configvalue' => '["dark-highcontrast","opendyslexic"]'],
			['userid' => 'bob', 'configvalue' => '["light"]'],
			['userid' => 'carol', 'configvalue' => '{"light-highcontrast"}'],
		];
		$this->config->expects(self::once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('light-highcontrast');
		$this->config->expects(self::once())
			->method('setSystemValue')
			->with('enforce_theme', 'highcontrast');
		$this->config->expects(self::once())
			->method('setUserValue')
			->with('alice', Application::APP_ID, 'enabled-themes', '["dark","highcontrast","opendyslexic"]');

		$output = $this->createMock(IOutput::class);
		$output->expects(self::exactly(2))->method('info');
		$output->expects(self::once())
			->method('warning')
			->with('Skipped 1 invalid enabled-themes values that still name a legacy high contrast theme');

		$this->migration->postSchemaChange($output, static fn () => null, []);
	}

	public function testPostSchemaChangeLeavesOtherEnforcedThemes(): void {
		$this->migration->preferences = [];
		$this->config->expects(self::once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('dark');
		$this->config->expects(self::never())->method('setSystemValue');
		$this->config->expects(self::never())->method('setUserValue');

		$output = $this->createMock(IOutput::class);
		$output->expects(self::once())
			->method('info')
			->with('Migrated high contrast preferences for 0 users');
		$output->expects(self::never())->method('warning');

		$this->migration->postSchemaChange($output, static fn () => null, []);
	}
}

class Version4000Date20261125164500MigrationUnderTest extends Version4000Date20261125164500 {
	/** @var list<array{userid: string, configvalue: string}> */
	public array $preferences = [];

	protected function loadEnabledThemePreferences(): array {
		return $this->preferences;
	}
}
