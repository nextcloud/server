<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\L10N;

use OCP\IUser;

/**
 * @since 8.2.0
 */
interface IFactory {
	/**
	 * Get a language instance
	 *
	 * @param string $app
	 * @param string|null $lang
	 * @param string|null $locale
	 * @return \OCP\IL10N
	 * @since 8.2.0
	 */
	public function get($app, $lang = null, $locale = null);

	/**
	 * Find the best language for the context of the current user
	 *
	 * This method will try to find the most specific language based on info
	 * from the user who is logged into the current process and will fall
	 * back to system settings and heuristics otherwise.
	 *
	 * @param string|null $appId specify if you only want a language a specific app supports
	 *
	 * @return string language code, defaults to 'en' if no other matches are found
	 * @since 9.0.0
	 */
	public function findLanguage(?string $appId = null): string;

	/**
	 * Try to find the best language for generic tasks
	 *
	 * This method will try to find the most generic language based on system
	 * settings, independent of the user logged into the current process. This
	 * is useful for tasks that are run for another user. E.g. the current user
	 * sends an email to someone else, then we don't want the current user's
	 * language to be picked but rather a instance-wide default that likely fits
	 * the target user
	 *
	 * @param string|null $appId specify if you only want a language a specific app supports
	 *
	 * @return string language code, defaults to 'en' if no other matches are found
	 * @since 23.0.0
	 */
	public function findGenericLanguage(string $appId = null): string;

	/**
	 * @param string|null $lang user language as default locale
	 * @return string locale If nothing works it returns 'en_US'
	 * @since 14.0.0
	 */
	public function findLocale($lang = null);

	/**
	 * find the matching lang from the locale
	 *
	 * @param string $app
	 * @param string $locale
	 * @return null|string
	 * @since 14.0.1
	 */
	public function findLanguageFromLocale(string $app = 'core', string $locale = null);

	/**
	 * Find all available languages for an app
	 *
	 * @param string|null $app App id or null for core
	 * @return string[] an array of available languages
	 * @since 9.0.0
	 */
	public function findAvailableLanguages($app = null): array;

	/**
	 * @return array an array of available
	 * @since 14.0.0
	 */
	public function findAvailableLocales();

	/**
	 * @param string|null $app App id or null for core
	 * @param string $lang
	 * @return bool
	 * @since 9.0.0
	 */
	public function languageExists($app, $lang);

	/**
	 * @param string $locale
	 * @return bool
	 * @since 14.0.0
	 */
	public function localeExists($locale);

	/**
	 * iterate through language settings (if provided) in this order:
	 * 1. returns the forced language or:
	 * 2. if applicable, the trunk of 1 (e.g. "fu" instead of "fu_BAR"
	 * 3. returns the user language or:
	 * 4. if applicable, the trunk of 3
	 * 5. returns the system default language or:
	 * 6. if applicable, the trunk of 5
	 * 7+∞. returns 'en'
	 *
	 * Hint: in most cases findLanguage() suits you fine
	 *
	 * @since 14.0.0
	 */
	public function getLanguageIterator(IUser $user = null): ILanguageIterator;

	/**
	 * returns the common language and other languages in an
	 * associative array
	 *
	 * @since 23.0.0
	 */
	public function getLanguages(): array;

	/**
	 * Return the language to use when sending something to a user
	 *
	 * @param IUser|null $user
	 * @return string
	 * @since 20.0.0
	 */
	public function getUserLanguage(IUser $user = null): string;
}
