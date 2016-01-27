<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\L10N;

/**
 * @since 8.2.0
 */
interface IFactory {
	/**
	 * Get a language instance
	 *
	 * @param string $app
	 * @param string|null $lang
	 * @return \OCP\IL10N
	 * @since 8.2.0
	 */
	public function get($app, $lang = null);

	/**
	 * Find the best language
	 *
	 * @param string|null $app App id or null for core
	 * @return string language If nothing works it returns 'en'
	 * @since 9.0.0
	 */
	public function findLanguage($app = null);

	/**
	 * Find all available languages for an app
	 *
	 * @param string|null $app App id or null for core
	 * @return string[] an array of available languages
	 * @since 9.0.0
	 */
	public function findAvailableLanguages($app = null);

	/**
	 * @param string|null $app App id or null for core
	 * @param string $lang
	 * @return bool
	 * @since 9.0.0
	 */
	public function languageExists($app, $lang);

	/**
	 * @param string|null $app App id or null for core
	 * @return string
	 * @since 9.0.0
	 */
	public function setLanguageFromRequest($app = null);


	/**
	 * Creates a function from the plural string
	 *
	 * @param string $string
	 * @return string Unique function name
	 * @since 9.0.0
	 */
	public function createPluralFunction($string);
}
