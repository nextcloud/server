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
	public const RECENT_LIMIT = 'recent_limit';
	public const GROUP_RECENT_FILES = 'group_recent_files';
	public const RECENT_FILES_GROUP_MIME_TYPES = 'recent_files_group_mime_types';
	public const RECENT_FILES_GROUP_TIMESPAN_MINUTES = 'recent_files_group_timespan_minutes';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
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
			new Entry(
				self::RECENT_LIMIT,
				ValueType::INT,
				defaultRaw: 100,
				definition: 'Maximum number of files to display on recent files view',
				lazy: false,
			),
			new Entry(
				self::GROUP_RECENT_FILES,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether to group recent files by MIME type or not',
				lazy: false,
			),
			new Entry(
				self::RECENT_FILES_GROUP_MIME_TYPES,
				ValueType::ARRAY,
				defaultRaw: [],
				definition: 'Which MIME types to group in the recent files list',
				lazy: false,
			),
			new Entry(
				self::RECENT_FILES_GROUP_TIMESPAN_MINUTES,
				ValueType::INT,
				defaultRaw: 2,
				definition: 'Time window in minutes to group files uploaded close together in the recent files list',
				lazy: false,
			),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
