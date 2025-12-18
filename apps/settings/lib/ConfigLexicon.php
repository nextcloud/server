<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings;

use OCP\Config\IUserConfig;
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
	public const USER_SETTINGS_EMAIL = 'email';
	public const USER_LIST_SHOW_STORAGE_PATH = 'user_list_show_storage_path';
	public const USER_LIST_SHOW_USER_BACKEND = 'user_list_show_user_backend';
	public const USER_LIST_SHOW_LAST_LOGIN = 'user_list_show_last_login';
	public const USER_LIST_SHOW_FIRST_LOGIN = 'user_list_show_first_login';
	public const USER_LIST_SHOW_NEW_USER_FORM = 'user_list_show_new_user_form';
	public const USER_LIST_SHOW_LANGUAGES = 'user_list_show_languages';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [];
	}

	public function getUserConfigs(): array {
		return [
			new Entry(
				key: self::USER_SETTINGS_EMAIL,
				type: ValueType::STRING,
				defaultRaw: '',
				definition: 'account mail address',
				flags: IUserConfig::FLAG_INDEXED,
			),
			new Entry(
				key: self::USER_LIST_SHOW_STORAGE_PATH,
				type: ValueType::BOOL,
				defaultRaw: false,
				definition: 'Show storage path column in user list',
				lazy: true,
			),
			new Entry(
				key: self::USER_LIST_SHOW_USER_BACKEND,
				type: ValueType::BOOL,
				defaultRaw: false,
				definition: 'Show user account backend column in user list',
				lazy: true,
			),
			new Entry(
				key: self::USER_LIST_SHOW_LAST_LOGIN,
				type: ValueType::BOOL,
				defaultRaw: false,
				definition: 'Show last login date column in user list',
				lazy: true,
			),
			new Entry(
				key: self::USER_LIST_SHOW_FIRST_LOGIN,
				type: ValueType::BOOL,
				defaultRaw: false,
				definition: 'Show first login date column in user list',
				lazy: true,
			),
			new Entry(
				key: self::USER_LIST_SHOW_NEW_USER_FORM,
				type: ValueType::BOOL,
				defaultRaw: false,
				definition: 'Show new user form in user list',
				lazy: true,
			),
			new Entry(
				key: self::USER_LIST_SHOW_LANGUAGES,
				type: ValueType::BOOL,
				defaultRaw: false,
				definition: 'Show languages in user list',
				lazy: true,
			),
		];
	}
}
