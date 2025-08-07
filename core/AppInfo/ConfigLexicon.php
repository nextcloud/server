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

	public const USER_LANGUAGE = 'lang';
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
				lazy: true,
			),
			new Entry(
				key: self::SHARE_CUSTOM_TOKEN,
				type: ValueType::BOOL,
				defaultRaw: fn (Preset $p): bool => match ($p) {
					Preset::FAMILY, Preset::PRIVATE => true,
					default => false,
				},
				definition: 'Allow users to set custom share link tokens',
				lazy: true,
				note: 'Shares with guessable tokens may be accessed easily. Shares with custom tokens will continue to be accessible after this setting has been disabled.',
			),
			new Entry(self::SHARE_LINK_PASSWORD_DEFAULT, ValueType::BOOL, false, 'Always ask for a password when sharing document'),
			new Entry(
				key: self::SHARE_LINK_PASSWORD_ENFORCED,
				type: ValueType::BOOL,
				defaultRaw: fn (Preset $p): bool => match ($p) {
					Preset::SCHOOL, Preset::UNIVERSITY, Preset::SHARED, Preset::SMALL, Preset::MEDIUM, Preset::LARGE => true,
					default => false,
				},
				definition: 'Enforce password protection when sharing document'
			),
			new Entry(self::LASTCRON_TIMESTAMP, ValueType::INT, 0, 'timestamp of last cron execution'),
		];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(self::USER_LANGUAGE, ValueType::STRING, null, 'language'),
		];
	}
}
