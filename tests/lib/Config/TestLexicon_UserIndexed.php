<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\lib\Config;

use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

class TestLexicon_UserIndexed implements ILexicon {
	public const APPID = 'lexicon_user_indexed';
	public function getStrictness(): Strictness {
		return Strictness::EXCEPTION;
	}

	public function getAppConfigs(): array {
		return [
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(key: 'key1', type: ValueType::STRING, defaultRaw: '', definition: 'test key', flags: IUserConfig::FLAG_INDEXED),
		];
	}
}
