<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for files_sharing.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const SYSTEM_ADDRESSBOOK_EXPOSED = 'system_addressbook_exposed';

	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(
				self::SYSTEM_ADDRESSBOOK_EXPOSED,
				ValueType::BOOL,
				defaultRaw: true,
				definition: 'Whether to not expose the system address book to users',
				lazy: true,
			),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
