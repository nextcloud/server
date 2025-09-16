<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings;

use OC\Config\UserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for settings.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 */
class ConfigLexicon implements ILexicon {
	public const USER_SETTINGS_MAIL = 'mail';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(key: self::USER_SETTINGS_MAIL, type: ValueType::STRING, defaultRaw: '', definition: 'account mail address', flags: UserConfig::FLAG_INDEXED),
		];
	}
}
