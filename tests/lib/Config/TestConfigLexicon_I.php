<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\lib\Config;

use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;
use OCP\IAppConfig;

class TestConfigLexicon_I implements ILexicon {
	public const APPID = 'lexicon_test_i';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry('key1', ValueType::STRING, 'abcde', 'test key', true, IAppConfig::FLAG_SENSITIVE),
			new Entry('key2', ValueType::INT, 12345, 'test key', false),
			new Entry('key3', ValueType::INT, 12345, 'test key', true, rename: 'old_key3'),
			new Entry('key4', ValueType::BOOL, 12345, 'test key', true, rename: 'old_key4', options: Entry::RENAME_INVERT_BOOLEAN),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry('key1', ValueType::STRING, 'abcde', 'test key', true, IUserConfig::FLAG_SENSITIVE),
			new Entry('key2', ValueType::INT, 12345, 'test key', false)
		];
	}

}
