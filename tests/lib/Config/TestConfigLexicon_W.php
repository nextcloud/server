<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\lib\Config;

use NCU\Config\IUserConfig;
use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;
use OCP\IAppConfig;

class TestConfigLexicon_W implements IConfigLexicon {
	public const APPID = 'lexicon_test_w';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::WARNING;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry('key1', ValueType::STRING, 'abcde', 'test key', true, IAppConfig::FLAG_SENSITIVE),
			new ConfigLexiconEntry('key2', ValueType::INT, 12345, 'test key', false)

		];
	}

	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry('key1', ValueType::STRING, 'abcde', 'test key', true, IUserConfig::FLAG_SENSITIVE),
			new ConfigLexiconEntry('key2', ValueType::INT, 12345, 'test key', false)		];
	}

}
