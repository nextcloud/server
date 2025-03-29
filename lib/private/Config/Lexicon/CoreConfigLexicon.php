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
	public function __construct(
		private IConfig $config
	) {
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

		switch($this->config->getSystemValue('operational-mode', '')) {
			case 'school':
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 1, 'definition');
				break;

			case 'sport-club':
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 0, 'definition');
				break;
		}

		return array_values($configs);
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

		switch($this->config->getSystemValue('operational-mode', '')) {
			case 'school':
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 1, 'definition');
				break;

			case 'sport-club':
				$configs[] = new ConfigLexiconEntry('key1', ValueType::INT, 12, 'definition');
				break;
		}

		return array_values($configs);
	}
}
