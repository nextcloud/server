<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\AppInfo;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

/**
 * Config Lexicon for core.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 */
class ConfigLexicon implements IConfigLexicon {
	public const UNIFIED_SEARCH_MIN_SEARCH_LENGTH = 'unified_search_min_search_length';
	public const UNIFIED_SEARCH_MAX_RESULTS_PER_REQUEST = 'unified_search_max_results_per_request';
	public const USER_LANGUAGE = 'lang';
	public const LASTCRON_TIMESTAMP = 'lastcron';
	public const USER_LOCALE = 'locale';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry(self::UNIFIED_SEARCH_MIN_SEARCH_LENGTH, ValueType::INT, 1, 'Minimum search length to trigger the request', lazy: false),
			new ConfigLexiconEntry(self::UNIFIED_SEARCH_MAX_RESULTS_PER_REQUEST, ValueType::INT, 25, 'Maximum number of results returned per request', lazy: false),
			new ConfigLexiconEntry(self::LASTCRON_TIMESTAMP, ValueType::INT, 0, 'timestamp of last cron execution'),
		];
	}

	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry(self::USER_LANGUAGE, ValueType::STRING, null, 'language'),
			new ConfigLexiconEntry(self::USER_LOCALE, ValueType::STRING, null, 'locale'),
		];
	}
}
