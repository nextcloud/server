<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Config;

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
	public const SHOW_FEDERATED_AS_INTERNAL = 'show_federated_shares_as_internal';
	public const SHOW_FEDERATED_TO_TRUSTED_AS_INTERNAL = 'show_federated_shares_to_trusted_servers_as_internal';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(self::SHOW_FEDERATED_AS_INTERNAL, ValueType::BOOL, false, 'shows federated shares as internal shares', true),
			new Entry(self::SHOW_FEDERATED_TO_TRUSTED_AS_INTERNAL, ValueType::BOOL, false, 'shows federated shares to trusted servers as internal shares', true),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
