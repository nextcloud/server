<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Federation;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for federation.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const string IGNORE_REMOTE_SYSTEM_ADDRESS_BOOK = 'ignore_remote_system_address_book';

	#[\Override]
	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	#[\Override]
	public function getAppConfigs(): array {
		return [
			new Entry(self::IGNORE_REMOTE_SYSTEM_ADDRESS_BOOK, ValueType::BOOL, true, 'Ignore system address book from trusted servers', true, note: 'This does not stop your own instance from replying to request from trusted servers'),
		];
	}

	#[\Override]
	public function getUserConfigs(): array {
		return [];
	}
}
