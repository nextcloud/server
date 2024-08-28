<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Settings;

/**
 * Special cases of settings that can be allowed to use by member of special
 * groups.
 * @since 23.0.0
 */
interface IDelegatedSettings extends ISettings {
	/**
	 * Get the name of the settings to differentiate settings inside a section or
	 * null if only the section name should be displayed.
	 * @since 23.0.0
	 */
	public function getName(): ?string;

	/**
	 * Get a list of authorized app config that this setting is allowed to modify.
	 * The format of the array is the following:
	 * ```php
	 * <?php
	 * [
	 * 		'app_name' => [
	 * 			'/simple_key/', # value
	 * 			'/s[a-z]*ldap/', # regex
	 * 		],
	 * 		'another_app_name => [ ... ],
	 * ]
	 * ```
	 * @since 23.0.0
	 */
	public function getAuthorizedAppConfig(): array;
}
