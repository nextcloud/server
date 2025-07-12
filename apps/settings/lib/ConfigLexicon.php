<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

/**
 * ConfigLexicon for 'settings' app/user configs
 */
class ConfigLexicon implements IConfigLexicon {
	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 */
	public function getAppConfigs(): array {
		return [
		];
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 */
	public function getUserConfigs(): array {
		return [
			(new ConfigLexiconEntry('email', ValueType::STRING, '', 'email'))->onSet(function (string &$value): void { $value = strtolower($value); }),
		];
	}
}
