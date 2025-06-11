<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\lib\Config;

use NCU\Config\IUserConfig;
use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconPreset;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;
use OCP\IAppConfig;

class TestConfigLexicon_E implements IConfigLexicon {
	public const APPID = 'lexicon_test_e';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::EXCEPTION;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry('key1', ValueType::STRING, 'abcde', 'test key', true, IAppConfig::FLAG_SENSITIVE),
			new ConfigLexiconEntry('key2', ValueType::INT, 12345, 'test key', false),
			new ConfigLexiconEntry('key3', ValueType::STRING, fn (ConfigLexiconPreset $p): string => match ($p) {
				ConfigLexiconPreset::FAMILY => 'family',
				ConfigLexiconPreset::CLUB, ConfigLexiconPreset::MEDIUM => 'club+medium',
				default => 'none',
			}, 'test key'),
		];
	}

	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry('key1', ValueType::STRING, 'abcde', 'test key', true, IUserConfig::FLAG_SENSITIVE),
			new ConfigLexiconEntry('key2', ValueType::INT, 12345, 'test key', false),
			new ConfigLexiconEntry('key3', ValueType::STRING, fn (ConfigLexiconPreset $p): string => match ($p) {
				ConfigLexiconPreset::FAMILY => 'family',
				ConfigLexiconPreset::CLUB, ConfigLexiconPreset::MEDIUM => 'club+medium',
				default => 'none',
			}, 'test key'),
		];
	}
}
