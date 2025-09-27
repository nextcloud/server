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

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry(self::UNIFIED_SEARCH_MIN_SEARCH_LENGTH, ValueType::INT, 1, 'Minimum search length to trigger the request', lazy: false),
		];
	}

	public function getUserConfigs(): array {
		return [
		];
	}
}
