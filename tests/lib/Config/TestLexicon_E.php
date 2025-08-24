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
use OCP\Config\Lexicon\Preset;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;
use OCP\IAppConfig;

class TestLexicon_E implements ILexicon {
	public const APPID = 'lexicon_test_e';

	public function getStrictness(): Strictness {
		return Strictness::EXCEPTION;
	}

	public function getAppConfigs(): array {
		return [
			new Entry('key1', ValueType::STRING, 'abcde', 'test key', true, IAppConfig::FLAG_SENSITIVE),
			new Entry('key2', ValueType::INT, 12345, 'test key', false),
			new Entry('key3', ValueType::STRING, fn (Preset $p): string => match ($p) {
				Preset::FAMILY => 'family',
				Preset::CLUB, Preset::MEDIUM => 'club+medium',
				default => 'none',
			}, 'test key'),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry('key1', ValueType::STRING, 'abcde', 'test key', true, IUserConfig::FLAG_SENSITIVE),
			new Entry('key2', ValueType::INT, 12345, 'test key', false),
			new Entry('key3', ValueType::STRING, fn (Preset $p): string => match ($p) {
				Preset::FAMILY => 'family',
				Preset::CLUB, Preset::MEDIUM => 'club+medium',
				default => 'none',
			}, 'test key'),
		];
	}
}
