<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for files.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const OVERWRITES_HOME_FOLDERS = 'overwrites_home_folders';

	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(
				self::OVERWRITES_HOME_FOLDERS,
				ValueType::ARRAY,
				defaultRaw: [],
				definition: 'List of applications overwriting home folders',
				lazy: false,
				note: 'It will be populated with app IDs of mount providers that overwrite home folders. Currently, only files_external and groupfolders.',
			),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
