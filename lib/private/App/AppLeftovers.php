<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App;

/**
 * What an app that is no longer enabled has left behind in the database and the app data folder.
 */
final class AppLeftovers {
	/**
	 * @param bool $codePresent whether the app's code is still installed; without it, its tables cannot be found
	 * @param list<string> $tables tables its migrations create that exist, with the table prefix
	 * @param ?int $appDataSize size in bytes of its app data folder, or null when it has none
	 */
	public function __construct(
		public readonly string $appId,
		public readonly bool $codePresent,
		public readonly array $tables,
		public readonly int $configKeys,
		public readonly int $userPreferences,
		public readonly int $backgroundJobs,
		public readonly int $migrations,
		public readonly ?int $appDataSize,
	) {
	}

	public function isEmpty(): bool {
		return $this->tables === []
			&& $this->configKeys === 0
			&& $this->userPreferences === 0
			&& $this->backgroundJobs === 0
			&& $this->migrations === 0
			&& $this->appDataSize === null;
	}

	/**
	 * @return array{app: string, code_present: bool, tables: list<string>, config_keys: int, user_preferences: int, background_jobs: int, migrations: int, app_data_size: ?int}
	 */
	public function toArray(): array {
		return [
			'app' => $this->appId,
			'code_present' => $this->codePresent,
			'tables' => $this->tables,
			'config_keys' => $this->configKeys,
			'user_preferences' => $this->userPreferences,
			'background_jobs' => $this->backgroundJobs,
			'migrations' => $this->migrations,
			'app_data_size' => $this->appDataSize,
		];
	}
}
