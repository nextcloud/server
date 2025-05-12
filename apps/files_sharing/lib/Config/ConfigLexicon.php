<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Config;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

/**
 * Config Lexicon for files_sharing.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see IConfigLexicon}
 */
class ConfigLexicon implements IConfigLexicon {
	public const SHOW_FEDERATED_AS_INTERNAL = 'show_federated_shares_as_internal';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry(self::SHOW_FEDERATED_AS_INTERNAL, ValueType::BOOL, false, 'shows federated shares as internal shares', true),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
