<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Config\Lexicon;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

/**
 * ConfigLexicon for 'core' app/user configs
 */
class CoreConfigLexicon implements IConfigLexicon {
	/**
	 * @inheritDoc
	 * @return ConfigLexiconStrictness
	 * @since 31.0.0
	 */
	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 * @since 31.0.0
	 */
	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry('lastcron', ValueType::INT, 0, 'timestamp of last cron execution'),
		];
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 * @since 31.0.0
	 */
	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry('lang', ValueType::STRING, null, 'language'),
		];
	}
}
