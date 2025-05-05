<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

/**
 * Config Lexicon for files_sharing.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see IConfigLexicon}
 */
class ConfigLexicon implements IConfigLexicon {
	public const ALLOW_USER_MOUNTING = 'allow_user_mounting';
	public const USER_MOUNTING_BACKENDS = 'user_mounting_backends';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::NOTICE;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry(self::ALLOW_USER_MOUNTING, ValueType::BOOL, false, 'allow users to mount their own external filesystems', true),
			new ConfigLexiconEntry(self::USER_MOUNTING_BACKENDS, ValueType::STRING, '', 'list of mounting backends available for users', true),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
