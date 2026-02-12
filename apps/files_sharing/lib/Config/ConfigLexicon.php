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
	public const EXCLUDE_RESHARE_FROM_EDIT = 'shareapi_exclude_reshare_from_edit';
	public const UPDATE_SINGLE_CUTOFF = 'update_single_cutoff';
	public const UPDATE_ALL_CUTOFF = 'update_all_cutoff';
	public const USER_NEEDS_SHARE_REFRESH = 'user_needs_share_refresh';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(self::SHOW_FEDERATED_AS_INTERNAL, ValueType::BOOL, false, 'shows federated shares as internal shares', true),
			new Entry(self::SHOW_FEDERATED_TO_TRUSTED_AS_INTERNAL, ValueType::BOOL, false, 'shows federated shares to trusted servers as internal shares', true),
			new Entry(self::EXCLUDE_RESHARE_FROM_EDIT, ValueType::BOOL, false, 'Exclude reshare permission from "Allow editing" bundled permissions'),

			new Entry(self::UPDATE_SINGLE_CUTOFF, ValueType::INT, 10, 'For how many users do we update the share data immediately for single-share updates'),
			new Entry(self::UPDATE_ALL_CUTOFF, ValueType::INT, 3, 'For how many users do we update the share data immediately for all-share updates'),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(self::USER_NEEDS_SHARE_REFRESH, ValueType::BOOL, false, 'whether a user needs to have the receiving share data refreshed for possible changes'),
		];
	}
}
