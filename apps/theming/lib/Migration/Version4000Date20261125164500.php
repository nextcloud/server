<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Migration;

use OCA\Theming\AppInfo\Application;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * light-highcontrast and dark-highcontrast were full themes.
 * High contrast is now supplementary and keeps the selected color scheme.
 * enforce_theme still names a single theme, so both legacy values become highcontrast
 * and the palette follows the system color scheme.
 */
#[DataCleansing(table: 'preferences', description: 'Replace legacy high contrast theme ids in enabled-themes')]
class Version4000Date20261125164500 extends SimpleMigrationStep {
	public const string LEGACY_LIGHT_HIGH_CONTRAST = 'light-highcontrast';
	public const string LEGACY_DARK_HIGH_CONTRAST = 'dark-highcontrast';
	public const string HIGH_CONTRAST = 'highcontrast';
	private const string ENABLED_THEMES = 'enabled-themes';

	/** @var array<string, list<string>> */
	private const array LEGACY_REPLACEMENTS = [
		self::LEGACY_LIGHT_HIGH_CONTRAST => ['light', self::HIGH_CONTRAST],
		self::LEGACY_DARK_HIGH_CONTRAST => ['dark', self::HIGH_CONTRAST],
	];

	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
	) {
	}

	#[\Override]
	public function name(): string {
		return 'Migrate legacy high contrast themes';
	}

	#[\Override]
	public function description(): string {
		return 'Replace light-highcontrast and dark-highcontrast in user preferences and enforce_theme';
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->migrateEnforcedTheme($output);
		$preferences = $this->loadEnabledThemePreferences();
		$updated = 0;
		$skipped = 0;
		foreach ($preferences as $preference) {
			$migrated = self::migrateEnabledThemesValue($preference['configvalue']);
			if ($migrated === null) {
				if (self::containsLegacyTheme($preference['configvalue'])) {
					$skipped++;
				}
				continue;
			}
			$this->config->setUserValue($preference['userid'], Application::APP_ID, self::ENABLED_THEMES, $migrated);
			$updated++;
		}

		$output->info(sprintf('Migrated high contrast preferences for %d users', $updated));
		if ($skipped > 0) {
			$output->warning(sprintf(
				'Skipped %d invalid enabled-themes values that still name a legacy high contrast theme',
				$skipped,
			));
		}
	}

	private static function containsLegacyTheme(string $stored): bool {
		return str_contains($stored, self::LEGACY_LIGHT_HIGH_CONTRAST)
			|| str_contains($stored, self::LEGACY_DARK_HIGH_CONTRAST);
	}

	/**
	 * Map a stored enforce_theme value.
	 * Returns null when the value does not need to change.
	 */
	public static function migrateEnforcedThemeId(string $themeId): ?string {
		if ($themeId === self::LEGACY_LIGHT_HIGH_CONTRAST || $themeId === self::LEGACY_DARK_HIGH_CONTRAST) {
			return self::HIGH_CONTRAST;
		}

		return null;
	}

	/**
	 * Map a stored enabled-themes JSON list.
	 * Returns null when the value does not need to change or cannot be read.
	 *
	 * @param list<string> $themeIds
	 * @return list<string>|null
	 */
	public static function migrateThemeIds(array $themeIds): ?array {
		$migrated = [];
		$changed = false;
		foreach ($themeIds as $themeId) {
			if (isset(self::LEGACY_REPLACEMENTS[$themeId])) {
				array_push($migrated, ...self::LEGACY_REPLACEMENTS[$themeId]);
				$changed = true;
				continue;
			}
			$migrated[] = $themeId;
		}
		if (!$changed) {
			return null;
		}

		$unique = [];
		foreach ($migrated as $id) {
			if (!in_array($id, $unique, true)) {
				$unique[] = $id;
			}
		}

		return $unique;
	}

	/**
	 * Map a stored enabled-themes preference.
	 * Returns the new JSON value, or null when it should be left untouched.
	 */
	public static function migrateEnabledThemesValue(string $stored): ?string {
		try {
			$decoded = json_decode($stored, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return null;
		}
		if (!is_array($decoded) || !array_is_list($decoded)) {
			return null;
		}

		$themeIds = [];
		foreach ($decoded as $themeId) {
			if (!is_string($themeId)) {
				return null;
			}
			$themeIds[] = $themeId;
		}

		$migrated = self::migrateThemeIds($themeIds);
		if ($migrated === null) {
			return null;
		}

		try {
			return json_encode($migrated, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return null;
		}
	}

	private function migrateEnforcedTheme(IOutput $output): void {
		$current = $this->config->getSystemValueString('enforce_theme', '');
		$migrated = self::migrateEnforcedThemeId($current);
		if ($migrated === null) {
			return;
		}

		$this->config->setSystemValue('enforce_theme', $migrated);
		$output->info(sprintf(
			'Replaced enforced theme "%s" with "%s". High contrast now follows the system color scheme because enforce_theme can only select one theme.',
			$current,
			$migrated,
		));
	}

	/**
	 * Preferences that still name a legacy high contrast theme.
	 *
	 * @return list<array{userid: string, configvalue: string}>
	 */
	protected function loadEnabledThemePreferences(): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('userid', 'configvalue')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter(Application::APP_ID)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter(self::ENABLED_THEMES)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->like('configvalue', $qb->createNamedParameter(
					'%' . $this->connection->escapeLikeParameter(self::LEGACY_LIGHT_HIGH_CONTRAST) . '%',
					IQueryBuilder::PARAM_STR,
				)),
				$qb->expr()->like('configvalue', $qb->createNamedParameter(
					'%' . $this->connection->escapeLikeParameter(self::LEGACY_DARK_HIGH_CONTRAST) . '%',
					IQueryBuilder::PARAM_STR,
				)),
			))
			->executeQuery();

		$preferences = [];
		while ($row = $result->fetchAssociative()) {
			$row = array_change_key_case($row, CASE_LOWER);
			$preferences[] = [
				'userid' => (string)$row['userid'],
				'configvalue' => (string)$row['configvalue'],
			];
		}
		$result->closeCursor();

		return $preferences;
	}
}
