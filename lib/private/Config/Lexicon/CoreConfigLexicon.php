<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config\Lexicon;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;
use OCP\IConfig;

/**
 * ConfigLexicon for 'core' app/user configs
 */
class CoreConfigLexicon implements IConfigLexicon {
	public const PRESET_SYSTEM_CONFIG_KEY = 'preset';
	/**	@since 32.0.0 */
	public const PRESET_SCHOOL = 'school';
	/**	@since 32.0.0 */
	public const PRESET_SPORTS_CLUB = 'sports-club';

	private string $preset;

	public function __construct(
		readonly IConfig $config
	) {
		$this->preset = $config->getSystemValue(self::PRESET_SYSTEM_CONFIG_KEY, '');
	}

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 */
	public function getAppConfigs(): array {
		$configs = [
			new ConfigLexiconEntry('lastcron', ValueType::INT, 0, 'timestamp of last cron execution'),
			new ConfigLexiconEntry('key1', ValueType::INT, 0, 'definition'),
		];

		switch($this->preset) {
			case self::PRESET_SCHOOL:
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 1, 'definition');
				break;

			case self::PRESET_SPORTS_CLUB:
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 2, 'definition');
				break;
		}

		return $configs;
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 */
	public function getUserConfigs(): array {
		$configs = [
			new ConfigLexiconEntry('lang', ValueType::STRING, null, 'language'),
			new ConfigLexiconEntry('key1', ValueType::INT, 0, 'definition'),
		];

		switch($this->preset) {
			case self::PRESET_SCHOOL:
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 1, 'definition');
				break;

			case self::PRESET_SPORTS_CLUB:
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 2, 'definition');
				break;
		}

		return $configs;
	}
}
