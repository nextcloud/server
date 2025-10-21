<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\AppInfo;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Preset;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for core.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 */
class ConfigLexicon implements ILexicon {
	public const SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES = 'shareapi_allow_federation_on_public_shares';
	public const SHARE_CUSTOM_TOKEN = 'shareapi_allow_custom_tokens';
	public const SHARE_LINK_PASSWORD_DEFAULT = 'shareapi_enable_link_password_by_default';
	public const SHARE_LINK_PASSWORD_ENFORCED = 'shareapi_enforce_links_password';
	public const SHARE_LINK_EXPIRE_DATE_DEFAULT = 'shareapi_default_expire_date';
	public const SHARE_LINK_EXPIRE_DATE_ENFORCED = 'shareapi_enforce_expire_date';
	public const USER_LANGUAGE = 'lang';
	public const OCM_DISCOVERY_ENABLED = 'ocm_discovery_enabled';
	public const OCM_INVITE_ACCEPT_DIALOG = 'ocm_invite_accept_dialog';

	public const USER_LOCALE = 'locale';
	public const USER_TIMEZONE = 'timezone';

	public const UNIFIED_SEARCH_MIN_SEARCH_LENGTH = 'unified_search_min_search_length';
	public const UNIFIED_SEARCH_MAX_RESULTS_PER_REQUEST = 'unified_search_max_results_per_request';

	public const LASTCRON_TIMESTAMP = 'lastcron';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(
				key: self::SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES,
				type: ValueType::BOOL,
				defaultRaw: true,
				definition: 'adds share permission to public shares to allow adding them to your Nextcloud (federation)',
			),
			new Entry(
				key: self::SHARE_CUSTOM_TOKEN,
				type: ValueType::BOOL,
				defaultRaw: fn (Preset $p): bool => match ($p) {
					Preset::FAMILY, Preset::PRIVATE => true,
					default => false,
				},
				definition: 'Allow users to customize share URL',
				note: 'Shares with guessable tokens may be accessed easily. Shares with custom tokens will continue to be accessible after this setting has been disabled.',
			),
			new Entry(self::SHARE_LINK_PASSWORD_DEFAULT, ValueType::BOOL, false, 'Ask for a password when sharing document by default'),
			new Entry(
				key: self::SHARE_LINK_PASSWORD_ENFORCED,
				type: ValueType::BOOL,
				defaultRaw: fn (Preset $p): bool => match ($p) {
					Preset::SCHOOL, Preset::UNIVERSITY, Preset::SHARED, Preset::SMALL, Preset::MEDIUM, Preset::LARGE => true,
					default => false,
				},
				definition: 'Enforce password protection for shared documents'
			),
			new Entry(
				key: self::SHARE_LINK_EXPIRE_DATE_DEFAULT,
				type: ValueType::BOOL,
				defaultRaw: fn (Preset $p): bool => match ($p) {
					Preset::SHARED, Preset::SMALL, Preset::MEDIUM, Preset::LARGE => true,
					default => false,
				},
				definition: 'Default expiration date for shares via link or mail'
			),
			new Entry(
				key: self::SHARE_LINK_EXPIRE_DATE_ENFORCED,
				type: ValueType::BOOL,
				defaultRaw: fn (Preset $p): bool => match ($p) {
					Preset::SHARED, Preset::SMALL, Preset::MEDIUM, Preset::LARGE => true,
					default => false,
				},
				definition: 'Enforce expiration date for shares via link or mail'
			),
			new Entry(self::LASTCRON_TIMESTAMP, ValueType::INT, 0, 'timestamp of last cron execution'),
			new Entry(self::OCM_DISCOVERY_ENABLED, ValueType::BOOL, true, 'enable/disable OCM'),
			new Entry(self::OCM_INVITE_ACCEPT_DIALOG, ValueType::STRING, '', 'route to local invite accept dialog', note: 'set as empty string to disable feature'),
			new Entry(self::UNIFIED_SEARCH_MIN_SEARCH_LENGTH, ValueType::INT, 1, 'Minimum search length to trigger the request', rename: 'unified-search.min-search-length'),
			new Entry(self::UNIFIED_SEARCH_MAX_RESULTS_PER_REQUEST, ValueType::INT, 25, 'Maximum results returned per search request', rename: 'unified-search.max-results-per-request'),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(self::USER_LANGUAGE, ValueType::STRING, definition: 'language'),
			new Entry(self::USER_LOCALE, ValueType::STRING, definition: 'locale'),
			new Entry(self::USER_TIMEZONE, ValueType::STRING, definition: 'timezone'),
		];
	}
}
