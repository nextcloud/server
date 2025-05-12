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
	public const SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES = 'shareapi_allow_federation_on_public_shares';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry(
				key: self::SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES,
				type: ValueType::BOOL,
				lazy: true,
				defaultRaw: true,
				definition: 'adds share permission to public shares to allow adding them to your Nextcloud (federation)',
			),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
