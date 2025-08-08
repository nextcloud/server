<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\lib\Config;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

class TestConfigLexicon_Migration implements IConfigLexicon {
	public const APPID = 'lexicon_test_migration';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::EXCEPTION;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry('key0', ValueType::STRING, 'default0'),
			new ConfigLexiconEntry('key1', ValueType::STRING, lazy: true)
		];
	}

	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry('key0', ValueType::STRING, 'default0'),
			new ConfigLexiconEntry('key1', ValueType::STRING, lazy: true)
		];
	}
}
